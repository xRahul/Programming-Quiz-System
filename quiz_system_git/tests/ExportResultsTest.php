<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * T5.2 results CSV export: auth gate, column shape, percentage math,
 * scope filtering (top=20 vs all) and the attachment filename contract.
 */
final class ExportResultsTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8101;
    private const ADMIN_USER = 'admin';
    private const ADMIN_PASS = '12345';

    private const SEED_QUIZ_ID = 987400;
    private const SEED_QUIZ_NAME = 'CSV Export Quiz!';
    private const TAKER_COUNT = 23;

    private static string $base;
    private static string $anonJar;
    private static string $authJar;
    /** @var resource|null */
    private static $server = null;
    private static ?PDO $pdo = null;

    public static function setUpBeforeClass(): void
    {
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$anonJar = tempnam(sys_get_temp_dir(), 'csv_anon_');
        self::$authJar = tempnam(sys_get_temp_dir(), 'csv_auth_');

        $docroot = dirname(__DIR__);
        $cmd = sprintf(
            '%s -S %s:%d -t %s',
            escapeshellarg(PHP_BINARY),
            self::HOST,
            self::PORT,
            escapeshellarg($docroot)
        );
        self::$server = proc_open(
            $cmd,
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes
        );
        self::assertIsResource(self::$server, 'Failed to start built-in server');

        $ready = false;
        for ($i = 0; $i < 50; $i++) {
            if ((int) proc_get_status(self::$server)['running'] !== 1) {
                break;
            }
            [$status] = self::request('GET', 'login.php', null, self::$anonJar);
            if ($status === 200) {
                $ready = true;
                break;
            }
            usleep(100_000);
        }
        self::assertTrue($ready, 'built-in server did not become ready');

        require_once dirname(__DIR__) . '/lib/config.php';
        self::$pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );

        self::seed();
        [, $redirect] = self::request('POST', 'login_check.php', [
            'login' => self::ADMIN_USER,
            'password' => self::ADMIN_PASS,
        ], self::$authJar);
        self::assertStringContainsString('admin.php', (string) $redirect, 'admin login should succeed');
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$pdo !== null) {
            self::$pdo->exec('DELETE FROM quiz_takers WHERE quiz_id = ' . self::SEED_QUIZ_ID);
            self::$pdo->exec('DELETE FROM quizes WHERE id = ' . self::SEED_QUIZ_ID);
        }
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        @unlink(self::$anonJar);
        @unlink(self::$authJar);
        self::$pdo = null;
    }

    /**
     * One dedicated quiz with 23 seeded takers: marks cycle so ordering is
     * fully determined by (marks desc, duration asc).
     */
    private static function seed(): void
    {
        self::$pdo->exec('DELETE FROM quiz_takers WHERE quiz_id = ' . self::SEED_QUIZ_ID);
        self::$pdo->exec('DELETE FROM quizes WHERE id = ' . self::SEED_QUIZ_ID);

        self::$pdo->prepare(
            'INSERT INTO quizes (id, quiz_name, total_questions, display_questions, time_allotted, set_default)
             VALUES (:id, :name, 4, 4, 30, 0)'
        )->execute(['id' => self::SEED_QUIZ_ID, 'name' => self::SEED_QUIZ_NAME]);

        // taker i: marks = 4 - (i % 5)  => marks pattern 4,3,2,1,0,4,...
        // duration = i+1 so ties break deterministically
        $stmt = self::$pdo->prepare(
            "INSERT INTO quiz_takers (username, percentage, date_time, quiz_id, duration, marks)
             VALUES (:username, '50', '2026-03-04 05:06:07', :quizId, :duration, :marks)"
        );
        for ($i = 1; $i <= self::TAKER_COUNT; $i++) {
            $stmt->execute([
                'username' => sprintf('csv_taker_%02d', $i),
                'quizId' => self::SEED_QUIZ_ID,
                'duration' => $i,
                'marks' => 4 - ($i % 5),
            ]);
        }
    }

    public function testUnauthenticatedRequestRedirectsToLogin(): void
    {
        [$status, $redirect] = self::request(
            'GET',
            'export_results.php?quiz=' . self::SEED_QUIZ_ID . '&scope=all',
            null,
            self::$anonJar
        );
        $this->assertSame(302, $status);
        $this->assertStringContainsString('login.php', (string) $redirect);
    }

    public function testCsvHeaderRowShapeAndFilename(): void
    {
        [$status, , $body, $headers] = self::request(
            'GET',
            'export_results.php?quiz=' . self::SEED_QUIZ_ID . '&scope=top',
            null,
            self::$authJar
        );

        $this->assertSame(200, $status);
        $this->assertStringContainsString('text/csv', (string) ($headers['content-type'] ?? ''));
        $this->assertMatchesRegularExpression(
            '#^attachment; filename="results-csv-export-quiz-\d{8}\.csv"$#',
            (string) ($headers['content-disposition'] ?? '')
        );

        $lines = explode("\n", rtrim((string) $body, "\r\n"));
        $this->assertSame('username,marks,percentage,date_time,duration', $lines[0]);

        // top scope caps at 20 rows + header
        $this->assertCount(21, $lines);
    }

    public function testTopScopeOrdersByMarksDescDurationAscAndComputesPercentage(): void
    {
        [, , $body] = self::request(
            'GET',
            'export_results.php?quiz=' . self::SEED_QUIZ_ID . '&scope=top',
            null,
            self::$authJar
        );

        $rows = $this->parseRows((string) $body);

        // expected order: marks desc, duration asc among the 23 seeded takers
        $expected = [];
        for ($i = 1; $i <= self::TAKER_COUNT; $i++) {
            $expected[] = [
                'username' => sprintf('csv_taker_%02d', $i),
                'marks' => (string) (4 - ($i % 5)),
                'duration' => $i,
            ];
        }
        usort($expected, static fn (array $a, array $b) => [(int) $b['marks'], $a['duration']] <=> [(int) $a['marks'], $b['duration']]);
        $expected = array_slice($expected, 0, 20);

        $this->assertCount(20, $rows);
        foreach ($rows as $idx => $row) {
            $this->assertSame($expected[$idx]['username'], $row[0], "row $idx username/order");
            $this->assertSame($expected[$idx]['marks'], $row[1], "row $idx marks");

            $expectedPct = round(((int) $expected[$idx]['marks'] / 4) * 100, 2);
            $this->assertSame((string) $expectedPct, $row[2], "row $idx percentage");
            $this->assertSame('2026-03-04 05:06:07', $row[3], "row $idx date_time");
            $this->assertSame((string) $expected[$idx]['duration'], $row[4], "row $idx duration");
        }
    }

    public function testAllScopeReturnsEverySeededTaker(): void
    {
        [, , $body] = self::request(
            'GET',
            'export_results.php?quiz=' . self::SEED_QUIZ_ID . '&scope=all',
            null,
            self::$authJar
        );

        $this->assertCount(self::TAKER_COUNT + 1, explode("\n", rtrim((string) $body, "\r\n")));
    }

    public function testUnknownQuizIsRejectedWithoutCsv(): void
    {
        [$status] = self::request('GET', 'export_results.php?quiz=99999999', null, self::$authJar);
        $this->assertSame(404, $status);

        [$status] = self::request('GET', 'export_results.php', null, self::$authJar);
        $this->assertSame(404, $status);
    }

    /**
     * Minimal CSV parse: our seeded data never contains commas/quotes, so a
     * plain split keeps the assertions readable.
     *
     * @return list<list<string>>
     */
    private function parseRows(string $csv): array
    {
        return array_map(
            static fn (string $line): array => str_getcsv($line),
            array_slice(explode("\n", rtrim($csv, "\r\n")), 1)
        );
    }

    /**
     * @param array<string, string>|null $post
     * @return array{0: int, 1: string, 2: string, 3: array<string, string>} [status, redirect, body, headers]
     */
    private static function request(string $method, string $path, ?array $post = null, ?string $jar = null): array
    {
        $args = [
            'curl', '-s', '--max-time', '10', '--max-redirs', '0',
            '-A', 'ExportResultsTest/1.0',
            '-b', $jar ?? self::$anonJar, '-c', $jar ?? self::$anonJar,
            '-D', '-', '-w', "\n%{http_code}",
            '-X', $method,
            self::$base . '/' . $path,
        ];
        if ($post !== null) {
            array_push($args, '--data', http_build_query($post));
        }

        $descriptors = [1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']];
        $proc = proc_open($args, $descriptors, $pipes);
        self::assertIsResource($proc);
        $out = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        proc_close($proc);

        $split = strpos($out, "\r\n\r\n");
        $headerBlock = $split === false ? $out : substr($out, 0, $split);
        $rest = $split === false ? '' : substr($out, $split + 4);

        $status = 0;
        if (preg_match('#HTTP/\S+\s+(\d{3})#', $headerBlock, $m) === 1) {
            $status = (int) $m[1];
        }

        $redirect = '';
        $headers = [];
        foreach (explode("\r\n", $headerBlock) as $line) {
            if (stripos($line, 'location:') === 0) {
                $redirect = trim((string) substr($line, 9));
            }
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
        }

        $body = (string) preg_replace('/\n\d{3}\n?$/', '', $rest);

        return [$status, $redirect, $body, $headers];
    }
}
