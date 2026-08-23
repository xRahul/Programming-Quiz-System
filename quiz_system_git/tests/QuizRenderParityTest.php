<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Body parity guard for the quiz page question/answer table (T7 phase 7).
 *
 * StructureParityTest deliberately scopes to the wrapper regions because the
 * quiz middle is randomized (shuffled question order, random-per-question
 * answer order). This guard owns that middle: both sides are canonicalized
 * before comparison so ordering randomness cannot flake the test while every
 * other byte still counts.
 *
 * Canonicalization contract (applied to BOTH sides):
 *  - the <div class="table-wrap"> table region is split at each
 *    <pre class="question_style"> marker into per-question chunks;
 *  - each chunk reduces to: question text | optional "brush:" code block |
 *    its (value => label) radio pairs sorted by answer id, dropping the
 *    positional rads{N} group number;
 *  - the chunk list itself is sorted, making question shuffle order inert.
 *
 * Positional markup that canonicalization intentionally ignores is instead
 * pinned by explicit assertions: sequential rads1..radsN group names, the
 * submit button, and the hidden inputs (rollno/total_ques/total_time/quizID).
 *
 * Fixture contract mirrors StructureParityTest: first run mints
 * tests/fixtures/snapshot-quiz-body.html from the live render; later runs
 * re-render and compare canonically.
 */
