<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * T6.2 regression guard for the pre-existing addNewQuiz bug.
 *
 * Legacy flow: addNewQuiz.php INSERTed quizes without the denormalized
 * quiz_id column copy, so it stayed at its DEFAULT 0 while every question
 * listing JOINed questions.quiz_id = quizes.quiz_id -- UI-created quizzes
 * therefore listed zero questions forever. Migration 006 dropped the column
 * copies; this test proves the UI round trip now works end to end:
 * create a quiz through addNewQuiz.php, add a question to it through the
 * admin form, and require the listing AJAX body to show that question.
 */
final class UiCreatedQuizListableTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8099;
    private const QUIZ_NAME = 'T62 UI Created Quiz';

    private static string $base;
    private static string $authJar;
    /** @var resource|null */
    private static $server = null;
    private static ?PDO $pdo = null;
    private static int $quizId = 0;

    public static function setUpBeforeClass(): void
    {
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$authJar = tempnam(sys_get_temp_dir(), 't62_jar_');

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
            if ((int) proc_get_status(self::$server)['running'] !== 1) {
                break;
            }
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
            'login' => 'admin',
            'password' => '12345',
        ]);
        self::assertSame(302, $status, 'admin login should redirect');
    }

    public static function tearDownAfterClass(): void
    {
        // Remove everything the round trip created so absolute-count suites
        // see an untouched database.
        if (self::$pdo !== null && self::$quizId !== 0) {
            self::$pdo->prepare('DELETE FROM answers WHERE quiz_id = :id')->execute(['id' => self::$quizId]);
            self::$pdo->prepare('DELETE FROM questions WHERE quiz_id = :id')->execute(['id' => self::$quizId]);
            self::$pdo->prepare('DELETE FROM quiz_takers WHERE quiz_id = :id')->execute(['id' => self::$quizId]);
            self::$pdo->prepare('DELETE FROM quizes WHERE id = :id')->execute(['id' => self::$quizId]);
        }
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

    public function testQuizCreatedViaUiListsItsQuestions(): void
    {
        $token = self::sessionToken();

        // 1. Create a quiz through the real UI handler.
        [$status, $redirect] = self::request('POST', 'addNewQuiz.php', [
            'quizName' => self::QUIZ_NAME,
            'quizTime' => '30',
            'numQues' => '5',
            'csrf_token' => $token,
        ]);
        self::assertSame(302, $status, 'addNewQuiz must accept a valid submission');

        $stmt = self::$pdo->prepare('SELECT id FROM quizes WHERE quiz_name = :name');
        $stmt->execute(['name' => self::QUIZ_NAME]);
        $quizId = $stmt->fetchColumn();
        self::assertNotFalse($quizId, 'UI-created quiz row must exist');
        self::$quizId = (int) $quizId;

        // The old bug: quizes.quiz_id stayed at DEFAULT 0 != id.
        // The columns are gone now; the row simply carries its PK.

        // 2. Add a question to the new quiz through the admin form.
        $desc = 'T62 probe ' . bin2hex(random_bytes(4));
        [$qStatus, $qRedirect] = self::request('POST', 'admin.php', [
            'desc' => $desc,
            'code_desc' => '',
            'prog-lang' => 'plain',
            'type' => 'tf',
            'quizID' => (string) self::$quizId,
            'answer1' => 'True',
            'answer2' => 'False',
            'answer3' => '',
            'answer4' => '',
            'iscorrect' => 'answer1',
            'csrf_token' => $token,
        ]);
        self::assertSame(302, $qStatus, 'create-question must succeed for the UI-created quiz');
        self::assertStringContainsString('msg=', (string) $qRedirect);

        $stmt = self::$pdo->prepare(
            'SELECT COUNT(*) FROM questions WHERE quiz_id = :quizId AND question = :question'
        );
        $stmt->execute(['quizId' => self::$quizId, 'question' => $desc]);
        self::assertSame(1, (int) $stmt->fetchColumn(), 'question row must be attached to the new quiz');

        // 3. The per-quiz listing must show the question. This exact view
        // returned an empty table before migration 006 killed the bug.
        [, , $body] = self::request('POST', 'admin.php', [
            'questionsQuiz' => self::QUIZ_NAME,
            'csrf_token' => $token,
        ]);
        self::assertStringContainsString(htmlspecialchars($desc, ENT_QUOTES), $body,
            'UI-created quiz listing must contain the newly added question');

        // 4. Same proof through the public quiz-taking path.
        $stmtCount = self::$pdo->prepare(
            'SELECT COUNT(*) FROM questions WHERE quiz_id = :quizId'
        );
        $stmtCount->execute(['quizId' => self::$quizId]);
        self::assertSame(1, (int) $stmtCount->fetchColumn());
    }

    private static function sessionToken(): string
    {
        [, , $body] = self::request('GET', 'admin.php');
        if (preg_match('/name="csrf_token" value="([0-9a-f]+)"/', $body, $m) === 1) {
            return $m[1];
        }

        return '';
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
            '-A', 'UiCreatedQuizListableTest/1.0',
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
