<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Every page-rendering endpoint must emit the shared security headers, and
 * login.php must mint its pre-auth session cookie through the hardened
 * session bootstrap (HttpOnly + SameSite=Lax).
 */
final class SecurityHeadersTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8094;
    private const ADMIN_USER = 'admin';
    private const ADMIN_PASS = '12345';

    private const EXPECTED_HEADERS = [
        'x-content-type-options' => 'nosniff',
        'x-frame-options' => 'DENY',
        'referrer-policy' => 'strict-origin-when-cross-origin',
        'content-security-policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:",
    ];

    /** @var array<string, string> endpoint => cookie jar to fetch it with */
    private const PAGES = [
        'index.php' => 'anon',
        'login.php' => 'anon',
        'quiz.php' => 'anon',
        'result.php' => 'anon',
        'admin.php' => 'auth',
    ];

    private static string $base;
    private static string $anonJar;
    private static string $authJar;
    /** @var resource|null */
    private static $server = null;

    public static function setUpBeforeClass(): void
    {
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$anonJar = tempnam(sys_get_temp_dir(), 'sechdr_anon_');
        self::$authJar = tempnam(sys_get_temp_dir(), 'sechdr_auth_');

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
            [$status] = self::fetch('login.php', self::$anonJar);
            if ($status === 200) {
                $ready = true;
                break;
            }
            usleep(100_000);
        }
        self::assertTrue($ready, 'PHP built-in server did not become ready on ' . self::$base);

        [$status, , $headers] = self::fetch('login_check.php', self::$authJar, [
            'login' => self::ADMIN_USER,
            'password' => self::ADMIN_PASS,
        ]);
        self::assertSame(302, $status, 'admin login should redirect');
        self::assertStringContainsString('admin.php', $headers['location'][0] ?? '', 'admin login should succeed');
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        @unlink(self::$anonJar);
        @unlink(self::$authJar);
    }

    public function testEveryRenderedPageSendsSecurityHeaders(): void
    {
        foreach (self::PAGES as $page => $jarName) {
            $jar = $jarName === 'auth' ? self::$authJar : self::$anonJar;
            [, , $headers] = self::fetch($page, $jar);

            foreach (self::EXPECTED_HEADERS as $name => $value) {
                $this->assertContains(
                    $value,
                    $headers[$name] ?? [],
                    "$page must send $name: $value"
                );
            }
        }
    }

    public function testLoginSessionCookieIsHardened(): void
    {
        // Fresh jar on purpose: a jar already holding a session id gets no
        // new Set-Cookie, and we are asserting exactly what a mint looks like.
        $jar = tempnam(sys_get_temp_dir(), 'sechdr_mint_');
        try {
            [, , $headers] = self::fetch('login.php', $jar);

            $cookies = $headers['set-cookie'] ?? [];
            $sessionCookies = array_values(array_filter(
                $cookies,
                static fn (string $c): bool => str_starts_with($c, 'PHPSESSID=')
            ));

            $this->assertNotEmpty($sessionCookies, 'login.php must start a session');
            foreach ($sessionCookies as $cookie) {
                $this->assertStringContainsString('HttpOnly', $cookie, 'session cookie must be HttpOnly');
                $this->assertStringContainsString('SameSite=Lax', $cookie, 'session cookie must be SameSite=Lax');
            }
        } finally {
            @unlink($jar);
        }
    }

    /**
     * @param array<string, string> $post
     * @return array{0: int, 1: string, 2: array<string, list<string>>} [status, body, headers]
     */
    private static function fetch(string $path, ?string $jar, array $post = []): array
    {
        $args = [
            'curl', '-s', '-D', '-', '-A', 'SecurityHeadersTest/1.0',
            '-b', $jar ?? self::$anonJar, '-c', $jar ?? self::$anonJar,
            '-w', "\n%{http_code}",
            self::$base . '/' . $path,
        ];
        if ($post !== []) {
            array_splice($args, count($args) - 1, 0, ['--data', http_build_query($post)]);
        }

        $descriptors = [1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']];
        $proc = proc_open($args, $descriptors, $pipes);
        self::assertIsResource($proc);
        $raw = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        proc_close($proc);

        $lines = preg_split('/\r?\n/', trim($raw)) ?: [];
        $status = (int) array_pop($lines);

        $headers = [];
        foreach ($lines as $line) {
            $pos = strpos($line, ':');
            if ($pos === false) {
                continue;
            }
            $name = strtolower(trim(substr($line, 0, $pos)));
            $headers[$name][] = trim(substr($line, $pos + 1));
        }

        return [$status, implode("\n", $lines), $headers];
    }
}