final class QuizRenderParityTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8110;
    /** roll number driving quiz.php; rows cleaned up before and after */
    private const ROLL = 'quiz_body_parity_runner';

    private static string $base;
    /** @var resource|null */
    private static $server = null;
    private static ?PDO $pdo = null;
    private static int $defaultQuizId = 0;
    private static int $expectedQuestions = 0;
    private static int $expectedTime = 0;
    private static ?string $rendered = null;

    public static function setUpBeforeClass(): void
    {
        require_live_db_credentials();
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);

        $docroot = dirname(__DIR__);
        // exec: let proc_get_status() see the real server PID for teardown.
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

        $row = self::$pdo->query(
            'SELECT id, display_questions, time_allotted FROM quizes WHERE set_default=1 ORDER BY id LIMIT 1'
        )->fetch();
        if ($row === false) {
            self::markTestSkipped('No default quiz configured; cannot drive quiz flow.');
        }
        self::$defaultQuizId = (int) $row['id'];
        self::$expectedTime = ((int) preg_replace('/[^0-9]/', '', (string) $row['time_allotted'])) * 60;
        $stmtCount = self::$pdo->prepare('SELECT COUNT(*) FROM questions WHERE quiz_id = :id');
        $stmtCount->execute(['id' => self::$defaultQuizId]);
        self::$expectedQuestions = min(
            (int) preg_replace('/[^0-9]/', '', (string) $row['display_questions']),
            (int) $stmtCount->fetchColumn()
        );

        // Idempotent runs: a previously killed suite may have left rows behind.
        self::$pdo->prepare('DELETE FROM quiz_takers WHERE username = ?')->execute([self::ROLL]);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$pdo !== null) {
            self::$pdo->prepare('DELETE FROM quiz_takers WHERE username = ?')->execute([self::ROLL]);
        }
        if (is_resource(self::$server)) {
            $status = proc_get_status(self::$server);
            if (!empty($status['pid'])) {
                exec('kill -9 ' . (int) $status['pid'] . ' >/dev/null 2>&1');
            }
            proc_close(self::$server);
        }
        self::$pdo = null;
    }

    public function testQuizBodyMatchesCommittedSnapshot(): void
    {
        $fixture = __DIR__ . '/fixtures/snapshot-quiz-body.html';
        if (!is_file($fixture)) {
            file_put_contents($fixture, self::renderQuiz());
        }

        $expected = (string) file_get_contents($fixture);
        $actual = self::renderQuiz();

        self::assertSame(
            self::canonicalize($expected),
            self::canonicalize($actual),
            'quiz body diverged from committed snapshot'
        );

        self::assertPositionalMarkup($actual);
    }

    /**
     * Pins the markup that canonicalization deliberately normalizes away.
     */
    private static function assertPositionalMarkup(string $html): void
    {
        self::assertSame(
            1,
            preg_match('/<input type="hidden" name="rollno" value="' . self::ROLL . '">/', $html),
            'rollno hidden input expected verbatim'
        );
        self::assertSame(
            1,
            substr_count($html, '<input type="hidden" name="total_ques" value="' . self::$expectedQuestions . '">'),
            "total_ques hidden input expected to be " . self::$expectedQuestions
        );
        self::assertSame(
            1,
            substr_count($html, '<input type="hidden" name="total_time" value="' . self::$expectedTime . '">'),
            "total_time hidden input expected to be " . self::$expectedTime
        );
        self::assertSame(
            1,
            substr_count($html, '<input type="hidden" name="quizID" value="' . self::$defaultQuizId . '">'),
            'quizID hidden input expected verbatim'
        );
        self::assertSame(
            1,
            substr_count($html, '<a href="javascript:{}" onclick="quiz_submit()" class="myButton">Submit</a>'),
            'submit button expected exactly once'
        );

        $groups = [];
        if (preg_match_all('/name="rads(\d+)"/', $html, $m) > 0) {
            $groups = array_count_values($m[1]);
        }
        self::assertSame(
            range(1, self::$expectedQuestions),
            array_keys($groups),
            'radio groups must be named sequentially rads1..radsN'
        );
        foreach ($groups as $group => $count) {
            self::assertGreaterThanOrEqual(2, $count, "rads$group should carry its option set");
        }
    }

    /**
     * Order-insensitive canonical form of the quiz table region; see the
     * class docblock for the contract.
     */
    private static function canonicalize(string $html): string
    {
        self::assertSame(
            1,
            preg_match('#<div class="table-wrap">\s*<table[^>]*>(.*?)</table>#is', $html, $region),
            'quiz table region not found'
        );

        $chunks = preg_split(
            '/(<pre class="question_style">)/',
            $region[1],
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        ) ?: [];
        self::assertGreaterThan(2, count($chunks), 'quiz table region has no questions');

        $canonical = [];
        for ($i = 1; $i < count($chunks); $i += 2) {
            $chunk = $chunks[$i] . $chunks[$i + 1];

            self::assertSame(
                1,
                preg_match(
                    '/^<pre class="question_style"><strong><div style="width: 730px; word-wrap: break-word;">(.*?)<\/div><\/strong><\/pre>/s',
                    $chunk,
                    $q
                ),
                'question text block malformed'
            );

            $code = '';
            if (preg_match('/<pre class="brush: ([^;]+);">(.*?)<\/pre>/s', $chunk, $c) === 1) {
                $code = $c[1] . '~' . $c[2];
            }

            $radios = [];
            $names = [];
            if (preg_match_all('/name="rads(\d+)" value="([^"]+)">([^<]*)<\/label>/', $chunk, $m, PREG_SET_ORDER) > 0) {
                foreach ($m as $r) {
                    $names[$r[1]] = true;
                    $radios[$r[2]] = $r[3];
                }
            }
            self::assertCount(1, $names, 'one radio group per question chunk expected');

            ksort($radios);
            $pairs = [];
            foreach ($radios as $value => $label) {
                $pairs[] = $value . '=' . $label;
            }
            $canonical[] = $q[1] . '|' . $code . '|' . implode("\x1f", $pairs);
        }

        sort($canonical);

        return implode("\n---\n", $canonical);
    }

    private static function renderQuiz(): string
    {
        if (self::$rendered !== null) {
            return self::$rendered;
        }

        [$status, , $body] = self::request('POST', 'quiz.php', ['rollno' => self::ROLL]);
        self::assertSame(200, $status, 'quiz.php should render (got ' . $status . ')');

        return self::$rendered = $body;
    }

    /**
     * @param array<string, string> $post
     * @return array{0: int, 1: string, 2: string} [status, redirect, body]
     */
    private static function request(string $method, string $path, array $post = []): array
    {
        $jar = tempnam(sys_get_temp_dir(), 'quiz_parity_jar_');
        $args = [
            'curl', '-s', '-A', 'QuizRenderParityTest/1.0',
            '-b', $jar, '-c', $jar,
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
        @unlink($jar);

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
