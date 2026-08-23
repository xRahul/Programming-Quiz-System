<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Set-Default switch reflected on the instructions page (T8 gap test).
 *
 * StructureParityTest snapshots the index page against the seeded default
 * quiz, but nothing proved the E2E switch: defaultQuiz AJAX flips
 * set_default, and index.php's instruction block (quiz name + duration/
 * question-count line) must follow. Restores the original default via SQL
 * in finally so snapshot suites always see LEVEL2 as the default.
 */
final class DefaultQuizSwitchTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8115;
    private const QUIZ_EASY = 'LEVEL1(EASY)';
    private const QUIZ_HARD = 'LEVEL2(HARD)';

    private static string $base;
    private static string $authJar;
    /** @var resource|null */
    private static $server = null;
    private static ?PDO $pdo = null;
    private static int $origDefaultId = 0;

    public static function setUpBeforeClass(): void
    {
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$authJar = tempnam(sys_get_temp_dir(), 'p8def_jar_');

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

        [$status] = self::request('POST', 'login_check.php', ['login' => 'admin', 'password' => '12345']);
        self::assertSame(302, $status, 'admin login should redirect');

        self::$origDefaultId = (int) self::$pdo
            ->query('SELECT id FROM quizes WHERE set_default = 1')
            ->fetchColumn();
        self::assertGreaterThan(0, self::$origDefaultId, 'seed must have exactly one default quiz');
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

    public function testSwitchingDefaultReflectsOnIndexInstructions(): void
    {
        try {
            // Baseline: seed default LEVEL2(HARD) with its 20 mins / 10 questions line.
            [, , $body] = self::request('GET', 'index.php');
            $this->assertStringContainsString(self::QUIZ_HARD, (string) $body);
            $this->assertStringContainsString("You've got 20 mins for attempting 10 questions.", (string) $body);
            $this->assertStringNotContainsString(self::QUIZ_EASY . ' </strong>', (string) $body);

            // Switch the default through the real admin AJAX handler.
            [$status, , $frag] = self::request('POST', 'admin.php', [
                'defaultQuiz' => self::QUIZ_EASY,
                'csrf_token' => self::token(),
            ]);
            $this->assertSame(200, $status);
            $this->assertStringContainsString('has now been set as default', (string) $frag);

            [, , $body] = self::request('GET', 'index.php');
            $this->assertStringContainsString(self::QUIZ_EASY, (string) $body, 'index must follow the new default');
            $this->assertStringContainsString("You've got 30 mins for attempting 20 questions.", (string) $body);
            $this->assertStringNotContainsString(self::QUIZ_HARD . ' </strong>', (string) $body);

            // Legacy wart kept bug-for-bug: the handler clears set_default
            // BEFORE the targeted update, so a failed switch (unknown quiz
            // name) leaves ZERO default quizzes -- index falls back to its
            // no-quizzes notice. If this ever gets transactional, update
            // this block together with the behavior change.
            [, , $frag] = self::request('POST', 'admin.php', [
                'defaultQuiz' => 'P8 No Such Quiz',
                'csrf_token' => self::token(),
            ]);
            $this->assertStringContainsString('Sorry, there was a problem setting', (string) $frag);
            $this->assertSame(
                0,
                (int) self::$pdo->query('SELECT COUNT(*) FROM quizes WHERE set_default = 1')->fetchColumn(),
                'legacy failed switch leaves no default at all'
            );
            [, , $body] = self::request('GET', 'index.php');
            $this->assertStringContainsString('no quizzes Available right now', (string) $body);

            // Exactly one row may carry set_default=1 whenever a default exists.
            [, , $frag] = self::request('POST', 'admin.php', [
                'defaultQuiz' => self::QUIZ_HARD,
                'csrf_token' => self::token(),
            ]);
            $this->assertStringContainsString('has now been set as default', (string) $frag);
            $this->assertSame(
                1,
                (int) self::$pdo->query('SELECT COUNT(*) FROM quizes WHERE set_default = 1')->fetchColumn()
            );
        } finally {
            // Deterministic restore: snapshot suites depend on this default.
            self::$pdo?->exec('UPDATE quizes SET set_default = 0');
            $stmt = self::$pdo?->prepare('UPDATE quizes SET set_default = 1 WHERE id = :id');
            $stmt?->execute(['id' => self::$origDefaultId]);
        }
    }

    // ---------- helpers ----------

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
            '-A', 'DefaultQuizSwitchTest/1.0',
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
