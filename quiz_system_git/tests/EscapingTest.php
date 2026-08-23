<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * T4.4 escape-on-output verification.
 *
 * Question/answer/quiz-name payloads containing an XSS probe are stored
 * through the REAL HTTP handlers (which must now store them RAW) and every
 * render surface must emit the encoded forms only. The results-table
 * username row is seeded directly so the fixture stays scoped to this
 * suite's own quiz instead of touching the shared default quiz.
 */
final class EscapingTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8098;
    private const ADMIN_USER = 'admin';
    private const ADMIN_PASS = '12345';

    private const ESC_QUIZ_ID = 987300;
    private const ESC_QID = 987301;
    /** single probe carrying every interesting character */
    private const PROBE = '<script>alert(1)</script>"quotes" \'apos\'';

    private static string $base;
    private static string $authJar;
    /** @var resource|null */
    private static $server = null;
    private static ?PDO $pdo = null;
    private static ?int $escQuizId = null;

    public static function setUpBeforeClass(): void
    {
        require_live_db_credentials();
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$authJar = tempnam(sys_get_temp_dir(), 'esc_jar_');

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

        [, $redirect] = self::request('POST', 'login_check.php', [
            'login' => self::ADMIN_USER,
            'password' => self::ADMIN_PASS,
        ], self::$authJar);
        self::assertNotSame('', $redirect, 'admin login should redirect');
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$pdo !== null) {
            // FKs cascade to takers/questions/answers
            self::$pdo->prepare('DELETE FROM quizes WHERE quiz_name = :name OR id = :id')
                ->execute(['name' => self::PROBE, 'id' => self::ESC_QUIZ_ID]);
        }
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        @unlink(self::$authJar);
        self::$pdo = null;
    }

    public function testStoredValuesAreRawAfterHttpCreateFlows(): void
    {
        // create the hostile quiz name through the real handler
        [$status, $redirect] = self::request('POST', 'addNewQuiz.php', [
            'quizName' => self::PROBE,
            'quizTime' => '30',
            'numQues' => '1',
            'csrf_token' => self::token(),
        ], self::$authJar);
        self::assertSame(302, $status);

        $quizId = self::quizIdByName(self::PROBE);
        self::assertNotNull($quizId, 'hostile quiz must be stored under its exact raw name');
        self::$escQuizId = $quizId;

        // create a hostile question + answers through the real handler
        [$qStatus] = self::request('POST', 'admin.php', [
            'desc' => self::PROBE,
            'code_desc' => self::PROBE,
            'prog-lang' => 'php',
            'answer1' => self::PROBE,
            'answer2' => 'plain answer',
            'answer3' => '',
            'answer4' => '',
            'type' => 'tf',
            'quizID' => (string) $quizId,
            'iscorrect' => 'answer1',
            'csrf_token' => self::token(),
        ], self::$authJar);
        self::assertSame(302, $qStatus);

        self::assertSame(
            self::PROBE,
            self::storedQuestion((int) $quizId, self::PROBE),
            'storage must be RAW after T4.4 (no entity encoding at input)'
        );
    }

    public function testAdminAjaxBodiesEscapeTheProbe(): void
    {
        // results-table username row, scoped to this suite's own quiz
        self::$pdo->prepare(
            "INSERT INTO quiz_takers (username, percentage, date_time, quiz_id, duration, marks)
             VALUES (:username, '0', '2026-01-02 03:04:05', :quizId, 5, 7)"
        )->execute(['username' => self::PROBE, 'quizId' => self::$escQuizId]);

        $body = self::ajaxBody('questionsQuiz', self::PROBE);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $body);
        self::assertStringNotContainsString('<script>', $body);

        foreach (['editaquestion', 'deleteSomeQuestions'] as $endpoint) {
            $body = self::ajaxBody($endpoint, self::PROBE);
            self::assertStringContainsString('&lt;script&gt;', $body, "$endpoint must escape question text");
            self::assertStringNotContainsString('<script>', $body, "$endpoint must not emit the raw probe");
        }

        // usernames rendered into the results tables must be escaped too
        $body = self::ajaxBody('usersAll', self::PROBE);
        self::assertStringContainsString('&lt;script&gt;', $body);
        self::assertStringNotContainsString('<script>', $body);
    }

    public function testAdminPageMenuHexEncodesNamesInJsArgsAndNeverEmitsRawProbe(): void
    {
        [, , $body] = self::request('GET', 'admin.php', [], self::$authJar);

        self::assertStringNotContainsString('<script>alert', $body, 'raw probe must never reach the page');
        self::assertStringContainsString('\u003Cscript\u003Ealert(1)', $body, 'menu JS args must carry the hex-encoded probe');
        self::assertStringContainsString('(&quot;\u003Cscript\u003Ealert(1)', $body, 'JS arg must be attribute-encoded inside href');
    }

    public function testPublicMessageEchoesEscapeUserMsg(): void
    {
        [, , $indexBody] = self::request('GET', 'index.php?user_msg=' . urlencode(self::PROBE));
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $indexBody);
        self::assertStringNotContainsString('<script>alert', $indexBody);

        [, , $loginBody] = self::request('GET', 'login.php?user_msg=' . urlencode(self::PROBE));
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $loginBody);
        self::assertStringNotContainsString('<script>alert', $loginBody);
    }

    /**
     * Declared LAST on purpose: it swaps the shared auth jar to a hostile
     * username, so every earlier escaping probe must already have run.
     */
    public function testAdminGreetingEscapesHostileLoginSession(): void
    {
        $hostile = '<script>alert(9)</script>';

        [$status] = self::request('POST', 'register.php', [
            'login' => $hostile,
            'password' => 'escape-pass-123',
            'csrf_token' => self::token(),
        ], self::$authJar);
        self::assertSame(302, $status, 'register with valid token should create the hostile admin');

        try {
            [$status] = self::request('POST', 'login_check.php', [
                'login' => $hostile,
                'password' => 'escape-pass-123',
            ], self::$authJar);
            self::assertSame(302, $status, 'hostile admin login should redirect');

            [, , $body] = self::request('GET', 'admin.php', [], self::$authJar);
            // admin.php:820 renders $login_session into span#usr.
            self::assertStringContainsString(
                '<span id="usr">&lt;script&gt;alert(9)&lt;/script&gt;!</span>',
                $body,
                'greeting must emit the escaped login_session'
            );
            self::assertStringNotContainsString('<script>alert(9)', $body, 'raw login_session must never render');
        } finally {
            self::$pdo?->prepare('DELETE FROM admins WHERE username = :username')
                ->execute(['username' => $hostile]);
        }
    }

    // ---------- helpers ----------

    private static function token(): string
    {
        [, , $body] = self::request('GET', 'admin.php', [], self::$authJar);
        self::assertSame(
            1,
            preg_match('/name="csrf_token" value="([^"]+)"/', $body, $m),
            'admin page must expose a csrf token'
        );

        return $m[1];
    }

    private static function ajaxBody(string $key, string $quizName): string
    {
        [$status, , $body] = self::request('POST', 'admin.php', [
            $key => $quizName,
            'csrf_token' => self::token(),
        ], self::$authJar);
        self::assertSame(200, $status, "$key body should render");

        return $body;
    }

    private static function quizIdByName(string $name): ?int
    {
        $stmt = self::$pdo->prepare('SELECT id FROM quizes WHERE quiz_name = :name');
        $stmt->execute(['name' => $name]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    private static function storedQuestion(int $quizId, string $probe): string
    {
        $stmt = self::$pdo->prepare('SELECT question FROM questions WHERE quiz_id = :id AND question = :probe LIMIT 1');
        $stmt->execute(['id' => $quizId, 'probe' => $probe]);

        return (string) $stmt->fetchColumn();
    }

    /**
     * @param array<string, string> $post
     * @return array{0: int, 1: string, 2: string} [status, redirect, body]
     */
    private static function request(string $method, string $path, array $post = [], ?string $jar = null): array
    {
        $args = [
            'curl', '-s', '--max-time', '10', '-A', 'EscapingTest/1.0',
            '-b', $jar ?? self::$authJar, '-c', $jar ?? self::$authJar,
            '-D', '-', '-w', "\n%{http_code}",
            '-X', $method,
            self::$base . '/' . $path,
        ];
        if ($post !== []) {
            array_push($args, '--data', http_build_query($post));
        }

        $descriptors = [1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']];
        $proc = proc_open($args, $descriptors, $pipes);
        self::assertIsResource($proc);
        $out = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        proc_close($proc);

        $split = strpos($out, "\r\n\r\n");
        $headerBlock = $split === false ? $out : substr($out, 0, $split);
        $rest = $split === false ? '' : substr($out, $split + 4);

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
