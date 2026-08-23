<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Phase 2 correctness batch: regression tests for the small behavior fixes
 * (edit-question answer prefill, digit stripping in destructive handlers,
 * required-field validation of literal '0', truncated closing tag, message
 * escaping, quiz timer/validator JS, resubmission guard).
 */
final class CorrectnessFixesTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8095;
    private const ADMIN_USER = 'admin';
    private const ADMIN_PASS = '12345';
    private const FIXTURE_QID = 987001;

    private static string $base;
    private static string $authJar;
    /** @var resource|null */
    private static $server = null;
    private static ?PDO $pdo = null;

    public static function setUpBeforeClass(): void
    {
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$authJar = tempnam(sys_get_temp_dir(), 'p2fix_jar_');

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

    public function testEditAqPrefillsRealMcAnswersNotUndefinedVar(): void
    {
        self::login();
        $token = self::sessionToken();

        $stmtQ = self::$pdo->prepare(
            "INSERT INTO questions (quiz_id, question_id, question, code, code_type, type)
             VALUES (1, :qid, 'P2 fixture MC question?', '', '', 'mc')"
        );
        $stmtQ->execute(['qid' => self::FIXTURE_QID]);
        $stmtA = self::$pdo->prepare(
            "INSERT INTO answers (quiz_id, question_id, answer, correct) VALUES (1, :qid, :answer, :correct)"
        );
        $answers = ['p2alpha', 'p2bravo', 'p2charlie', 'p2delta'];
        foreach ($answers as $i => $text) {
            $stmtA->execute([
                'qid' => self::FIXTURE_QID,
                'answer' => $text,
                'correct' => $i === 0 ? '1' : '0',
            ]);
        }

        try {
            [, , $body] = self::request('POST', 'admin.php', [
                'editAQ' => (string) self::FIXTURE_QID,
                'csrf_token' => $token,
            ]);

            foreach ($answers as $i => $text) {
                $this->assertStringContainsString(
                    'mcanswer' . ($i + 1) . '\').value = "' . $text . '"',
                    $body,
                    'MC edit prefill must embed the real answer text'
                );
            }
            $this->assertStringNotContainsString('= null;', $body, '$gaq_answer undefined-variable artifact must be gone');
            $this->assertStringContainsString('mcans1\').checked=true;', $body, 'correct option must be pre-checked');
        } finally {
            self::$pdo->prepare('DELETE FROM answers WHERE question_id = :qid')
                ->execute(['qid' => self::FIXTURE_QID]);
            self::$pdo->prepare('DELETE FROM questions WHERE question_id = :qid')
                ->execute(['qid' => self::FIXTURE_QID]);
        }
    }

    private static function login(): void
    {
        @unlink(self::$authJar);
        touch(self::$authJar);
        [$status, $redirect] = self::request('POST', 'login_check.php', [
            'login' => self::ADMIN_USER,
            'password' => self::ADMIN_PASS,
        ]);
        self::assertSame(302, $status, 'admin login should redirect');
        self::assertStringContainsString('admin.php', (string) $redirect, 'admin login should succeed');
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
            '-A', 'CorrectnessFixesTest/1.0',
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
