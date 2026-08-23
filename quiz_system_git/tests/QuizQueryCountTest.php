<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Query-count regression guard for the quiz page answer loading (T7 phase 7).
 *
 * Renders quiz.php exactly once against a SCRATCH database (DB_NAME env var
 * is honored by lib/config.php, so the php -S worker is spawned pointing at
 * a throwaway copy of database/debug-v2.sql) while MariaDB's general_log
 * captures every statement that single request issues. The assertion: the
 * number of SELECTs hitting the `answers` table during that one render is
 * exactly 1 (the batched fetch_answers_by_question_ids() IN() query). The
 * pre-phase-7 code issued one ORDER BY rand() SELECT per displayed question
 * (9 on seeded data), so this test fails against the old implementation.
 *
 * Privilege note: toggling general_log needs SUPER-level privileges which
 * the app's `quiz` user does not have. The controller therefore connects as
 * the local admin-equivalent unix_socket user (`rahul`, passwordless, ALL
 * PRIVILEGES WITH GRANT OPTION) over PDO host=localhost — no sudo needed.
 * Statements are collected via log_output=TABLE (SELECT from
 * mysql.general_log) rather than a log FILE because the daemon may run
 * under PrivateTmp, making a /tmp path invisible to the test process. If
 * that account or the mysql CLI is unavailable, the test is SKIPPED with an
 * explicit message rather than silently passing (PERFORMANCE_SCHEMA /
 * statement-wrapper fallbacks were considered and rejected as strictly
 * weaker evidence at this scale).
 *
 * general_log state is snapshotted before and restored (OFF + original
 * log_output) in teardown, whatever the test outcome. Rows accumulated in
 * mysql.general_log during the capture window are truncated afterwards; the
 * scratch database is dropped and its per-db grant revoked.
 */
