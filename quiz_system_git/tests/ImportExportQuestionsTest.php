<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * T5.4 JSON question bank import/export: export shape, round-trip fidelity,
 * strict validation (422 + zero rows on every malformed shape), the
 * -imported/-imported2 name-collision ladder, and audit rows for both
 * outcomes.
 */
final class ImportExportQuestionsTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8103;
    private const ADMIN_USER = 'admin';
    private const ADMIN_PASS = '12345';

    private const SEED_QUIZ_ID = 987600;
    private const SEED_QUIZ_NAME = 'Roundtrip Quiz';
    private const TF_QID = 987601;
    private const MC_QID = 987602;

    private static string $base;
    private static string $anonJar;
    private static string $authJar;
    /** @var resource|null */
    private static $server = null;
    private static ?PDO $pdo = null;
    /** body of the most recent request() call */
    private static string $lastBody = '';
    private static ?string $cachedToken = null;
    /** highest audit_log id present at class start; everything above is ours */
    private static int $auditFloorId = 0;

    public static function setUpBeforeClass(): void
    {
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$anonJar = tempnam(sys_get_temp_dir(), 'ie_anon_');
        self::$authJar = tempnam(sys_get_temp_dir(), 'ie_auth_');

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

        self::cleanupSeed();
        self::$auditFloorId = (int) self::$pdo
            ->query('SELECT COALESCE(MAX(id), 0) FROM audit_log')
            ->fetchColumn();
        self::seed();

        [, $redirect] = self::request('POST', 'login_check.php', [
            'login' => self::ADMIN_USER,
            'password' => self::ADMIN_PASS,
        ]);
        self::assertStringContainsString('admin.php', (string) $redirect, 'admin login should succeed');
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$pdo !== null) {
            self::cleanupSeed();
        }
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        @unlink(self::$anonJar);
        @unlink(self::$authJar);
        self::$pdo = null;
    }

    // ---------- fixtures ----------

    private static function seed(): void
    {
        self::$pdo->prepare(
            'INSERT INTO quizes (id, quiz_name, total_questions, display_questions, time_allotted, set_default)
             VALUES (:id, :name, 2, 2, 45, 0)'
        )->execute(['id' => self::SEED_QUIZ_ID, 'name' => self::SEED_QUIZ_NAME]);

        $stmtQ = self::$pdo->prepare(
            'INSERT INTO questions (id, quiz_id, question, code, code_type, type)
             VALUES (:id, :quizId, :question, :code, :codeType, :type)'
        );
        $stmtQ->execute([
            'id' => self::TF_QID,
            'quizId' => self::SEED_QUIZ_ID,
            'question' => 'Roundtrip: is 2+2 four?',
            'code' => '',
            'codeType' => '',
            'type' => 'tf',
        ]);
        $stmtQ->execute([
            'id' => self::MC_QID,
            'quizId' => self::SEED_QUIZ_ID,
            'question' => "Roundtrip: which prints O'Brien?",
            'code' => '<?php echo "roundtrip"; ?>',
            'codeType' => 'php',
            'type' => 'mc',
        ]);

        $stmtA = self::$pdo->prepare(
            'INSERT INTO answers (quiz_id, question_id, answer, correct) VALUES (:quizId, :qid, :answer, :correct)'
        );
        foreach ([['True', 1], ['False', 0]] as [$answer, $correct]) {
            $stmtA->execute(['quizId' => self::SEED_QUIZ_ID, 'qid' => self::TF_QID, 'answer' => $answer, 'correct' => $correct]);
        }
        foreach ([['rtA', 0], ['rtB', 1], ['rtC', 0]] as [$answer, $correct]) {
            $stmtA->execute(['quizId' => self::SEED_QUIZ_ID, 'qid' => self::MC_QID, 'answer' => $answer, 'correct' => $correct]);
        }
    }

    private static function cleanupSeed(): void
    {
        // every quiz this suite could have created: the seed plus its imports
        $stmt = self::$pdo->prepare('SELECT id FROM quizes WHERE id = :id OR quiz_name LIKE :pattern');
        $stmt->execute(['id' => self::SEED_QUIZ_ID, 'pattern' => self::SEED_QUIZ_NAME . '-imported%']);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($ids as $id) {
            foreach (
                [
                    'DELETE FROM quiz_takers WHERE quiz_id = ' . (int) $id,
                    'DELETE FROM answers WHERE quiz_id = ' . (int) $id,
                    'DELETE FROM questions WHERE quiz_id = ' . (int) $id,
                    'DELETE FROM quizes WHERE id = ' . (int) $id,
                ] as $sql
            ) {
                self::$pdo->exec($sql);
            }
        }

        self::$pdo->prepare('DELETE FROM audit_log WHERE action = ? AND id > ?')
            ->execute(['import_questions', self::$auditFloorId]);
    }

    // ---------- tests ----------

    public function testUnauthenticatedRequestsAreRedirectedToLogin(): void
    {
        [$status, $redirect] = self::request('GET', 'export_questions.php?quiz=' . self::SEED_QUIZ_ID, null, self::$anonJar);
        $this->assertSame(302, $status);
        $this->assertStringContainsString('login.php', (string) $redirect);

        [$status, $redirect] = self::request('POST', 'import_questions.php', [], self::$anonJar);
        $this->assertSame(302, $status);
        $this->assertStringContainsString('login.php', (string) $redirect);
    }

    public function testExportShapeMatchesContract(): void
    {
        [$status] = self::request('GET', 'export_questions.php?quiz=' . self::SEED_QUIZ_ID);

        $this->assertSame(200, $status);
        $data = json_decode(self::$lastBody, true);
        $this->assertIsArray($data);
        $this->assertSame(
            ['name' => self::SEED_QUIZ_NAME, 'time_allotted' => 45, 'display_questions' => 2],
            $data['quiz']
        );

        $this->assertCount(2, $data['questions']);

        $tf = $data['questions'][0];
        $this->assertSame(
            [
                'question' => 'Roundtrip: is 2+2 four?',
                'type' => 'tf',
                'code' => '',
                'code_type' => '',
                'answers' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            $tf
        );

        $mc = $data['questions'][1];
        $this->assertSame('mc', $mc['type']);
        $this->assertSame('<?php echo "roundtrip"; ?>', $mc['code']);
        $this->assertSame('php', $mc['code_type']);
        $this->assertSame(
            [
                ['text' => 'rtA', 'correct' => false],
                ['text' => 'rtB', 'correct' => true],
                ['text' => 'rtC', 'correct' => false],
            ],
            $mc['answers']
        );
    }

    public function testImportRoundTripProducesIdenticalQuestionBank(): void
    {
        [$exportStatus] = self::request('GET', 'export_questions.php?quiz=' . self::SEED_QUIZ_ID);
        $this->assertSame(200, $exportStatus);
        $originalJson = self::$lastBody;

        [$status, $body] = $this->importString($originalJson);
        $this->assertSame(200, $status, 'valid import must return 200');

        $result = json_decode($body, true);
        $this->assertIsArray($result);
        $this->assertSame(2, $result['imported']);
        $this->assertGreaterThan(0, (int) $result['quizId']);
        $importedQuizId = (int) $result['quizId'];

        // imported quiz carries the -imported suffix
        $stmt = self::$pdo->prepare('SELECT quiz_name FROM quizes WHERE id = :id');
        $stmt->execute(['id' => $importedQuizId]);
        $this->assertSame(self::SEED_QUIZ_NAME . '-imported', $stmt->fetchColumn());

        // re-export the imported quiz: questions must be identical
        [$reStatus] = self::request('GET', 'export_questions.php?quiz=' . $importedQuizId);
        $this->assertSame(200, $reStatus);
        $reExport = json_decode(self::$lastBody, true);
        $origin = json_decode($originalJson, true);

        $this->assertSame($origin['questions'], $reExport['questions'], 'questions must survive the round-trip unchanged');
        $this->assertSame(2, $reExport['quiz']['display_questions'], 'metadata must survive the round-trip');
        $this->assertSame(45, $reExport['quiz']['time_allotted'], 'metadata must survive the round-trip');
    }

    /**
     * @dataProvider invalidPayloadProvider
     */
    public function testInvalidImportsAreRejectedWithZeroRows(string $label, string $payload): void
    {
        [$before] = self::tableCounts();

        [$status, $body] = $this->importString($payload);

        $this->assertSame(422, $status, "$label must be rejected with 422");
        $decoded = json_decode($body, true);
        $this->assertIsArray($decoded, "$label must respond with a JSON error body");
        $this->assertArrayHasKey('error', $decoded);

        [$after] = self::tableCounts();
        $this->assertSame($before, $after, "$label must not touch any table");
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidPayloadProvider(): iterable
    {
        yield 'malformed json' => ['malformed json', '{"quiz": {"name": "x", '];

        $missingQuestions = ['quiz' => ['name' => self::SEED_QUIZ_NAME, 'time_allotted' => 10, 'display_questions' => 5]];
        yield 'missing questions key' => ['missing questions key', (string) json_encode($missingQuestions)];

        $badQuiz = ['quiz' => ['time_allotted' => 10, 'display_questions' => 5], 'questions' => []];
        yield 'missing quiz name' => ['missing quiz name', (string) json_encode($badQuiz)];

        $nonIntTime = ['quiz' => ['name' => self::SEED_QUIZ_NAME, 'time_allotted' => 'half-hour', 'display_questions' => 5], 'questions' => []];
        yield 'non-numeric time_allotted' => ['non-numeric time_allotted', (string) json_encode($nonIntTime)];

        $unknownType = self::baseImport();
        $unknownType['questions'][0]['type'] = 'essay';
        yield 'unknown question type' => ['unknown question type', (string) json_encode($unknownType)];

        $tfThreeAnswers = self::baseImport();
        $tfThreeAnswers['questions'][0]['answers'][] = ['text' => 'Maybe', 'correct' => false];
        yield 'tf with three answers' => ['tf with three answers', (string) json_encode($tfThreeAnswers)];

        $mcTwoCorrect = self::baseImport();
        $mcTwoCorrect['questions'][1]['answers'][0]['correct'] = true;
        yield 'mc with two correct answers' => ['mc with two correct answers', (string) json_encode($mcTwoCorrect)];

        $mcFiveAnswers = self::baseImport();
        $mcFiveAnswers['questions'][1]['answers'][] = ['text' => 'rtE', 'correct' => false];
        $mcFiveAnswers['questions'][1]['answers'][] = ['text' => 'rtF', 'correct' => false];
        yield 'mc with five answers' => ['mc with five answers', (string) json_encode($mcFiveAnswers)];

        $noCorrect = self::baseImport();
        $noCorrect['questions'][0]['answers'][0]['correct'] = false;
        yield 'tf without any correct answer' => ['tf without any correct answer', (string) json_encode($noCorrect)];

        $emptyAnswerText = self::baseImport();
        $emptyAnswerText['questions'][0]['answers'][0]['text'] = '';
        yield 'empty answer text' => ['empty answer text', (string) json_encode($emptyAnswerText)];

        yield 'oversized payload over 2MB' => [
            'oversized payload over 2MB',
            json_encode(self::baseImport()) . str_repeat(' ', 3 * 1024 * 1024),
        ];
    }

    public function testNameCollisionLadderAppendsImportedSuffixes(): void
    {
        // self-contained: drop imports from earlier tests in this class first
        $stmt = self::$pdo->prepare('SELECT id FROM quizes WHERE id = :id OR quiz_name LIKE :pattern');
        $stmt->execute(['id' => self::SEED_QUIZ_ID, 'pattern' => self::SEED_QUIZ_NAME . '-imported%']);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            if ((int) $id === self::SEED_QUIZ_ID) {
                continue;
            }
            self::$pdo->exec('DELETE FROM answers WHERE quiz_id = ' . (int) $id);
            self::$pdo->exec('DELETE FROM questions WHERE quiz_id = ' . (int) $id);
            self::$pdo->exec('DELETE FROM quizes WHERE id = ' . (int) $id);
        }

        [$exportStatus] = self::request('GET', 'export_questions.php?quiz=' . self::SEED_QUIZ_ID);
        $this->assertSame(200, $exportStatus);
        $json = self::$lastBody;

        $this->importString($json);   // -> -imported
        $this->importString($json);   // -> -imported2

        $names = self::$pdo
            ->query("SELECT quiz_name FROM quizes WHERE quiz_name LIKE '" . self::SEED_QUIZ_NAME . "-imported%' ORDER BY id")
            ->fetchAll(PDO::FETCH_COLUMN);

        $this->assertSame(
            [self::SEED_QUIZ_NAME . '-imported', self::SEED_QUIZ_NAME . '-imported2'],
            array_slice($names, -2),
            'collision suffixes must climb the -imported, -imported2 ladder'
        );
    }

    public function testBothImportOutcomesAreAudited(): void
    {
        $stmt = self::$pdo->prepare(
            "SELECT detail FROM audit_log WHERE action = 'import_questions' AND id > :floor ORDER BY id"
        );
        $stmt->execute(['floor' => self::$auditFloorId]);
        $details = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $successes = array_filter($details, static fn (string $d) => str_contains($d, 'imported'));
        $rejections = array_filter($details, static fn (string $d) => str_contains($d, 'rejected'));

        $this->assertNotEmpty($successes, 'successful imports must be audited');
        $this->assertNotEmpty($rejections, 'rejected imports must be audited too');
    }

    // ---------- helpers ----------

    private static function baseImport(): array
    {
        return [
            'quiz' => ['name' => self::SEED_QUIZ_NAME, 'time_allotted' => 45, 'display_questions' => 2],
            'questions' => [
                [
                    'question' => 'Roundtrip: is 2+2 four?',
                    'type' => 'tf',
                    'code' => '',
                    'code_type' => '',
                    'answers' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false],
                    ],
                ],
                [
                    'question' => "Roundtrip: which prints O'Brien?",
                    'type' => 'mc',
                    'code' => '<?php echo "roundtrip"; ?>',
                    'code_type' => 'php',
                    'answers' => [
                        ['text' => 'rtA', 'correct' => false],
                        ['text' => 'rtB', 'correct' => true],
                        ['text' => 'rtC', 'correct' => false],
                    ],
                ],
            ],
        ];
    }

    /** @return array{0: string, 1: string} [table-count signature, unused slot] */
    private static function tableCounts(): array
    {
        $counts = [];
        foreach (['quizes', 'questions', 'answers'] as $table) {
            $counts[$table] = (int) self::$pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        }

        return [implode(',', $counts), ''];
    }

    /**
     * POSTs $contents as a multipart jsonfile upload.
     *
     * @return array{0: int, 1: string} [status, body]
     */
    private function importString(string $contents): array
    {
        $file = tempnam(sys_get_temp_dir(), 'ie_payload_');
        file_put_contents($file, $contents);

        try {
            [$status] = self::request(
                'POST',
                'import_questions.php',
                null,
                self::$authJar,
                ['-F', 'jsonfile=@' . $file, '-F', 'csrf_token=' . self::token()]
            );

            return [$status, self::$lastBody];
        } finally {
            @unlink($file);
        }
    }

    /**
     * @param array<string, string>|null $form urlencoded form body
     * @param list<string>|null $rawArgs verbatim extra curl args (multipart -F pairs)
     * @return array{0: int, 1: string, 2: string} [status, redirect, body]
     */
    private static function request(string $method, string $path, ?array $form = null, ?string $jar = null, ?array $rawArgs = null): array
    {
        $args = [
            'curl', '-s', '--max-time', '15', '--max-redirs', '0',
            '-A', 'ImportExportQuestionsTest/1.0',
            '-b', $jar ?? self::$authJar, '-c', $jar ?? self::$authJar,
            '-D', '-',
            '-w', "\n%{http_code}",
            '-X', $method,
            self::$base . '/' . $path,
        ];
        if ($form !== null && $form !== []) {
            array_push($args, '--data', http_build_query($form));
        }
        foreach ($rawArgs ?? [] as $rawArg) {
            $args[] = $rawArg;
        }

        $descriptors = [1 => ['pipe', 'w'], 2 => ['file', '/dev/null', 'w']];
        $proc = proc_open($args, $descriptors, $pipes);
        self::assertIsResource($proc);
        $out = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        proc_close($proc);

        $split = strpos($out, "\r\n\r\n");
        $headerBlock = $split === false ? '' : substr($out, 0, $split);
        $rest = $split === false ? $out : substr($out, $split + 4);

        $status = 0;
        $body = $rest;
        if (preg_match('/\n(\d{3})\n?$/', $rest, $m) === 1) {
            $status = (int) $m[1];
            $body = substr($rest, 0, -1 * strlen($m[0]));
        }

        $redirect = '';
        foreach (explode("\r\n", $headerBlock) as $line) {
            if (stripos($line, 'location:') === 0) {
                $redirect = trim((string) substr($line, 9));
            }
        }

        self::$lastBody = $body;

        return [$status, $redirect, $body];
    }

    private static function token(): string
    {
        if (self::$cachedToken !== null) {
            return self::$cachedToken;
        }

        self::request('GET', 'admin.php');
        self::assertSame(
            1,
            preg_match('/name="csrf_token" value="([^"]+)"/', self::$lastBody, $m),
            'admin page must expose a csrf token'
        );

        return self::$cachedToken = (string) $m[1];
    }
}
