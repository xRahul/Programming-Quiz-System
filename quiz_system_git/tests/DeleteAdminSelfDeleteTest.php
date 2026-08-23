<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * deleteAdmin self-delete parity proof (T8 gap test).
 *
 * Legacy behavior (readme: "Delete Your Account") lets an admin delete the
 * account currently logged in -- there is deliberately NO self-delete
 * guard. This suite proves that behavior is preserved: a temp admin logs
 * in, deletes their own username through admin.php, gets the legacy
 * success fragment, and the row disappears with an audited trail.
 */
final class DeleteAdminSelfDeleteTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8116;

    private static string $base;
    private static ?PDO $pdo = null;
    /** @var resource|null */
    private static $server = null;

    public static function setUpBeforeClass(): void
    {
        require_live_db_credentials();
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);

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
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            $status = proc_get_status(self::$server);
            if (!empty($status['pid'])) {
                exec('kill -9 ' . (int) $status['pid'] . ' >/dev/null 2>&1');
            }
            proc_close(self::$server);
        }
        self::$pdo = null;
    }

    public function testSelfDeleteMatchesLegacyAllowedBehavior(): void
    {
        $username = 'p8_selfdel_' . bin2hex(random_bytes(4));
        $jar = (string) tempnam(sys_get_temp_dir(), 'p8selfdel_jar_');

        self::$pdo->prepare('INSERT INTO admins (username, password) VALUES (:username, :password)')
            ->execute([
                'username' => $username,
                'password' => password_hash('p8-selfdel-pass', PASSWORD_DEFAULT),
            ]);

        try {
            // Log in AS the doomed admin.
            [$status] = self::request('POST', 'login_check.php', [
                'login' => $username,
                'password' => 'p8-selfdel-pass',
            ], $jar);
            $this->assertSame(302, $status, 'temp admin login should redirect');

            [, , $body] = self::request('GET', 'admin.php', [], $jar);
            $this->assertSame(1, preg_match('/name="csrf_token" value="([0-9a-f]+)"/', (string) $body, $m));
            $token = $m[1];

            // Self-delete through the real handler.
            [$status, , $frag] = self::request('POST', 'admin.php', [
                'deleteAdmin' => $username,
                'csrf_token' => $token,
            ], $jar);
            $this->assertSame(200, $status);
            $this->assertStringContainsString('has now been deleted', (string) $frag);
            $this->assertStringContainsString(htmlspecialchars($username, ENT_QUOTES), (string) $frag);

            $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM admins WHERE username = :username');
            $stmt->execute(['username' => $username]);
            $this->assertSame(0, (int) $stmt->fetchColumn(), 'self-deleted admin row must be gone');

            $stmt = self::$pdo->prepare(
                "SELECT actor, detail FROM audit_log WHERE action = 'delete_admin'
                 ORDER BY id DESC LIMIT 1"
            );
            $stmt->execute();
            $audit = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->assertIsArray($audit, 'delete_admin must be audited');
            $this->assertSame($username, $audit['actor'], 'the deleting actor is the deleted admin themselves');
            $this->assertSame($username, $audit['detail']);
        } finally {
            self::$pdo?->prepare("DELETE FROM admins WHERE username = :username")
                ->execute(['username' => $username]);
            self::$pdo?->prepare("DELETE FROM audit_log WHERE action = 'delete_admin' AND detail = :detail")
                ->execute(['detail' => $username]);
            @unlink($jar);
        }
    }

    /**
     * @param array<string, string> $post
     * @return array{0: int, 1: string, 2: string} [http status, redirect URL ('' if none), body]
     */
    private static function request(string $method, string $path, array $post = [], ?string $jar = null): array
    {
        $args = [
            'curl', '-s', '--max-redirs', '0',
            '-X', $method,
            '-A', 'DeleteAdminSelfDeleteTest/1.0',
            '-b', $jar ?? '',
            '-c', $jar ?? '',
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