final class QuizQueryCountTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8111;
    private const ROLL = 'qcount_runner';

    private static string $base;
    private static string $scratchDb;
    private static string $origLogOutput = 'FILE';
    private static string $origLogState = '0';
    /** @var resource|null */
    private static $server = null;
    private static ?PDO $controller = null;

    public static function setUpBeforeClass(): void
    {
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$scratchDb = 'debug_test_p7_' . bin2hex(random_bytes(4));

        exec('command -v mysql 2>/dev/null', $out, $cliCode);
        if ($cliCode !== 0) {
            self::markTestSkipped('mysql CLI unavailable; cannot control general_log');
        }

        try {
            // unix_socket auth binds this PDO to the OS user running phpunit.
            self::$controller = new PDO(
                'mysql:host=localhost;charset=utf8mb4',
                'rahul',
                '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            self::$origLogState = (string) self::$controller->query('SELECT @@general_log')->fetchColumn();
            self::$origLogOutput = (string) self::$controller->query('SELECT @@log_output')->fetchColumn();
            self::$controller->exec("SET GLOBAL general_log = 'OFF'");
            self::$controller->exec("SET GLOBAL log_output = 'TABLE'");
        } catch (PDOException | RuntimeException $e) {
            self::markTestSkipped(
                'general_log control unavailable (need SUPER via unix_socket user rahul): ' . $e->getMessage()
            );
        }

        foreach (['DROP DATABASE IF EXISTS `' . self::$scratchDb . '`'] as $sql) {
            self::$controller->exec($sql);
        }
        self::$controller->exec('CREATE DATABASE `' . self::$scratchDb . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        self::$controller->exec(
            "GRANT ALL PRIVILEGES ON `" . self::$scratchDb . "`.* TO 'quiz'@'localhost'"
        );

        $dump = dirname(__DIR__) . '/database/debug-v2.sql';
        exec(
            'mysql --default-character-set=utf8mb4 ' . escapeshellarg(self::$scratchDb)
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
        $env = getenv();
        $env['DB_NAME'] = self::$scratchDb;
        self::$server = proc_open($cmd, [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes, null, $env);
        self::assertIsResource(self::$server, "Failed to start server: $cmd");

        $ready = false;
        for ($i = 0; $i < 50; $i++) {
            if ((int) proc_get_status(self::$server)['running'] !== 1) {
                break;
            }
            [, , $probe] = self::request('GET', 'login.php');
            if ($probe !== '') {
                $ready = true;
                break;
            }
            usleep(100_000);
        }
        self::assertTrue($ready, 'PHP built-in server did not become ready on ' . self::$base);

        self::deleteRunnerRows();
    }

    public static function tearDownAfterClass(): void
    {
        try {
            if (self::$controller !== null) {
                self::$controller->exec("SET GLOBAL general_log = 'OFF'");
                self::$controller->exec('SET GLOBAL log_output = ' . self::$controller->quote(self::$origLogOutput));
                self::$controller->exec(
                    "SET GLOBAL general_log = '" . (self::$origLogState === '1' ? 'ON' : 'OFF') . "'"
                );
                self::$controller->exec('TRUNCATE TABLE mysql.general_log');
            }
        } catch (PDOException $e) {
            fwrite(STDERR, '[QuizQueryCountTest] general_log restore failed: ' . $e->getMessage() . "\n");
        }

        if (self::$controller !== null) {
            try {
                self::$controller->exec('DROP DATABASE IF EXISTS `' . self::$scratchDb . '`');
                self::$controller->exec(
                    "REVOKE ALL PRIVILEGES ON `" . self::$scratchDb . "`.* FROM 'quiz'@'localhost'"
                );
            } catch (PDOException $e) {
                fwrite(STDERR, '[QuizQueryCountTest] scratch teardown failed: ' . $e->getMessage() . "\n");
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

    public function testQuizRenderHitsAnswersTableExactlyOnce(): void
    {
        self::$controller->exec("SET GLOBAL general_log = 'ON'");
        try {
            [$status, , $body] = self::request('POST', 'quiz.php', ['rollno' => self::ROLL]);
            self::assertSame(200, $status, 'quiz.php should render against scratch DB (got ' . $status . ')');
            self::assertStringContainsString('<pre class="question_style">', $body, 'rendered quiz should carry questions');
        } finally {
            self::$controller->exec("SET GLOBAL general_log = 'OFF'");
        }

        $answersSelects = [];
        $logged = self::$controller->query('SELECT argument FROM mysql.general_log')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($logged as $argument) {
            $sql = ltrim((string) $argument);
            if (preg_match('/^SELECT\b/i', $sql) === 1 && preg_match('/\bFROM\s+`?answers`?\b/i', $sql) === 1) {
                $answersSelects[] = $sql;
            }
        }
        self::assertNotEmpty($logged, 'general_log captured no statements; capture window broken');

        self::assertCount(
            1,
            $answersSelects,
            "one batched answers SELECT expected per quiz render, got " . count($answersSelects)
            . ":\n" . implode("\n", $answersSelects)
        );
        self::assertMatchesRegularExpression('/\bIN\s*\(/i', $answersSelects[0], 'batched query must be an IN() fetch');
    }

    private static function deleteRunnerRows(): void
    {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=' . self::$scratchDb . ';charset=utf8mb4',
            'quiz',
            'quizpass',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $pdo->prepare('DELETE FROM quiz_takers WHERE username = ?')->execute([self::ROLL]);
    }

    /**
     * @param array<string, string> $post
     * @return array{0: int, 1: string, 2: string} [status, redirect, body]
     */
    private static function request(string $method, string $path, array $post = []): array
    {
        $jar = tempnam(sys_get_temp_dir(), 'qcount_jar_');
        $args = [
            'curl', '-s', '-A', 'QuizQueryCountTest/1.0',
            '-b', $jar, '-c', $jar,
            '-D', '-', '-w', "\n%{http_code}",
            '-X', $method,
            self::$base . '/' . $path,
        ];
        if ($post !== []) {
            array_push($args, '--data', http_build_query($post));
        }

        $descriptors = [1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']];
        $proc = proc_open($args, $descriptors, $pipes);
        self::assertIsResource($proc);
        $out = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        proc_close($proc);
        @unlink($jar);

        $split = strpos($out, "\r\n\r\n");
        $headerBlock = $split === false ? $out : substr($out, 0, $split);
        $rest = $split === false ? '' : substr($out, $split + 4);

        $status = 0;
        if (preg_match('#HTTP/\S+\s+(\d{3})#', $headerBlock, $m) === 1) {
            $status = (int) $m[1];
        }

        $redirect = '';
        foreach (explode("\r\n", $headerBlock) as $line) {
            if (stripos($line, 'location:') === 0) {
                $redirect = trim((string) substr($line, 9));
            }
        }

        $body = (string) preg_replace('/\n\d{3}\n?$/', '', $rest);

        return [$status, $redirect, $body];
    }
}
