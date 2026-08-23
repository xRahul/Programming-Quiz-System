<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Session lifecycle hardening: a successful login must rotate the session id
 * behind an HttpOnly cookie, malformed logins must bounce to login.php (not
 * admin.php), and logout must fully tear the session down server- and
 * client-side.
 */
final class SessionHardeningTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8093;
    private const ADMIN_USER = 'admin';
    private const ADMIN_PASS = '12345';

    private static string $base;
    private static string $jar;
    /** @var resource|null */
    private static $server = null;

    public static function setUpBeforeClass(): void
    {
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$jar = tempnam(sys_get_temp_dir(), 'sesshard_jar_');

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
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        @unlink(self::$jar);
    }

    public function testLoginRegeneratesSessionIdAndMarksCookieHttpOnly(): void
    {
        @unlink(self::$jar);
        touch(self::$jar);

        // Establish a pre-login session so we have an id worth rotating away from.
        [$status] = self::request('GET', 'login.php');
        $this->assertSame(200, $status);

        $preLoginId = self::jarSessionId();
        $this->assertNotSame('', $preLoginId, 'login.php should mint a pre-login session id');

        [$code, $redirect, $headers] = self::request('POST', 'login_check.php', [
            'login' => self::ADMIN_USER,
            'password' => self::ADMIN_PASS,
        ]);
        $this->assertSame(302, $code, 'valid login must redirect');
        $this->assertStringContainsString('admin.php', $redirect);

        $postLoginId = self::jarSessionId();
        $this->assertNotSame('', $postLoginId);
        $this->assertNotSame(
            $preLoginId,
            $postLoginId,
            'session id must change across successful login (regeneration)'
        );

        $setCookies = array_values(array_filter(
            $headers,
            static fn (string $h): bool => stripos($h, 'set-cookie:') === 0
                && stripos($h, 'phpsessid=' . $postLoginId) !== false
        ));
        $this->assertNotEmpty($setCookies, 'login must re-issue the rotated session cookie');
        foreach ($setCookies as $line) {
            $this->assertStringContainsStringIgnoringCase('httponly', $line, 'session cookie must be HttpOnly');
        }
    }

    public function testEmptyInputLoginRedirectsToLoginNotAdmin(): void
    {
        @unlink(self::$jar);
        touch(self::$jar);

        [$code, $redirect] = self::request('POST', 'login_check.php');

        $this->assertSame(302, $code, 'empty-input login attempt must redirect');
        $this->assertStringContainsString('login.php?user_msg=', $redirect);
        $this->assertStringNotContainsString('admin.php', $redirect);
    }

    public function testLogoutExpiresCookieAndRejectsOldSession(): void
    {
        @unlink(self::$jar);
        touch(self::$jar);
        self::login();

        // Sanity: the freshly logged-in session is accepted.
        [$code, $redirect] = self::request('GET', 'session.php');
        $this->assertSame(200, $code, 'logged-in session.php should pass, got redirect to ' . $redirect);
        $oldId = self::jarSessionId();
        $this->assertNotSame('', $oldId);

        [, , $headers] = self::request('GET', 'logout.php');

        $expiryCookies = array_values(array_filter(
            $headers,
            static fn (string $h): bool => preg_match('/^set-cookie:\s*phpsessid=[^;]*;.*(expires=|max-age=)/i', trim($h)) === 1
        ));
        $this->assertNotEmpty($expiryCookies, 'logout must expire the session cookie via Set-Cookie');

        // Old session id must no longer grant access.
        [$code, $redirect] = self::request('GET', 'session.php');
        $this->assertSame(302, $code, 'old session id must be rejected after logout');
        $this->assertStringContainsString('login.php', $redirect);
    }

    private static function login(): void
    {
        [$code, $redirect] = self::request('POST', 'login_check.php', [
            'login' => self::ADMIN_USER,
            'password' => self::ADMIN_PASS,
        ]);
        self::assertSame(302, $code, 'setup login should redirect');
        self::assertStringContainsString('admin.php', (string) $redirect, 'setup login should succeed');
    }

    private static function jarSessionId(): string
    {
        foreach (file(self::$jar, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (str_starts_with($line, '#') && !str_starts_with($line, '#HttpOnly_')) {
                continue;
            }
            $fields = explode("\t", $line);
            if (count($fields) >= 7 && $fields[5] === 'PHPSESSID') {
                return $fields[6];
            }
        }

        return '';
    }

    /**
     * @param array<string, string> $post
     * @return array{0: int, 1: string, 2: list<string>} [http status, redirect URL ('' if none), header lines]
     */
    private static function request(string $method, string $path, array $post = []): array
    {
        $headerDump = tempnam(sys_get_temp_dir(), 'sesshard_hdr_');
        $args = [
            'curl', '-s', '-o', '/dev/null', '--max-redirs', '0',
            '-X', $method,
            '-A', 'SessionHardeningTest/1.0',
            '-b', self::$jar,
            '-c', self::$jar,
            '-D', $headerDump,
            '-w', '%{http_code} %{redirect_url}',
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

        $parts = explode(' ', trim($out), 2);
        $headers = array_values(array_filter(file($headerDump, FILE_IGNORE_NEW_LINES) ?: []));

        return [(int) ($parts[0] ?? 0), trim((string) ($parts[1] ?? '')), $headers];
    }
}
