<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * CSRF enforcement breadth (T8 security-matrix gap test).
 *
 * CsrfTest proves token enforcement deeply for register.php and one
 * admin.php destructive handler. The 5618ac9 fix actually covers every
 * state-changing entry point; this suite requires the 403 from ALL of
 * them (authed session, missing token) and proves zero mutations across
 * the whole sweep, so removing any single csrf_verify() call fails here.
 */
final class CsrfEnforcementCoverageTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8117;
    private const ADMIN_USER = 'admin';
    private const ADMIN_PASS = '12345';

    /** endpoint => payload that would mutate data if the csrf_verify() regressed */
    private const ENDPOINTS = [
        'register.php' => [['login' => 'p8csrf_intruder', 'password' => 'csrf-pass-123']],
        'changePassword.php' => [['login' => self::ADMIN_USER, 'password' => 'hijacked-pass']],
        'addNewQuiz.php' => [['quizName' => 'p8csrf_quiz', 'quizTime' => '5', 'numQues' => '1']],
        'updateExistingQuiz.php' => [['quizName' => 'LEVEL1(EASY)', 'quizTime' => '1', 'numQues' => '1']],
        'deleteSomeQues.php' => [['total_ques' => '1', 'qu1' => '999999']],
        'editaquest.php' => [['desc' => 'csrf probe', 'iscorrect' => 'answer1', 'questionID' => '999999']],
        'import_questions.php' => [['format' => 'json', 'payload' => '{"questions":[]}']],
        'reset_password.php' => [['login' => 'admin', 'password' => 'short']],
        // admin.php's csrf_verify() sits above every destructive handler.
        'admin.php' => [
            ['defaultQuiz' => 'P8 Ghost Quiz'],
            ['clearResult' => '999999'],
            ['deleteAdmin' => 'p8csrf_ghost'],
            ['resetTables' => 'yes'],
        ],
    ];

    private static string $base;
    private static string $authJar;
    /** @var resource|null */
    private static $server = null;
    private static ?PDO $pdo = null;

    public static function setUpBeforeClass(): void
    {
        require_live_db_credentials();
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$authJar = tempnam(sys_get_temp_dir(), 'p8csrf_jar_');

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

        [$status] = self::request('POST', 'login_check.php', [
            'login' => self::ADMIN_USER,
            'password' => self::ADMIN_PASS,
        ]);
        self::assertSame(302, $status, 'admin login should redirect');
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
        @unlink(self::$authJar);
        self::$pdo = null;
    }

    public function testEveryStateChangingEndpointRejectsMissingToken(): void
    {
        $before = self::tableCounts();

        foreach (self::ENDPOINTS as $endpoint => $payloads) {
            foreach ($payloads as $payload) {
                [$status, , $body] = self::request('POST', $endpoint, $payload);
                $label = "$endpoint (" . implode(',', array_keys($payload)) . ')';
                $this->assertSame(
                    403,
                    $status,
                    "$label must reject an authed POST without a CSRF token"
                );
                $this->assertStringContainsString('Invalid request token.', (string) $body);
            }
        }

        $this->assertSame($before, self::tableCounts(), 'the whole token-less sweep must not mutate anything');

        // Sanity: the same session WITH its token gets past csrf_verify()
        // (here: a read-shaped admin AJAX that must answer 200, not 403).
        [, , $body] = self::request('POST', 'admin.php', [
            'usersQuiz' => 'LEVEL2(HARD)',
            'csrf_token' => self::token(),
        ]);
        $this->assertStringContainsString('Rank', (string) $body, 'valid-token request must not be rejected');
    }

    // ---------- helpers ----------

    /** @return array<string, int> row counts of every mutable table */
    private static function tableCounts(): array
    {
        $counts = [];
        foreach (['admins', 'quizes', 'questions', 'answers', 'quiz_takers'] as $table) {
            $counts[$table] = (int) self::$pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        }

        return $counts;
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
            '-A', 'CsrfEnforcementCoverageTest/1.0',
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
