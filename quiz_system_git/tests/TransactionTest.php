<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * T4.3 transactional guarantees for the multi-write flows.
 *
 * Mid-flow failures are forced by temporarily renaming the child table
 * (answers/questions) so a write past the first statement fails hard; the
 * handlers must roll back cleanly and leave zero partial state behind.
 */
final class TransactionTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8097;
    private const ADMIN_USER = 'admin';
    private const ADMIN_PASS = '12345';

    private const QUIZ_A = 987200;
    private const QUIZ_B = 987201;
    private const QID_A1 = 987210;
    private const QID_A2 = 987211;
    private const QID_A3 = 987212;
    private const QID_B1 = 987213;
    private const QID_B2 = 987214;

    private const MARKER = 'tx-marker-question';

    private static string $base;
    private static string $authJar;
    /** @var resource|null */
    private static $server = null;
    private static ?PDO $pdo = null;

    public static function setUpBeforeClass(): void
    {
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$authJar = tempnam(sys_get_temp_dir(), 'tx_jar_');

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

        // Defensive restore if a previous run died mid-failure-injection.
        self::restoreTable('answers_txguard', 'answers');
        self::restoreTable('questions_txguard', 'questions');

        [, $redirect] = self::request('POST', 'login_check.php', [
            'login' => self::ADMIN_USER,
            'password' => self::ADMIN_PASS,
        ], self::$authJar);
        self::assertNotSame('', $redirect, 'admin login should redirect');
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$pdo !== null) {
            foreach ([self::QID_A1, self::QID_A2, self::QID_A3, self::QID_B1, self::QID_B2] as $qid) {
                self::$pdo->prepare('DELETE FROM answers WHERE question_id = :q')->execute(['q' => $qid]);
                self::$pdo->prepare('DELETE FROM questions WHERE question_id = :q')->execute(['q' => $qid]);
            }
            self::$pdo->prepare('DELETE FROM questions WHERE question LIKE :marker')
                ->execute(['marker' => '%' . self::MARKER . '%']);
            self::$pdo->prepare('DELETE FROM quiz_takers WHERE quiz_id IN (?, ?)')
                ->execute([self::QUIZ_A, self::QUIZ_B]);
            self::$pdo->prepare('DELETE FROM quizes WHERE id IN (?, ?)')
                ->execute([self::QUIZ_A, self::QUIZ_B]);

            self::restoreTable('answers_txguard', 'answers');
            self::restoreTable('questions_txguard', 'questions');
        }
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        @unlink(self::$authJar);
        self::$pdo = null;
    }

    public function testCreateQuestionRollsBackCompletelyWhenAnswerInsertFails(): void
    {
        self::seedQuiz(self::QUIZ_A, 'Tx Quiz A', 0);

        self::renameTable('answers', 'answers_txguard');
        try {
            [$status, $redirect] = self::request('POST', 'admin.php', [
                'desc' => self::MARKER . ' create',
                'code_desc' => '',
                'prog-lang' => '',
                'answer1' => 'True',
                'answer2' => 'False',
                'type' => 'tf',
                'quizID' => (string) self::QUIZ_A,
                'iscorrect' => 'answer1',
                'csrf_token' => self::token(),
            ], self::$authJar);

            self::assertSame(302, $status, 'handler must fail gracefully with a redirect');
            self::assertStringContainsString(
                urlencode('Sorry, something went wrong while saving the question.'),
                $redirect,
                'error redirect must carry the safe message'
            );
            self::assertSame(
                0,
                self::countQuestionsLike('%' . self::MARKER . '%'),
                'question insert must be rolled back'
            );
            self::assertSame(0, self::quizTotal(self::QUIZ_A), 'total_questions must be unchanged');
        } finally {
            self::restoreTable('answers_txguard', 'answers');
        }
    }

    public function testEditQuestionRollsBackQuestionUpdateAndAnswerDelete(): void
    {
        self::seedQuiz(self::QUIZ_A, 'Tx Quiz A', 0);
        self::seedTfQuestion(self::QID_A1, self::QUIZ_A, 'original text');

        self::renameTable('answers', 'answers_txguard');
        try {
            [$status] = self::request('POST', 'editaquest.php', [
                'desc' => self::MARKER . ' edited',
                'code_desc' => '',
                'prog-lang' => '',
                'answer1' => 'True',
                'answer2' => 'False',
                'type' => 'tf',
                'quizID' => (string) self::QUIZ_A,
                'questionID' => (string) self::QID_A1,
                'iscorrect' => 'answer1',
                'csrf_token' => self::token(),
            ]);
            self::assertSame(302, $status, 'handler must fail gracefully with a redirect');
        } finally {
            self::restoreTable('answers_txguard', 'answers');
        }

        // Assert only after restoring the table so the checks themselves can
        // query it.
        self::assertSame(
            'original text',
            self::questionText(self::QID_A1),
            'question UPDATE must be rolled back'
        );
        self::assertSame(
            2,
            self::answerCount(self::QID_A1),
            'answer DELETE must be rolled back so answers survive'
        );
    }
    public function testSetBasedDeleteRemovesExactlyRequestedIdsAndDecrementsEachQuiz(): void
    {
        self::seedQuiz(self::QUIZ_A, 'Tx Quiz A', 3);
        self::seedQuiz(self::QUIZ_B, 'Tx Quiz B', 2);
        self::seedMcQuestion(self::QID_A1, self::QUIZ_A);
        self::seedMcQuestion(self::QID_A2, self::QUIZ_A);
        self::seedMcQuestion(self::QID_A3, self::QUIZ_A);
        self::seedMcQuestion(self::QID_B1, self::QUIZ_B);
        self::seedMcQuestion(self::QID_B2, self::QUIZ_B);

        [$status, $redirect] = self::request('POST', 'deleteSomeQues.php', [
            'total_ques' => '5',
            'qu1' => (string) self::QID_A1,
            'qu3' => (string) self::QID_A3,
            'qu4' => (string) self::QID_B1,
            'csrf_token' => self::token(),
        ], self::$authJar);

        self::assertSame(302, $status);
        self::assertStringContainsString(
            urlencode('Questions, 1, 3, 4,'),
            $redirect,
            'message must list exactly the deleted indexes'
        );

        foreach ([self::QID_A1, self::QID_A3, self::QID_B1] as $gone) {
            self::assertSame(0, self::questionCount($gone), "question $gone must be deleted");
            self::assertSame(0, self::answerCount($gone), "answers of $gone must be deleted");
        }
        foreach ([self::QID_A2, self::QID_B2] as $kept) {
            self::assertSame(1, self::questionCount($kept), "untouched question $kept must remain");
            self::assertSame(4, self::answerCount($kept), "untouched answers of $kept must remain");
        }

        self::assertSame(1, self::quizTotal(self::QUIZ_A), 'quiz A decremented by exactly its 2 deletions');
        self::assertSame(1, self::quizTotal(self::QUIZ_B), 'quiz B decremented by exactly its 1 deletion');
    }

    public function testDeleteQuizCascadeRollsBackWhenAChildDeleteFails(): void
    {
        self::seedQuiz(self::QUIZ_A, 'Tx Quiz A', 1);
        self::seedMcQuestion(self::QID_A1, self::QUIZ_A);

        self::renameTable('questions', 'questions_txguard');
        try {
            [$status, , $body] = self::request('POST', 'admin.php', [
                'deleteQuiz' => 'Tx Quiz A',
                'csrf_token' => self::token(),
            ], self::$authJar);

            self::assertSame(200, $status);
            self::assertStringContainsString(
                'Sorry, there was a problem deleting the /Tx Quiz A/ quiz.',
                $body
            );
            self::assertSame(1, self::quizExists(self::QUIZ_A), 'quiz row must survive the failed cascade');

            // Restore first so child rows are visible to FK-backed checks.
            self::restoreTable('questions_txguard', 'questions');
            self::assertSame(1, self::questionCount(self::QID_A1), 'question must survive the failed cascade');
        } finally {
            self::restoreTable('questions_txguard', 'questions');
        }
    }

    public function testDeleteQuizCascadeHappyPathReportsSuccessViaDmlRowCount(): void
    {
        self::seedQuiz(self::QUIZ_B, 'Tx Quiz B', 1);
        self::seedMcQuestion(self::QID_B2, self::QUIZ_B);

        [$status, , $body] = self::request('POST', 'admin.php', [
            'deleteQuiz' => 'Tx Quiz B',
            'csrf_token' => self::token(),
        ], self::$authJar);

        self::assertSame(200, $status);
        self::assertStringContainsString('Thanks! The quiz /Tx Quiz B/ has now been deleted.', $body);
        self::assertSame(0, self::quizExists(self::QUIZ_B));
        self::assertSame(0, self::questionCount(self::QID_B2), 'cascade must remove the quiz questions');
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

    private static function renameTable(string $from, string $to): void
    {
        if (!self::tableExists($from)) {
            return;
        }
        self::$pdo->exec("RENAME TABLE `$from` TO `$to`");
    }

    private static function restoreTable(string $from, string $to): void
    {
        if (self::tableExists($from) && !self::tableExists($to)) {
            self::$pdo->exec("RENAME TABLE `$from` TO `$to`");
        }
    }

    private static function tableExists(string $name): bool
    {
        $stmt = self::$pdo->prepare('SHOW TABLES LIKE :name');
        $stmt->execute(['name' => $name]);

        return $stmt->fetch() !== false;
    }

    private static function seedQuiz(int $id, string $name, int $totalQuestions): void
    {
        self::$pdo->prepare('DELETE FROM quiz_takers WHERE quiz_id = :id')->execute(['id' => $id]);
        self::$pdo->prepare('DELETE FROM answers WHERE quiz_id = :id')->execute(['id' => $id]);
        self::$pdo->prepare('DELETE FROM questions WHERE quiz_id = :id')->execute(['id' => $id]);
        self::$pdo->prepare('DELETE FROM quizes WHERE id = :id')->execute(['id' => $id]);
        self::$pdo->prepare(
            'INSERT INTO quizes (id, quiz_id, quiz_name, total_questions, display_questions, time_allotted, set_default)
             VALUES (:id, :id, :name, :total, 2, 60, 0)'
        )->execute(['id' => $id, 'name' => $name, 'total' => $totalQuestions]);
    }

    private static function seedTfQuestion(int $qid, int $quizId, string $text): void
    {
        self::$pdo->prepare('DELETE FROM answers WHERE question_id = :q')->execute(['q' => $qid]);
        self::$pdo->prepare('DELETE FROM questions WHERE question_id = :q')->execute(['q' => $qid]);
        self::$pdo->prepare(
            "INSERT INTO questions (id, quiz_id, question_id, question, code, code_type, type)
             VALUES (:qid, :quizId, :qid, :text, '', '', 'tf')"
        )->execute(['qid' => $qid, 'quizId' => $quizId, 'text' => $text]);
        $stmtA = self::$pdo->prepare(
            'INSERT INTO answers (quiz_id, question_id, answer, correct) VALUES (:quizId, :qid, :answer, :correct)'
        );
        $stmtA->execute(['quizId' => $quizId, 'qid' => $qid, 'answer' => 'True', 'correct' => '1']);
        $stmtA->execute(['quizId' => $quizId, 'qid' => $qid, 'answer' => 'False', 'correct' => '0']);
    }

    private static function seedMcQuestion(int $qid, int $quizId): void
    {
        self::$pdo->prepare('DELETE FROM answers WHERE question_id = :q')->execute(['q' => $qid]);
        self::$pdo->prepare('DELETE FROM questions WHERE question_id = :q')->execute(['q' => $qid]);
        self::$pdo->prepare(
            "INSERT INTO questions (id, quiz_id, question_id, question, code, code_type, type)
             VALUES (:qid, :quizId, :qid, :text, '', '', 'mc')"
        )->execute(['qid' => $qid, 'quizId' => $quizId, 'text' => 'tx mc question ' . $qid]);
        $stmtA = self::$pdo->prepare(
            'INSERT INTO answers (quiz_id, question_id, answer, correct) VALUES (:quizId, :qid, :answer, :correct)'
        );
        foreach (['a1', 'a2', 'a3', 'a4'] as $i => $answer) {
            $stmtA->execute([
                'quizId' => $quizId,
                'qid' => $qid,
                'answer' => $answer,
                'correct' => $i === 0 ? '1' : '0',
            ]);
        }
    }

    private static function countQuestionsLike(string $like): int
    {
        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM questions WHERE question LIKE :like');

        $stmt->execute(['like' => $like]);

        return (int) $stmt->fetchColumn();
    }

    private static function questionCount(int $qid): int
    {
        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM questions WHERE question_id = :q');
        $stmt->execute(['q' => $qid]);

        return (int) $stmt->fetchColumn();
    }

    private static function answerCount(int $qid): int
    {
        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM answers WHERE question_id = :q');
        $stmt->execute(['q' => $qid]);

        return (int) $stmt->fetchColumn();
    }

    private static function questionText(int $qid): string
    {
        $stmt = self::$pdo->prepare('SELECT question FROM questions WHERE question_id = :q LIMIT 1');
        $stmt->execute(['q' => $qid]);
        $row = $stmt->fetch();

        return $row === false ? '' : (string) $row['question'];
    }

    private static function quizTotal(int $quizId): int
    {
        $stmt = self::$pdo->prepare('SELECT total_questions FROM quizes WHERE id = :id');
        $stmt->execute(['id' => $quizId]);
        $row = $stmt->fetch();

        return $row === false ? -1 : (int) $row['total_questions'];
    }

    private static function quizExists(int $quizId): int
    {
        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM quizes WHERE id = :id');
        $stmt->execute(['id' => $quizId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string, string> $post
     * @return array{0: int, 1: string, 2: string} [status, redirect, body]
     */
    private static function request(string $method, string $path, array $post = [], ?string $jar = null): array
    {
        $args = [
            'curl', '-s', '--max-time', '10', '-A', 'TransactionTest/1.0',
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
