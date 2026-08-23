<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * T5.5 admin-assisted password reset: auth gate, minimum-length rule,
 * successful hash rotation, and the audit trail entry.
 */
final class ResetPasswordTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8104;
    private const ADMIN_USER = 'admin';
    private const ADMIN_PASS = '12345';

    private static string $base;
    private static string $anonJar;
    private static string $authJar;
    /** @var resource|null */
    private static $server = null;
    private static ?PDO $pdo = null;
    private static string $targetUser;

    public static function setUpBeforeClass(): void
    {
        require_live_db_credentials();
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$anonJar = tempnam(sys_get_temp_dir(), 'rp_anon_');
        self::$authJar = tempnam(sys_get_temp_dir(), 'rp_auth_');
        self::$targetUser = 'reset_target_' . bin2hex(random_bytes(4));

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
            [$status] = self::request('GET', 'login.php');
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

        // dedicated victim account so the shared admin/12345 pair stays intact
        self::$pdo->prepare('DELETE FROM admins WHERE username = :username')
            ->execute(['username' => self::$targetUser]);
        self::$pdo->prepare(
            "INSERT INTO admins (username, password) VALUES (:username, :password)"
        )->execute([
            'username' => self::$targetUser,
            'password' => password_hash('original-pass', PASSWORD_DEFAULT),
        ]);

        [, $redirect] = self::request('POST', 'login_check.php', [
            'login' => self::ADMIN_USER,
            'password' => self::ADMIN_PASS,
        ]);
        self::assertStringContainsString('admin.php', (string) $redirect, 'admin login should succeed');
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$pdo !== null) {
            self::$pdo->prepare('DELETE FROM admins WHERE username = :username')
                ->execute(['username' => self::$targetUser]);
            self::$pdo->prepare("DELETE FROM audit_log WHERE action = 'password_reset' AND detail LIKE :marker")
                ->execute(['marker' => '%' . self::$targetUser . '%']);
        }
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        @unlink(self::$anonJar);
        @unlink(self::$authJar);
        self::$pdo = null;
    }

    public function testUnauthenticatedPostRedirectsToLoginWithoutChangingHash(): void
    {
        $before = self::targetHash();

        [$status, $redirect] = self::request(
            'POST',
            'reset_password.php',
            ['username' => self::$targetUser, 'new_password' => 'hacked-pass-9'],
            self::$anonJar
        );

        $this->assertSame(302, $status);
        $this->assertStringContainsString('login.php', (string) $redirect);
        $this->assertSame($before, self::targetHash(), 'unauthenticated request must not touch the hash');
    }

    public function testShortPasswordIsRejectedAndHashStaysUntouched(): void
    {
        $before = self::targetHash();

        [$status, $redirect] = self::request('POST', 'reset_password.php', [
            'username' => self::$targetUser,
            'new_password' => 'abc12',
            'csrf_token' => self::token(),
        ]);

        $this->assertSame(302, $status, 'weak password must bounce back with a message');
        $this->assertStringContainsString('admin.php?msg=', (string) $redirect);
        $this->assertStringContainsString(
            urlencode('at least 6 characters'),
            (string) $redirect,
            'rejection message must explain the length rule'
        );
        $this->assertSame($before, self::targetHash(), 'weak password must leave the hash untouched');

        // rejected attempts are not logged as resets either
        $stmt = self::$pdo->prepare("SELECT COUNT(*) FROM audit_log WHERE action = 'password_reset' AND detail LIKE :marker");
        $stmt->execute(['marker' => '%' . self::$targetUser . '%']);
        $this->assertSame(0, (int) $stmt->fetchColumn(), 'no audit row may exist after only a rejection');
    }

    public function testSuccessfulResetChangesHashAndWritesAuditRow(): void
    {
        $before = self::targetHash();

        [$status, $redirect] = self::request('POST', 'reset_password.php', [
            'username' => self::$targetUser,
            'new_password' => 'fresh-pass-42',
            'csrf_token' => self::token(),
        ]);

        $this->assertSame(302, $status);
        $this->assertStringContainsString('admin.php?msg=', (string) $redirect);

        $after = self::targetHash();
        $this->assertNotSame($before, $after, 'successful reset must rotate the stored hash');
        $this->assertTrue(password_verify('fresh-pass-42', (string) $after), 'stored hash must verify against the new password');

        $stmt = self::$pdo->prepare(
            "SELECT actor, detail FROM audit_log WHERE action = 'password_reset' ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute();
        $row = $stmt->fetch();

        $this->assertNotFalse($row, 'successful reset must write an audit row');
        $this->assertSame(self::ADMIN_USER, $row['actor']);
        $this->assertSame(self::$targetUser, $row['detail']);
    }

    // ---------- helpers ----------

    private static function targetHash(): string
    {
        $stmt = self::$pdo->prepare('SELECT password FROM admins WHERE username = :username');
        $stmt->execute(['username' => self::$targetUser]);

        return (string) $stmt->fetchColumn();
    }

    private static function token(): string
    {
        [, , $body] = self::request('GET', 'admin.php');
        self::assertSame(
            1,
            preg_match('/name="csrf_token" value="([^"]+)"/', $body, $m),
            'admin page must expose a csrf token'
        );

        return (string) $m[1];
    }

    /**
     * @param array<string, string>|null $post
     * @return array{0: int, 1: string, 2: string} [status, redirect, body]
     */
    private static function request(string $method, string $path, ?array $post = null, ?string $jar = null): array
    {
        $args = [
            'curl', '-s', '--max-time', '10', '--max-redirs', '0',
            '-A', 'ResetPasswordTest/1.0',
            '-b', $jar ?? self::$authJar, '-c', $jar ?? self::$authJar,
            '-D', '-',
            '-w', "\n%{http_code}",
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
        $headerBlock = $split === false ? '' : substr($out, 0, $split);
        $rest = $split === false ? $out : substr($out, $split + 4);

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
