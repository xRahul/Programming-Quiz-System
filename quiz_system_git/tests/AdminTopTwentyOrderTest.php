<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Admin Result(Top 20) / Result(All) ordering proof (T8 gap test).
 *
 * ExportResultsTest proves marks-desc/duration-asc for the CSV export
 * surface. The admin AJAX tables go through a different code path
 * (admin.php usersQuiz/usersAll -> render_results_table), so this suite
 * seeds 22 controlled takers on a dedicated scratch quiz and requires:
 * exact ordering, the LIMIT-20 cap on usersQuiz, and no cap on usersAll.
 */
final class AdminTopTwentyOrderTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8113;
    private const QUIZ_NAME = 'P8 Top20 Ordering Quiz';
    private const TAKER_COUNT = 22;

    private static string $base;
    private static string $authJar;
    /** @var resource|null */
    private static $server = null;
    private static ?PDO $pdo = null;
    private static int $quizId = 0;
    /** @var list<array{username:string,marks:int,duration:int}> expected ranking, best first */
    private static array $seeded = [];

    public static function setUpBeforeClass(): void
    {
        require_live_db_credentials();
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$authJar = tempnam(sys_get_temp_dir(), 'p8top_jar_');

        $docroot = dirname(__DIR__);
        $cmd = sprintf(
            'exec %s -S %s:%d -t %s',
            escapeshellarg(PHP_BINARY),
            self::HOST,
            self::PORT,
            escapeshellarg($docroot)
        );
        self::$server = proc_open($cmd, [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
        self::assertIsResource(self::$server, "Failed to start server: $cmd");

        $ready = false;
        for ($i = 0; $i < 50; $i++) {
            [, , $probe] = self::request('GET', 'login.php');
            if ($probe !== '') {
                $ready = true;
                break;
            }
            usleep(100_000);
        }
        self::assertTrue($ready, 'PHP built-in server did not become ready on ' . self::$base);

        require_once dirname(__DIR__) . '/lib/config.php';
        self::$pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );

        [$status] = self::request('POST', 'login_check.php', ['login' => 'admin', 'password' => '12345']);
        self::assertSame(302, $status, 'admin login should redirect');

        // Dedicated quiz so seed takers can never pollute the expected order.
        self::$pdo->prepare(
            "INSERT INTO quizes (quiz_name, display_questions, time_allotted, set_default)
             VALUES (:name, 5, 10, 0)"
        )->execute(['name' => self::QUIZ_NAME]);
        self::$quizId = (int) self::$pdo->lastInsertId();

        $insert = self::$pdo->prepare(
            "INSERT INTO quiz_takers (username, percentage, date_time, quiz_id, duration, marks)
             VALUES (:username, '0', '2026-03-04 05:06:07', :quizId, :duration, :marks)"
        );
        for ($i = 1; $i <= self::TAKER_COUNT; $i++) {
            // marks cycles (forces ties), duration descends (order must NOT
            // follow insertion or duration alone) -- only the composite
            // marks-desc, duration-asc sort produces the expected ranking.
            $taker = [
                'username' => sprintf('p8ord_%02d', $i),
                'marks' => $i % 4,
                'duration' => 1000 - $i,
            ];
            $insert->execute([
                'username' => $taker['username'],
                'quizId' => self::$quizId,
                'duration' => $taker['duration'],
                'marks' => $taker['marks'],
            ]);
            self::$seeded[] = $taker;
        }

        // Mirror of the SQL ORDER BY marks desc, duration asc over the data
        // we just seeded; every row assertion below compares against this.
        usort(self::$seeded, static fn (array $a, array $b): int
            => [$b['marks'], $a['duration']] <=> [$a['marks'], $b['duration']]);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$pdo !== null && self::$quizId !== 0) {
            self::$pdo->prepare('DELETE FROM quiz_takers WHERE quiz_id = :id')->execute(['id' => self::$quizId]);
            self::$pdo->prepare('DELETE FROM quizes WHERE id = :id')->execute(['id' => self::$quizId]);
        }
        if (is_resource(self::$server)) {
            $status = proc_get_status(self::$server);
            if (!empty($status['pid'])) {
                exec('kill -9 ' . (int) $status['pid'] . ' >/dev/null 2>&1');
            }
            proc_close(self::$server);
        }
        @unlink(self::$authJar);
        self::$pdo = null;
    }

    public function testUsersQuizCapsAtTwentyInCompositeOrder(): void
    {
        [, , $body] = self::request('POST', 'admin.php', [
            'usersQuiz' => self::QUIZ_NAME,
            'csrf_token' => self::token(),
        ]);

        $rows = self::parseRows((string) $body);
        $this->assertCount(20, $rows, 'Result(Top 20) must cap at 20 rows');

        foreach ($rows as $idx => $row) {
            $expected = self::$seeded[$idx];
            $this->assertSame((string) ($idx + 1), $row['rank'], "rank at row $idx");
            $this->assertSame($expected['username'], $row['username'], "username/order at row $idx");
            $this->assertSame((string) $expected['marks'], $row['marks'], "marks at row $idx");
            $this->assertSame((string) $expected['duration'], $row['duration'], "duration at row $idx");
        }

        // The cap must keep the BEST takers, never merely the first inserted.
        $this->assertSame(3, (int) $rows[0]['marks'], 'top rank must carry the highest mark present');
        $this->assertSame('p8ord_19', $rows[0]['username'], 'duration asc must break the marks tie');
        $this->assertNotSame('p8ord_01', $rows[0]['username'], 'insertion order must not decide the top rank');
    }

    public function testUsersAllReturnsEveryTakerSameOrder(): void
    {
        [, , $body] = self::request('POST', 'admin.php', [
            'usersAll' => self::QUIZ_NAME,
            'csrf_token' => self::token(),
        ]);

        $rows = self::parseRows((string) $body);
        $this->assertCount(self::TAKER_COUNT, $rows, 'Result(All) must return every taker uncapped');

        foreach ($rows as $idx => $row) {
            $this->assertSame(self::$seeded[$idx]['username'], $row['username'], "username/order at row $idx");
        }
        $worst = self::$seeded[self::TAKER_COUNT - 1];
        $this->assertSame('p8ord_04', $worst['username'], 'mirrored order sanity: zero-mark earliest taker is last');
        $this->assertSame($worst['username'], $rows[self::TAKER_COUNT - 1]['username'], 'worst taker ranks last');
    }

    // ---------- helpers ----------
    private static function parseRows(string $body): array
    {
        preg_match_all(
            '/<tr align="center">\s*<td>(\d+)<\/td>\s*<td>([^<]*)<\/td>\s*<td>([^<]*)<\/td>'
            . '\s*<td>([^<]*)<\/td>\s*<td>([^<]*)<\/td>\s*<td>([^<]*)<\/td>/',
            $body,
            $m,
            PREG_SET_ORDER
        );

        $rows = [];
        foreach ($m as $set) {
            $rows[] = [
                'rank' => $set[1],
                'username' => $set[2],
                'marks' => $set[3],
                'percentage' => $set[4],
                'duration' => $set[5],
                'date_time' => $set[6],
            ];
        }

        return $rows;
    }

    private static function token(): string
    {
        [, , $body] = self::request('GET', 'admin.php');
        self::assertSame(1, preg_match('/name="csrf_token" value="([0-9a-f]+)"/', $body, $m), 'need csrf token');

        return $m[1];
    }

    /**
     * @param array<string, string> $post
     * @return array{0: int, 1: string, 2: string} [http status, redirect URL ('' if none), body]
     */
    private static function request(string $method, string $path, array $post = []): array
    {
        $args = [
            'curl', '-s', '--max-redirs', '0',
            '-X', $method,
            '-A', 'AdminTopTwentyOrderTest/1.0',
            '-b', self::$authJar,
            '-c', self::$authJar,
            '-w', "\n%{http_code} %{redirect_url}",
            self::$base . '/' . $path,
        ];
        if ($post !== []) {
            array_splice($args, count($args) - 1, 0, ['--data', http_build_query($post)]);
        }

        $descriptors = [1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']];
        $proc = proc_open($args, $descriptors, $pipes);
        self::assertIsResource($proc, 'failed to spawn curl');
        $stdout = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        proc_close($proc);

        $lines = explode("\n", rtrim($stdout, "\n"));
        $meta = array_pop($lines);
        $body = implode("\n", $lines);
        $parts = explode(' ', trim($meta), 2);

        return [(int) ($parts[0] ?? 0), trim($parts[1] ?? ''), $body];
    }
}
