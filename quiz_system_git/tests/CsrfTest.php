<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * CSRF enforcement: state-changing endpoints must reject POSTs that do not
 * carry the current session's token (403, zero mutations) and still accept
 * well-formed admin requests that do.
 */
final class CsrfTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8092;
    private const ADMIN_USER = 'admin';
    private const ADMIN_PASS = '12345';

    private static string $base;
    private static string $authJar;
    /** @var resource|null */
    private static $server = null;
    private static ?PDO $pdo = null;

    public static function setUpBeforeClass(): void
    {
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$authJar = tempnam(sys_get_temp_dir(), 'csrf_auth_');

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
        self::assertIsResource(self::$server, "Failed to start server: $cmd");

        $ready = false;
        for ($i = 0; $i < 50; $i++) {
            if ((int) proc_get_status(self::$server)['running'] !== 1) {
                break;
            }
            [$status] = self::request('GET', 'login.php');
            if ($status === 200) {
                $ready = true;
                break;
            }
            usleep(100_000);
        }
        self::assertTrue($ready, 'PHP built-in server did not become ready on ' . self::$base);

        // Independent handle on purpose: see AuthGuardTest::setUpBeforeClass().
        require_once dirname(__DIR__) . '/lib/config.php';
        self::$pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        @unlink(self::$authJar);
        self::$pdo = null;
    }

    public function testRegisterWithoutTokenIsRejectedWith403AndNoMutation(): void
    {
        self::login();
        $newUser = 'csrftest_' . bin2hex(random_bytes(4));
        [$status] = self::post('register.php', [
            'login' => $newUser,
            'password' => 'csrf-pass-123',
        ]);

        $this->assertSame(403, $status, 'register.php must reject a POST without a CSRF token');
        $this->assertSame(0, self::adminCount($newUser), 'register.php must not create an admin without a token');
    }

    public function testRegisterWithWrongTokenIsRejectedWith403(): void
    {
        self::login();
        $newUser = 'csrftest_' . bin2hex(random_bytes(4));
        [$status] = self::post('register.php', [
            'login' => $newUser,
            'password' => 'csrf-pass-123',
            'csrf_token' => str_repeat('f', 64),
        ]);

        $this->assertSame(403, $status, 'register.php must reject a forged CSRF token');
        $this->assertSame(0, self::adminCount($newUser), 'register.php must not create an admin with a wrong token');
    }

    public function testRegisterWithValidTokenStillSucceeds(): void
    {
        self::login();
        $token = self::sessionToken();
        $this->assertNotSame('', $token, 'admin page must render a csrf_token hidden input');

        $newUser = 'csrftest_' . bin2hex(random_bytes(4));
        [$status, $redirect] = self::post('register.php', [
            'login' => $newUser,
            'password' => 'csrf-pass-123',
            'csrf_token' => $token,
        ]);

        try {
            $this->assertSame(302, $status, 'register.php should redirect after creating user');
            $this->assertStringContainsString('admin.php', (string) $redirect, 'valid-token register must not be rejected');
            $this->assertSame(1, self::adminCount($newUser), 'register.php must create exactly one admin row');
        } finally {
            $stmt = self::$pdo->prepare('DELETE FROM admins WHERE username = :username');
            $stmt->execute(['username' => $newUser]);
        }
    }

    public function testAdminClearResultWithoutTokenIsRejectedWith403(): void
    {
        self::login();
        [$status] = self::post('admin.php', ['clearResult' => '999999']);

        $this->assertSame(403, $status, 'admin.php destructive handlers must require a CSRF token');
    }

    private static function login(): void
    {
        [$status, $redirect] = self::post('login_check.php', [
            'login' => self::ADMIN_USER,
            'password' => self::ADMIN_PASS,
        ]);
        self::assertSame(302, $status, 'admin login should redirect');
        self::assertStringContainsString('admin.php', (string) $redirect, 'admin login should succeed');
    }

    /**
     * Fetches admin.php as the logged-in admin and extracts the rendered
     * session token from its hidden csrf_token inputs.
     */
    private static function sessionToken(): string
    {
        [, , $body] = self::request('GET', 'admin.php');
        if (preg_match('/name="csrf_token" value="([0-9a-f]+)"/', $body, $m) === 1) {
            return $m[1];
        }

        return '';
    }

    private static function adminCount(string $username): int
    {
        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM admins WHERE username = :username');
        $stmt->execute(['username' => $username]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string, string> $post
     * @return array{0: int, 1: string} [http status, redirect URL ('' if none)]
     */
    private static function post(string $path, array $post): array
    {
        [$status, $redirect] = self::request('POST', $path, $post);

        return [$status, $redirect];
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
            '-A', 'CsrfTest/1.0',
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
        self::assertIsResource($proc);
        $out = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        proc_close($proc);

        $nl = strrpos($out, "\n");
        self::assertNotFalse($nl, "curl produced no status line for $method $path");
        $parts = explode(' ', trim(substr($out, $nl + 1)), 2);

        return [(int) ($parts[0] ?? 0), trim($parts[1] ?? ''), rtrim(substr($out, 0, $nl))];
    }
}
