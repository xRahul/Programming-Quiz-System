<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Once-per-quiz enforcement, start-side half (T8 gap test).
 *
 * CorrectnessFixesTest::testResultResubmissionGuardBlocksSecondSubmission
 * proves the result.php replay guard; nothing proved the OTHER legacy
 * guard: starting the same quiz twice through the public flow must be
 * blocked by the (username, quiz_id) existence check with the legacy
 * message, leaving the recorded taker row untouched.
 */
final class OncePerQuizGuardTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8112;

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
        self::$server = proc_open(
            $cmd,
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes
        );
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

    public function testSecondStartOfSameQuizIsBlockedWithLegacyMessage(): void
    {
        $stmt = self::$pdo->query("SELECT id, quiz_name FROM quizes WHERE set_default = 1");
        $quiz = $stmt->fetch();
        self::assertIsArray($quiz, 'seed must contain a default quiz');
        $rollno = 'p8start_' . bin2hex(random_bytes(4));

        $takerCount = static function () use ($rollno, $quiz): int {
            $stmt = OncePerQuizGuardTest::pdo()->prepare(
                'SELECT COUNT(*) FROM quiz_takers WHERE username = :username AND quiz_id = :quizId'
            );
            $stmt->execute(['username' => $rollno, 'quizId' => (int) $quiz['id']]);

            return (int) $stmt->fetchColumn();
        };

        try {
            // First start creates the duration=0 taker row and renders the quiz.
            [$status, , $body] = self::request('POST', 'quiz.php', ['rollno' => $rollno]);
            $this->assertSame(200, $status, 'first start must render the quiz page');
            $this->assertStringContainsString('name="rads1"', $body, 'quiz page must carry radio groups');
            $this->assertSame(1, $takerCount(), 'exactly one taker row after the first start');

            // Second start of the SAME quiz must hit the legacy duplicate guard.
            [$status, $redirect] = self::request('POST', 'quiz.php', ['rollno' => $rollno]);
            $this->assertSame(302, $status, 'second start must be redirected');
            $this->assertStringContainsString('index.php?user_msg=', (string) $redirect);
            $msg = urldecode((string) parse_url((string) $redirect, PHP_URL_QUERY));
            $this->assertStringContainsString('already attempted', $msg, 'legacy wording required, got: ' . $msg);
            $this->assertStringContainsString($rollno, $msg);
            $this->assertStringContainsString((string) $quiz['quiz_name'], $msg);

            $this->assertSame(1, $takerCount(), 'blocked restart must not create a second row');
        } finally {
            self::$pdo->prepare('DELETE FROM quiz_takers WHERE username = :username')
                ->execute(['username' => $rollno]);
        }
    }

    private static function pdo(): PDO
    {
        $pdo = self::$pdo;
        self::assertNotNull($pdo);

        return $pdo;
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
            '-A', 'OncePerQuizGuardTest/1.0',
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
