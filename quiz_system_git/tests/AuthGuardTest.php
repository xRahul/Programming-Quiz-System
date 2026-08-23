<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Guards the privileged endpoints: unauthenticated POSTs must be redirected
 * to login.php with zero database mutations; an authenticated admin POST
 * must still succeed end-to-end.
 */
final class AuthGuardTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8091;
    private const ADMIN_USER = 'admin';
    private const ADMIN_PASS = '12345';

    /** @var array<string, list<string>> endpoint => payload that would mutate data if the guard regressed */
    private const ENDPOINTS = [
        'register.php' => ['login' => 'guardtest_intruder', 'password' => 'intruder-pass'],
        'addNewQuiz.php' => ['quizName' => 'guardtest_hack_quiz', 'quizTime' => '5', 'numQues' => '1'],
        'updateExistingQuiz.php' => ['quizName' => 'guardtest_renamed', 'quizTime' => '5', 'numQues' => '1'],
        'deleteSomeQues.php' => ['total_ques' => '1'],
        'editaquest.php' => ['desc' => 'hacked question', 'iscorrect' => '1'],
        'changePassword.php' => ['login' => self::ADMIN_USER, 'password' => 'hijacked-pass'],
    ];

    private static string $base;
    private static string $anonJar;
    private static string $authJar;
    /** @var resource|null */
    private static $server = null;
    private static ?PDO $pdo = null;

    public static function setUpBeforeClass(): void
    {
        require_live_db_credentials();
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$anonJar = tempnam(sys_get_temp_dir(), 'authguard_anon_');
        self::$authJar = tempnam(sys_get_temp_dir(), 'authguard_auth_');

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

        // Independent handle on purpose: requiring scripts/db.php here would
        // consume its require_once and break DbConnectTest's own bootstrap.
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
        @unlink(self::$anonJar);
        @unlink(self::$authJar);
        self::$pdo = null;
    }

    public function testUnauthenticatedPostsAreRedirectedWithoutMutatingData(): void
    {
        foreach (self::ENDPOINTS as $endpoint => $payload) {
            $adminsBefore = (int) self::$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
            $quizesBefore = self::$pdo->query('SELECT quiz_name FROM quizes ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);

            [$status, $redirect] = self::request('POST', $endpoint, $payload);

            $this->assertSame(302, $status, "$endpoint must redirect when unauthenticated");
            $this->assertStringContainsString('login.php', (string) $redirect, "$endpoint must target login.php");

            $adminsAfter = (int) self::$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
            $quizesAfter = self::$pdo->query('SELECT quiz_name FROM quizes ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);

            $this->assertSame($adminsBefore, $adminsAfter, "$endpoint mutated admins");
            $this->assertSame($quizesBefore, $quizesAfter, "$endpoint mutated quizes");
        }
    }

    public function testAuthenticatedAdminCanStillRegisterNewUser(): void
    {
        [$status, $redirect] = self::request('POST', 'login_check.php', [
            'login' => self::ADMIN_USER,
            'password' => self::ADMIN_PASS,
        ], self::$authJar);
        $this->assertSame(302, $status, 'admin login should redirect');
        $this->assertStringContainsString('admin.php', (string) $redirect, 'admin login should succeed');

        $newUser = 'guardtest_' . bin2hex(random_bytes(4));
        $before = (int) self::$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();

        [$status, $redirect] = self::request('POST', 'register.php', [
            'login' => $newUser,
            'password' => 'legit-pass-123',
            'csrf_token' => self::csrfToken(),
        ], self::$authJar);

        try {
            $this->assertSame(302, $status, 'register.php should redirect after creating user');
            $this->assertStringContainsString('admin.php', (string) $redirect, 'authenticated register must not bounce to login.php');
            $after = (int) self::$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
            $this->assertSame($before + 1, $after, 'register.php must create exactly one admin row');
        } finally {
            $stmt = self::$pdo->prepare('DELETE FROM admins WHERE username = :username');
            $stmt->execute(['username' => $newUser]);
        }
    }

    /**
     * Fetches admin.php as the logged-in admin and extracts the rendered
     * session token from its hidden csrf_token input.
     */
    private static function csrfToken(): string
    {
        $args = [
            'curl', '-s', '-A', 'AuthGuardTest/1.0',
            '-b', self::$authJar, '-c', self::$authJar,
            self::$base . '/admin.php',
        ];
        $descriptors = [1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']];
        $proc = proc_open($args, $descriptors, $pipes);
        self::assertIsResource($proc);
        $body = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        proc_close($proc);

        self::assertSame(
            1,
            preg_match('/name="csrf_token" value="([0-9a-f]+)"/', $body, $m),
            'admin.php must render a csrf_token hidden input'
        );

        return (string) $m[1];
    }

    /**
     * @return array{0: int, 1: string} [http status, redirect URL ('' if none)]
     */
    private static function request(string $method, string $path, array $post = [], ?string $jar = null): array
    {
        $args = [
            'curl', '-s', '-o', '/dev/null', '--max-redirs', '0',
            '-X', $method,
            '-A', 'AuthGuardTest/1.0',
            '-b', $jar ?? self::$anonJar,
            '-c', $jar ?? self::$anonJar,
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

        return [(int) ($parts[0] ?? 0), trim($parts[1] ?? '')];
    }
}
