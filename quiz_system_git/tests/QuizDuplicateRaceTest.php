<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Once-per-quiz enforcement, race half (FINAL-B finding 6).
 *
 * The legacy guard is SELECT-then-INSERT. Two near-simultaneous first-time
 * starts of the same (username, quiz_id) can both pass the SELECT; the
 * unique key uq_takers_user_quiz then rejects the second INSERT, which used
 * to escape as an UNCAUGHT PDOException -> blank HTTP 500. The handler now
 * maps SQLSTATE 23000 to the exact same friendly already-attempted redirect
 * the rowCount branch uses.
 *
 * This suite fires a burst of concurrent first-time POSTs against a
 * multi-worker php -S instance on a SCRATCH database and asserts the
 * outcome contract: no 500s, exactly one rendered quiz, every loser
 * redirected with the byte-identical legacy message, exactly one taker row.
 */
final class QuizDuplicateRaceTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8105;
    private const BURST = 12;

    private static string $base;
    private static string $scratchDb;
    /** @var resource|null */
    private static $server = null;
    private static ?PDO $controller = null;

    public static function setUpBeforeClass(): void
    {
        require_live_db_credentials();
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$scratchDb = 'debug_test_finalb_race_' . bin2hex(random_bytes(4));

        exec('command -v mysql 2>/dev/null', $out, $cliCode);
        if ($cliCode !== 0) {
            self::markTestSkipped('mysql CLI unavailable; cannot provision a scratch database');
        }

        try {
            // Elevated controller: explicit admin creds, else CI's app user.
            self::$controller = TestEnv::adminPdo();
            if (self::$controller === null) {
                self::markTestSkipped('scratch DB control unavailable (no working admin credentials)');
            }
        } catch (PDOException $e) {
            self::markTestSkipped('scratch DB control unavailable: ' . $e->getMessage());
        }

        self::$controller->exec('DROP DATABASE IF EXISTS `' . self::$scratchDb . '`');
        self::$controller->exec(
            'CREATE DATABASE `' . self::$scratchDb . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        self::$controller->exec("GRANT ALL PRIVILEGES ON `" . self::$scratchDb . "`.* TO '" . TestEnv::grantPrincipal() . "'");

        $dump = dirname(__DIR__) . '/database/debug-v2.sql';
        exec(
            'mysql --default-character-set=utf8mb4' . TestEnv::cliFlags() . ' ' . escapeshellarg(self::$scratchDb)
            . ' < ' . escapeshellarg($dump) . ' 2>&1',
            $importOut,
            $importCode
        );
        self::assertSame(0, $importCode, 'scratch import failed: ' . implode("\n", $importOut));

        $docroot = dirname(__DIR__);
        $cmd = sprintf(
            'exec %s -S %s:%d -t %s',
            escapeshellarg(PHP_BINARY),
            self::HOST,
            self::PORT,
            escapeshellarg($docroot)
        );
        // Multi-worker built-in server so the burst can genuinely interleave
        // SELECT/INSERT across requests (single worker serializes them).
        $env = getenv();
        $env['DB_NAME'] = self::$scratchDb;
        $env['PHP_CLI_SERVER_WORKERS'] = (string) (self::BURST + 2);
        self::$server = proc_open($cmd, [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes, null, $env);
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
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$controller !== null) {
            try {
                self::$controller->exec('DROP DATABASE IF EXISTS `' . self::$scratchDb . '`');
                self::$controller->exec(
                    "REVOKE ALL PRIVILEGES ON `" . self::$scratchDb . "`.* FROM '" . TestEnv::grantPrincipal() . "'"
                );
            } catch (PDOException $e) {
                fwrite(STDERR, '[QuizDuplicateRaceTest] scratch teardown failed: ' . $e->getMessage() . "\n");
            }
        }
        if (is_resource(self::$server)) {
            $status = proc_get_status(self::$server);
            if (!empty($status['pid'])) {
                exec('kill -9 ' . (int) $status['pid'] . ' >/dev/null 2>&1');
            }
            proc_close(self::$server);
        }
        self::$controller = null;
    }

    public function testConcurrentFirstTimeStartsNeverFiveHundred(): void
    {
        $quiz = self::$controller->query(
            "SELECT id, quiz_name FROM `" . self::$scratchDb . "`.quizes WHERE set_default = 1"
        )->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($quiz, 'seed must contain a default quiz');

        $rollno = 'race_' . bin2hex(random_bytes(4));

        try {
            [$codes, $redirects] = self::burst($rollno);

            foreach ($codes as $i => $code) {
                self::assertNotSame('500', (string) $code, "request $i must not die as a blank 500");
            }

            $rendered = array_keys($codes, '200', true);
            self::assertCount(
                1,
                $rendered,
                'exactly one request may render the quiz page, got codes: ' . implode(',', $codes)
            );

            $expectedMsg = 'Sorry, but ' . $rollno . ', has already attempted the quiz, '
                . $quiz['quiz_name'] . '!';
            $losers = 0;
            foreach ($redirects as $i => $redirect) {
                if ((string) $codes[$i] !== '302') {
                    continue;
                }
                $losers++;
                self::assertStringContainsString('index.php?user_msg=', (string) $redirect, "request $i redirect");
                $query = (string) parse_url((string) $redirect, PHP_URL_QUERY);
                $msg = urldecode((string) substr($query, strlen('user_msg=')));
                self::assertSame(
                    $expectedMsg,
                    $msg,
                    "loser $i must carry the byte-identical legacy message"
                );
            }
            self::assertSame(self::BURST - 1, $losers, 'every non-winner must be redirected as already-attempted');

            $rows = (int) self::countTakers($rollno);
            self::assertSame(1, $rows, 'the burst must leave exactly one taker row');
        } finally {
            self::$controller->prepare("DELETE FROM `" . self::$scratchDb . "`.quiz_takers WHERE username = ?")
                ->execute([$rollno]);
        }
    }

    private static function countTakers(string $rollno): int
    {
        $stmt = self::$controller->prepare(
            "SELECT COUNT(*) FROM `" . self::$scratchDb . "`.quiz_takers WHERE username = ?"
        );
        $stmt->execute([$rollno]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Fire BURST concurrent first-time POSTs via one backgrounded curl per
     * request, then collect "%{http_code} %{redirect_url}" lines.
     *
     * @return array{0: list<string>, 1: list<string>} [status codes, redirect urls]
     */
    private static function burst(string $rollno): array
    {
        $dir = sys_get_temp_dir() . '/race_' . bin2hex(random_bytes(4));
        self::assertTrue(mkdir($dir), 'burst temp dir');

        $parts = [];
        for ($i = 0; $i < self::BURST; $i++) {
            $out = escapeshellarg(sprintf('%s/out_%02d.txt', $dir, $i));
            $parts[] = sprintf(
                'curl -s --max-redirs 0 -o /dev/null -w "%%{http_code} %%{redirect_url}\n"'
                . ' -X POST --data rollno=%s %s >> %s',
                escapeshellarg($rollno),
                escapeshellarg(self::$base . '/quiz.php'),
                $out
            );
        }

        $cmd = implode(' & ', $parts) . ' & wait';
        exec($cmd);

        $codes = [];
        $redirects = [];
        for ($i = 0; $i < self::BURST; $i++) {
            $raw = trim((string) file_get_contents(sprintf('%s/out_%02d.txt', $dir, $i)));
            [$code, $redirect] = array_pad(preg_split('/\s+/', $raw, 2), 2, '');
            $codes[] = (string) $code;
            $redirects[] = (string) $redirect;
        }
        foreach (glob($dir . '/out_*.txt') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($dir);

        return [$codes, $redirects];
    }

    private static function request(string $method, string $path): array
    {
        $args = [
            'curl', '-s', '--max-redirs', '0',
            '-X', $method,
            '-A', 'QuizDuplicateRaceTest/1.0',
            '-w', "\n%{http_code} %{redirect_url}",
            self::$base . '/' . $path,
        ];

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
