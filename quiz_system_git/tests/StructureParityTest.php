<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Structural parity guard for the five HTML pages (T4.1/T4.5/T4.6).
 *
 * On the first run (no fixture yet) each page is rendered through the PHP
 * built-in server and the raw response is stored in tests/fixtures/. Later
 * runs re-render and compare the <head>/<body> wrapper regions against the
 * committed fixture.
 *
 * Normalization contract (documented deviations, applied to BOTH sides):
 *  - the viewport meta added by T4.1 is removed;
 *  - every <script> element is removed (T4.5 moves admin's inline blocks to
 *    assets/js/admin.js; the other pages keep their tiny inline scripts);
 *  - charset meta / title element are removed because the shared head partial
 *    unifies their legacy per-page ordering;
 *  - HTML comments are stripped (result.php's favicon comment text gets
 *    unified with the other four pages by the shared favicon partial);
 *  - doctype case and the optional html lang attribute are unified;
 *  - runs of whitespace collapse to a single space (the shared partials emit
 *    one canonical indentation).
 *
 * Everything else in the wrapper regions must match byte for byte.
 */
final class StructureParityTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8096;
    private const ADMIN_USER = 'admin';
    private const ADMIN_PASS = '12345';
    /** roll numbers used to drive quiz.php/result.php; rows are cleaned up */
    private const QUIZ_ROLL = 'snapshot_runner';
    private const RESULT_ROLL = 'snapshot_result_runner';

    private const PAGES = ['index', 'login', 'admin', 'quiz', 'result'];

    /**
     * Deterministic seed rows backing the five admin AJAX renderer bodies
     * (T4.2). Seeded once and left in place so the autoincrement ids (and
     * therefore the captured markup) stay byte-stable across runs; the names
     * are namespaced so re-runs are a no-op.
     */
    private const AJAX_SEED_QUIZ_ID = 987100;
    private const AJAX_SEED_QUIZ = 'Parity Ajax Fixture Quiz';
    private const AJAX_Q_TF_ID = 987101;
    private const AJAX_Q_MC_ID = 987102;

    /**
     * fixture name => [POST key, POST value] for the five admin AJAX POST
     * bodies (T4.2). The *-all variants drive the allthequestions branch,
     * the -quiz variants the per-quiz branch.
     */
    private const AJAX_SPECS = [
        'ajax-usersQuiz' => ['usersQuiz', self::AJAX_SEED_QUIZ],
        'ajax-usersAll' => ['usersAll', self::AJAX_SEED_QUIZ],
        'ajax-questionsQuiz-quiz' => ['questionsQuiz', self::AJAX_SEED_QUIZ],
        'ajax-editaquestion-quiz' => ['editaquestion', self::AJAX_SEED_QUIZ],
        'ajax-deleteSomeQuestions-quiz' => ['deleteSomeQuestions', self::AJAX_SEED_QUIZ],
        'ajax-questionsQuiz-all' => ['questionsQuiz', 'allthequestions'],
        'ajax-editaquestion-all' => ['editaquestion', 'allthequestions'],
        'ajax-deleteSomeQuestions-all' => ['deleteSomeQuestions', 'allthequestions'],
    ];

    private static string $base;
    private static string $anonJar;
    private static string $authJar;
    /** @var resource|null */
    private static $server = null;
    private static ?PDO $pdo = null;
    private static int $defaultQuizId = 0;
    /** @var array<string, string> page => rendered html, filled during minting */
    private static array $rendered = [];

    public static function setUpBeforeClass(): void
    {
        if (!is_dir(__DIR__ . '/fixtures')) {
            mkdir(__DIR__ . '/fixtures', 0777, true);
        }

        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$anonJar = tempnam(sys_get_temp_dir(), 'parity_anon_');
        self::$authJar = tempnam(sys_get_temp_dir(), 'parity_auth_');

        $docroot = dirname(__DIR__);
        // exec: make the built-in server replace the sh -c wrapper so
        // proc_get_status() reports the real server PID for teardown.
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

        $stmt = self::$pdo->query('SELECT id FROM quizes WHERE set_default=1 ORDER BY id LIMIT 1');
        $row = $stmt->fetch();
        if ($row === false) {
            self::markTestSkipped('No default quiz configured; cannot drive quiz/result flows.');
        }
        self::$defaultQuizId = (int) $row['id'];

        // Idempotent runs: a previously killed suite may have left rows behind.
        self::$pdo->prepare('DELETE FROM quiz_takers WHERE username IN (?, ?)')
            ->execute([self::QUIZ_ROLL, self::RESULT_ROLL]);

        [$status] = self::request('POST', 'login_check.php', [
            'login' => self::ADMIN_USER,
            'password' => self::ADMIN_PASS,
        ], self::$authJar);
        self::assertSame(302, $status, 'admin login should redirect');

        // Self-seeding: mint any missing fixture now, caching the renders so
        // each page is produced exactly once per suite run.
        foreach (self::PAGES as $page) {
            $fixture = __DIR__ . '/fixtures/snapshot-' . $page . '.html';
            if (!is_file($fixture)) {
                file_put_contents($fixture, self::renderPage($page));
            }
        }

        self::seedAjaxFixtureRows();
        foreach (array_keys(self::AJAX_SPECS) as $name) {
            $fixture = __DIR__ . '/fixtures/' . $name . '.html';
            if (!is_file($fixture)) {
                file_put_contents($fixture, self::renderAjaxBody($name));
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$pdo !== null) {
            $cleanup = self::$pdo->prepare('DELETE FROM quiz_takers WHERE username IN (?, ?)');
            $cleanup->execute([self::QUIZ_ROLL, self::RESULT_ROLL]);

            // Remove the T4.2 AJAX seed rows so suites asserting absolute
            // table counts (DbConnectTest) see an untouched database. The
            // ids are fixed constants, so the next run reseeds identical
            // bytes and the committed fixtures keep matching.
            foreach ([
                'DELETE FROM quiz_takers WHERE quiz_id = ' . self::AJAX_SEED_QUIZ_ID,
                'DELETE FROM answers WHERE quiz_id = ' . self::AJAX_SEED_QUIZ_ID,
                'DELETE FROM questions WHERE quiz_id = ' . self::AJAX_SEED_QUIZ_ID,
                'DELETE FROM quizes WHERE id = ' . self::AJAX_SEED_QUIZ_ID,
            ] as $sql) {
                self::$pdo->exec($sql);
            }
        }
        if (is_resource(self::$server)) {
            // proc_terminate alone leaves the built-in server running (it
            // ignores SIGTERM while idle-watching sockets in some setups),
            // which wedges any pipe waiting on our output.
            $status = proc_get_status(self::$server);
            if (!empty($status['pid'])) {
                exec('kill -9 ' . (int) $status['pid'] . ' >/dev/null 2>&1');
            }
            proc_close(self::$server);
        }
        @unlink(self::$anonJar);
        @unlink(self::$authJar);
        self::$pdo = null;
    }

    /**
     * @dataProvider pageProvider
     */
    public function testWrapperRegionsMatchCommittedSnapshot(string $page): void
    {
        $fixture = __DIR__ . '/fixtures/snapshot-' . $page . '.html';
        self::assertFileExists($fixture, "$page fixture should exist (self-seeding failed)");

        $expected = (string) file_get_contents($fixture);
        $actual = self::$rendered[$page] ?? self::renderPage($page);

        // Wrapper regions only: dynamic middles (random question order,
        // DB-driven lists, csrf tokens) are out of scope by design.
        $expectedRegions = self::extractWrappers($expected);
        $actualRegions = self::extractWrappers($actual);

        self::assertSame(
            array_keys($expectedRegions),
            array_keys($actualRegions),
            "$page: wrapper region inventory changed"
        );
        foreach ($expectedRegions as $region => $expectedBytes) {
            self::assertSame(
                self::normalize($expectedBytes),
                self::normalize($actualRegions[$region]),
                "$page: '$region' region diverged from committed snapshot"
            );
        }

        self::assertTargetedInvariants($page, $actual);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function pageProvider(): iterable
    {
        foreach (self::PAGES as $page) {
            yield $page => [$page];
        }
    }

    /**
     * Idempotent seed of the rows behind the five AJAX renderer bodies.
     * Rows are inserted once and never deleted so ids stay stable and the
     * committed fixtures keep matching byte for byte.
     */
    private static function seedAjaxFixtureRows(): void
    {
        $exists = self::$pdo->prepare('SELECT id FROM quizes WHERE id = :id');
        $exists->execute(['id' => self::AJAX_SEED_QUIZ_ID]);
        if ($exists->fetch() !== false) {
            return;
        }

        self::$pdo->prepare(
            'INSERT INTO quizes (id, quiz_name, total_questions, display_questions, time_allotted, set_default)
             VALUES (:id, :name, 2, 2, 60, 0)'
        )->execute(['id' => self::AJAX_SEED_QUIZ_ID, 'name' => self::AJAX_SEED_QUIZ]);

        $stmtQ = self::$pdo->prepare(
            'INSERT INTO questions (id, quiz_id, question, code, code_type, type)
             VALUES (:id, :quizId, :question, :code, :codeType, :type)'
        );
        $stmtQ->execute([
            'id' => self::AJAX_Q_TF_ID,
            'quizId' => self::AJAX_SEED_QUIZ_ID,
            'question' => 'Parity fixture: what is 2+2?',
            'code' => '',
            'codeType' => '',
            'type' => 'tf',
        ]);
        $stmtQ->execute([
            'id' => self::AJAX_Q_MC_ID,
            'quizId' => self::AJAX_SEED_QUIZ_ID,
            'question' => "Parity fixture: which one is O'Brien's?",
            'code' => '<?php echo "parity"; ?>',
            'codeType' => 'php',
            'type' => 'mc',
        ]);

        $stmtA = self::$pdo->prepare(
            'INSERT INTO answers (quiz_id, question_id, answer, correct) VALUES (:quizId, :qid, :answer, :correct)'
        );
        foreach ([['True', 1], ['False', 0]] as [$answer, $correct]) {
            $stmtA->execute([
                'quizId' => self::AJAX_SEED_QUIZ_ID,
                'qid' => self::AJAX_Q_TF_ID,
                'answer' => $answer,
                'correct' => $correct,
            ]);
        }
        foreach ([["parityA O'Brien", 1], ['parityB', 0], ['parityC', 0], ['parityD', 0]] as [$answer, $correct]) {
            $stmtA->execute([
                'quizId' => self::AJAX_SEED_QUIZ_ID,
                'qid' => self::AJAX_Q_MC_ID,
                'answer' => $answer,
                'correct' => $correct,
            ]);
        }

        $stmtT = self::$pdo->prepare(
            "INSERT INTO quiz_takers (username, percentage, date_time, quiz_id, duration, marks)
             VALUES (:username, :percentage, '2026-01-02 03:04:05', :quizId, :duration, :marks)"
        );
        $stmtT->execute(['username' => 'parity_ajax_a', 'percentage' => '100', 'quizId' => self::AJAX_SEED_QUIZ_ID, 'duration' => 5, 'marks' => 10]);
        $stmtT->execute(['username' => 'parity_ajax_b', 'percentage' => '80', 'quizId' => self::AJAX_SEED_QUIZ_ID, 'duration' => 9, 'marks' => 8]);
    }

    private static ?string $ajaxToken = null;

    /**
     * Render one of the five admin AJAX POST bodies through the live server.
     */
    private static function renderAjaxBody(string $fixtureName): string
    {
        if (self::$ajaxToken === null) {
            [, , $adminHtml] = self::request('GET', 'admin.php', [], self::$authJar);
            self::assertSame(
                1,
                preg_match('/name="csrf_token" value="([^"]+)"/', $adminHtml, $m),
                'admin page must expose a csrf token for AJAX fixtures'
            );
            self::$ajaxToken = $m[1];
        }

        [$key, $value] = self::AJAX_SPECS[$fixtureName];
        [$status, , $body] = self::request('POST', 'admin.php', [
            $key => $value,
            'csrf_token' => self::$ajaxToken,
        ], self::$authJar);
        self::assertSame(200, $status, "$fixtureName should render an AJAX body (got $status)");

        return $body;
    }

    /**
     * @dataProvider ajaxProvider
     */
    public function testAjaxBodyMatchesCommittedSnapshot(string $fixtureName): void
    {
        $fixture = __DIR__ . '/fixtures/' . $fixtureName . '.html';
        self::assertFileExists($fixture, "$fixtureName fixture should exist (self-seeding failed)");

        $expected = (string) file_get_contents($fixture);
        $actual = self::renderAjaxBody($fixtureName);

        // Same normalization contract as the wrapper regions: whitespace
        // runs collapse, script elements vanish; everything else must match.
        self::assertSame(
            self::normalize($expected),
            self::normalize($actual),
            "$fixtureName: AJAX body diverged from committed snapshot"
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function ajaxProvider(): iterable
    {
        foreach (array_keys(self::AJAX_SPECS) as $name) {
            yield $name => [$name];
        }
    }

    private static function renderPage(string $page): string
    {
        if (isset(self::$rendered[$page])) {
            return self::$rendered[$page];
        }

        switch ($page) {
            case 'index':
            case 'login':
                [$status, , $body] = self::request('GET', $page . '.php');
                break;
            case 'admin':
                [$status, , $body] = self::request('GET', 'admin.php', [], self::$authJar);
                break;
            case 'quiz':
                [$status, , $body] = self::request('POST', 'quiz.php', ['rollno' => self::QUIZ_ROLL]);
                break;
            case 'result':
                [$status, , $body] = self::request('POST', 'result.php', [
                    'total_ques' => '2',
                    'rollno' => self::RESULT_ROLL,
                    'quizID' => (string) self::$defaultQuizId,
                ]);
                break;
            default:
                throw new RuntimeException("unknown page $page");
        }

        self::assertSame(200, $status, "$page should render (got $status)");
        return self::$rendered[$page] = $body;
    }

    /**
     * @return array<string, string> region name => raw bytes
     */
    private static function extractWrappers(string $html): array
    {
        $regions = [];

        if (preg_match('/<!doctype.*?<\/head>/is', $html, $m) === 1) {
            $regions['head'] = $m[0];
        }
        if (preg_match('/<div id="head"[^>]*>\s*<img[^>]*>\s*<\/div>/i', $html, $m) === 1) {
            $regions['banner'] = $m[0];
        }
        if (preg_match('/<div id="footer".*?<\/table>\s*<\/div>/is', $html, $m) === 1) {
            $regions['footer'] = $m[0];
        }

        return $regions;
    }

    /**
     * Normalization contract documented in the class docblock.
     */
    private static function normalize(string $html): string
    {
        // viewport meta added by T4.1
        $html = preg_replace('/<meta name="viewport"[^>]*>/i', '', $html) ?? '';
        // script elements moved/kept per T4.5 (inline, src, and json islands)
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? '';
        // legacy ordering of charset/title unified by the shared head partial
        $html = preg_replace('/<meta charset="utf-8">/i', '', $html) ?? '';
        $html = preg_replace('/<title>.*?<\/title>/is', '', $html) ?? '';
        // comments carry no rendering semantics (faviconit banner text etc.)
        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? '';
        // legacy divergence folded into the canonical opener
        $html = preg_replace('/<!doctype html>/i', '<!DOCTYPE html>', $html) ?? '';
        $html = preg_replace('/<html(?:\s+lang="en")?>/i', '<html lang="en">', $html) ?? '';
        $html = preg_replace('/\s+/', ' ', $html) ?? '';

        return trim($html);
    }

    /**
     * Targeted assertions that tighten as the refactor lands: each one is
     * skipped until the corresponding artifact exists, then enforced forever.
     */
    private static function assertTargetedInvariants(string $page, string $html): void
    {
        if (str_contains($html, 'name="viewport"')) {
            self::assertSame(
                1,
                preg_match_all('/<meta name="viewport" content="width=device-width, initial-scale=1">/', $html),
                "$page: canonical viewport meta expected exactly once"
            );
        }

        self::assertSame(
            1,
            preg_match('/<title>[^<]*<\/title>/', $html),
            "$page: exactly one title element"
        );
        self::assertMatchesRegularExpression(
            '/<title>[^< ][^<]*<\/title>/',
            $html,
            "$page: title text must be present"
        );
        self::assertStringContainsString('<meta charset="utf-8">', $html, "$page: charset meta present");
        self::assertStringContainsString(
            'img/faviconit/favicon-195.png',
            $html,
            "$page: favicon partial wired"
        );

        // T6.1: the vendored SyntaxHighlighter 3 sh/ tree and CodeMirror 3
        // codemirror/ tree were deleted; pages must reference only the new
        // pinned vendors under assets/vendor/.
        if ($page === 'admin' || $page === 'quiz') {
            self::assertSame(
                0,
                preg_match_all('#(?:src|href)="(?:sh|codemirror)/#', $html),
                "$page: no deleted legacy vendor path may be referenced"
            );
            self::assertSame(
                1,
                substr_count($html, 'assets/vendor/prism-1.29.0/themes/prism.css'),
                "$page: prism theme stylesheet expected exactly once"
            );
            self::assertSame(
                1,
                substr_count($html, 'assets/vendor/prism-1.29.0/components/prism-core.min.js'),
                "$page: prism core script expected exactly once"
            );
            self::assertSame(
                1,
                substr_count($html, '<script src="assets/js/code-highlight.js"></script>'),
                "$page: brush-to-prism adapter expected exactly once"
            );
        }
        if ($page === 'admin') {
            self::assertSame(
                1,
                substr_count($html, 'assets/vendor/codemirror-5.65.16/lib/codemirror.js'),
                'admin.php: CodeMirror 5 library expected exactly once'
            );
        }

        if ($page === 'admin' && is_file(dirname(__DIR__) . '/assets/js/admin.js')) {
            self::assertSame(
                1,
                substr_count($html, '<script src="assets/js/admin.js"></script>'),
                'admin.php must load assets/js/admin.js exactly once'
            );
            [, , , $headers] = self::request('GET', 'admin.php', [], self::$authJar);
            $csp = $headers['content-security-policy'] ?? '';
            self::assertSame(
                1,
                preg_match("/script-src\s+([^;]+);?/", $csp, $cspParts),
                'CSP must declare a script-src directive'
            );
            self::assertStringNotContainsString(
                'unsafe-inline',
                (string) ($cspParts[1] ?? ''),
                'script-src must not allow inline scripts once admin.js exists'
            );
        }
    }

    /**
     * @param array<string, string> $post
     * @return array{0: int, 1: string, 2: string, 3: array<string, string>} [status, redirect, body, headers]
     */
    private static function request(string $method, string $path, array $post = [], ?string $jar = null): array
    {
        $args = [
            'curl', '-s', '-A', 'StructureParityTest/1.0',
            '-b', $jar ?? self::$anonJar, '-c', $jar ?? self::$anonJar,
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
        $headers = [];
        foreach (explode("\r\n", $headerBlock) as $line) {
            if (stripos($line, 'location:') === 0) {
                $redirect = trim((string) substr($line, 9));
            }
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
        }

        // strip the -w trailer
        $body = (string) preg_replace('/\n\d{3}\n?$/', '', $rest);

        return [$status, $redirect, $body, $headers];
    }
}
