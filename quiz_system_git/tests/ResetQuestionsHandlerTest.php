<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * "Delete all Questions" (admin.php reset handler) FK-survival proof.
 *
 * The reset handler used to run bare TRUNCATEs in parent-before-child
 * order (questions, answers). Under the migration-004 FK schema InnoDB
 * refuses TRUNCATE on any referenced table, so "TRUNCATE TABLE questions"
 * aborted with error 1701 and the request died as a blank HTTP/1.0 500 --
 * same defect shape resetTables had before c8f765d. This suite posts the
 * real authed+CSRF request against a SCRATCH database (the handler
 * TRUNCATEs, so the shared debug DB must never see it) and proves the full
 * contract end-to-end: old order provably fails, new order returns 200 with
 * the byte-identical legacy message, questions/answers are gone, quiz rows
 * survive with total_questions zeroed, and the action is audited.
 */
final class ResetQuestionsHandlerTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8106;

    private static string $base;
    private static string $scratchDb;
    private static string $authJar;
    /** @var resource|null */
    private static $server = null;
    private static ?PDO $controller = null;

    public static function setUpBeforeClass(): void
    {
        require_live_db_credentials();
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$scratchDb = 'debug_test_finalb_resetq_' . bin2hex(random_bytes(4));
        self::$authJar = tempnam(sys_get_temp_dir(), 'resetq_jar_');

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
            self::markTestSkipped('scratch DB control unavailable (need unix_socket admin user): ' . $e->getMessage());
        }

        self::$controller->exec('DROP DATABASE IF EXISTS `' . self::$scratchDb . '`');
        self::$controller->exec(
            'CREATE DATABASE `' . self::$scratchDb . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        self::$controller->exec("GRANT ALL PRIVILEGES ON `" . self::$scratchDb . "`.* TO ' . TestEnv::grantPrincipal() . '");

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
        $env = getenv();
        $env['DB_NAME'] = self::$scratchDb;
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

        [$status] = self::request('POST', 'login_check.php', ['login' => 'admin', 'password' => '12345']);
        self::assertSame(302, $status, 'admin login should redirect on the scratch database');

        // Non-default state first, so the wipe + counter-zero has something
        // to undo and the counter UPDATE is observable.
        self::$controller->exec(
            "INSERT INTO `" . self::$scratchDb . "`.questions (quiz_id, question, code, code_type, type)
             VALUES (1, 'resetq probe question', '', '', 'tf')"
        );
        self::$controller->exec(
            "INSERT INTO `" . self::$scratchDb . "`.answers (quiz_id, question_id, answer, correct)
             SELECT 1, id, 'True', '1' FROM `" . self::$scratchDb . "`.questions
             WHERE question = 'resetq probe question'"
        );
        self::$controller->exec("UPDATE `" . self::$scratchDb . "`.quizes SET total_questions = total_questions + 7");
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$controller !== null) {
            try {
                self::$controller->exec('DROP DATABASE IF EXISTS `' . self::$scratchDb . '`');
                self::$controller->exec(
                    "REVOKE ALL PRIVILEGES ON `" . self::$scratchDb . "`.* FROM ' . TestEnv::grantPrincipal() . '"
                );
            } catch (PDOException $e) {
                fwrite(STDERR, '[ResetQuestionsHandlerTest] scratch teardown failed: ' . $e->getMessage() . "\n");
            }
        }
        if (is_resource(self::$server)) {
            $status = proc_get_status(self::$server);
            if (!empty($status['pid'])) {
                exec('kill -9 ' . (int) $status['pid'] . ' >/dev/null 2>&1');
            }
            proc_close(self::$server);
        }
        @unlink(self::$authJar);
        self::$controller = null;
    }

    public function testResetQuestionsSurvivesFkSchemaAndZeroesCounters(): void
    {
        // Prove the OLD parent-first order really fails under this schema:
        // bare TRUNCATE on a referenced parent aborts even though children
        // exist/absent (MariaDB error 1701). This is what blanked the
        // handler out as a 500 before the fix.
        try {
            ResetQuestionsHandlerTest::db()->exec('TRUNCATE TABLE `'
                . ResetQuestionsHandlerTest::$scratchDb . '`.`questions`');
            $this->fail('parent-first TRUNCATE unexpectedly succeeded; schema has no FK pressure to guard against');
        } catch (PDOException $e) {
            // old order aborts as expected -- keep the evidence visible
            self::assertStringContainsString('1701', $e->getCode() . ' ' . $e->getMessage());
        }

        [$status, , $body] = self::request('POST', 'admin.php', [
            'reset' => 'yes',
            'csrf_token' => self::token(),
        ]);

        $this->assertSame(200, $status, 'reset handler must answer 200 once FK-safe');
        $this->assertStringContainsString(
            'Thanks! The all quizes have now been reset back to 0 questions.',
            $body,
            'legacy success message must stay byte-identical'
        );

        $count = static fn (string $sql): int => (int) ResetQuestionsHandlerTest::db()
            ->query(str_replace('%DB%', '`' . ResetQuestionsHandlerTest::$scratchDb . '`', $sql))
            ->fetchColumn();

        $this->assertSame(0, $count('SELECT COUNT(*) FROM %DB%.questions'), 'questions must be truncated');
        $this->assertSame(0, $count('SELECT COUNT(*) FROM %DB%.answers'), 'answers must be truncated');

        // Quiz rows survive the wipe (handler never touches quizes) ...
        $quizCount = $count('SELECT COUNT(*) FROM %DB%.quizes');
        $this->assertGreaterThan(0, $quizCount, 'seed quizzes must survive the questions-only reset');
        // ... but their counters are zeroed by the handler's UPDATE.
        $this->assertSame(
            0,
            $count('SELECT COALESCE(SUM(total_questions), -1) FROM %DB%.quizes'),
            'every quiz total_questions must be zeroed'
        );

        $audit = ResetQuestionsHandlerTest::db()
            ->query("SELECT actor, action FROM `" . ResetQuestionsHandlerTest::$scratchDb . "`.audit_log
                     WHERE action = 'reset_questions'")
            ->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($audit, 'reset_questions must be audited');
        $this->assertSame('admin', $audit['actor']);

        // FOREIGN_KEY_CHECKS is session-scoped: the connection that served
        // the request is gone, but a fresh insert through a NEW connection
        // proves no global flag leaked anywhere.
        try {
            ResetQuestionsHandlerTest::db()->exec(
                "INSERT INTO `" . ResetQuestionsHandlerTest::$scratchDb . "`.questions (quiz_id, question, code, code_type, type)
                 VALUES (999999, 'fk still enforced probe', '', '', 'tf')"
            );
            $this->fail('FK enforcement must still hold after the reset (questions.quiz_id -> quizes.id)');
        } catch (PDOException $e) {
            $this->assertStringContainsString('1452', $e->getCode() . ' ' . $e->getMessage(), 'expected FK violation 1452');
        }
    }

    // ---------- helpers ----------

    private static function db(): PDO
    {
        $c = self::$controller;
        self::assertNotNull($c);

        return $c;
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
            '-A', 'ResetQuestionsHandlerTest/1.0',
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
