<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Reset All Tables reseed proof (T8 gap test).
 *
 * admin.php's resetTables handler truncates every table and restores a
 * single default admin. The readme promises that account is (admin, 12345);
 * this suite proves it against a SCRATCH database (the handler TRUNCATEs,
 * so the shared debug DB must never see this request): after the POST, the
 * admins table holds exactly one bcrypt-verifiable admin/'12345' row, all
 * content tables are empty, and an audit_log row records the action.
 */
final class ResetTablesReseedTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8114;

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
        self::$scratchDb = 'debug_test_p8_reset_' . bin2hex(random_bytes(4));
        self::$authJar = tempnam(sys_get_temp_dir(), 'p8reset_jar_');

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

        // Non-default state first, so the reseed has something to undo.
        self::$controller->exec(
            "INSERT INTO `" . self::$scratchDb . "`.admins (username, password)
             VALUES ('p8_extra_admin', '" . password_hash('extra-pass', PASSWORD_DEFAULT) . "')"
        );
        self::$controller->exec(
            "INSERT INTO `" . self::$scratchDb . "`.quizes (quiz_name, display_questions, time_allotted, set_default)
             VALUES ('P8 Doomed Quiz', 5, 10, 0)"
        );
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
                fwrite(STDERR, '[ResetTablesReseedTest] scratch teardown failed: ' . $e->getMessage() . "\n");
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

    public function testResetTablesTruncatesAndReseedsBcryptAdmin(): void
    {
        [$status, , $body] = self::request('POST', 'admin.php', [
            'resetTables' => 'yes',
            'csrf_token' => self::token(),
        ]);

        // Regression guard for the FK half-reset defect (T8, fixed in fix
        // round 1): the handler used to run bare TRUNCATEs in
        // parent-before-child order, so "TRUNCATE TABLE questions" aborted
        // with MariaDB error 1701 -- blank HTTP/1.0 500 AFTER admins was
        // truncated but BEFORE the default-admin reseed, leaving the DB
        // locked out. The handler now wipes child-first inside a lifted
        // FK_CHECKS window; this test proves the full end-to-end contract.
        $this->assertSame(200, $status, 'resetTables must answer 200');
        $this->assertStringContainsString('your database is now reset', $body);

        $count = static fn (string $table): int => (int) ResetTablesReseedTest::db()
            ->query("SELECT COUNT(*) FROM `" . ResetTablesReseedTest::$scratchDb . "`.$table")
            ->fetchColumn();

        $this->assertSame(1, $count('admins'), 'exactly one admin must survive the reset');
        $this->assertSame(0, $count('quizes'), 'quizes must be truncated');
        $this->assertSame(0, $count('questions'), 'questions must be truncated');
        $this->assertSame(0, $count('answers'), 'answers must be truncated');
        $this->assertSame(0, $count('quiz_takers'), 'quiz_takers must be truncated');

        $row = ResetTablesReseedTest::db()
            ->query("SELECT username, password FROM `" . ResetTablesReseedTest::$scratchDb . "`.admins")
            ->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('admin', $row['username'], 'reseeded admin must be named admin');
        $this->assertTrue(
            password_verify('12345', (string) $row['password']),
            'reseeded admin password must bcrypt-verify as 12345, got hash: ' . $row['password']
        );

        $audit = ResetTablesReseedTest::db()
            ->query("SELECT actor, action FROM `" . ResetTablesReseedTest::$scratchDb . "`.audit_log
                     WHERE action = 'reset_tables'")
            ->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($audit, 'reset_tables must be audited');
        $this->assertSame('admin', $audit['actor']);
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
            '-A', 'ResetTablesReseedTest/1.0',
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
