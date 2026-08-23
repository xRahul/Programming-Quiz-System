<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Phase 3 schema-migration suite: proves database/migrations/* turn a legacy
 * debug.sql import into the utf8mb4/constrained/audited schema via
 * database/migrate.sh, and that the strict-mode defaults (005a) keep the
 * legacy INSERT flows alive once the db.php sql_mode shim is gone.
 */
final class MigrationTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8097;

    private static string $base;
    private static string $authJar;
    /** @var resource|null */
    private static $server = null;
    private static ?PDO $pdo = null;
    /** Question text of the HTTP strict-mode probe, for failure cleanup. */
    private static ?string $probeDesc = null;

    public static function setUpBeforeClass(): void
    {
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$authJar = tempnam(sys_get_temp_dir(), 'p3mig_jar_');

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

    // ---------------------------------------------------------------- helpers

    /**
     * Run a mysql CLI statement (against an optional database); the test fails
     * if the client exits non-zero.
     */
    private static function sql(string $statement, ?string $db = null): string
    {
        [$code, $out] = self::rawSql($statement, $db);
        if ($code !== 0) {
            self::fail("mysql exited $code: $out\n> $statement");
        }

        return $out;
    }

    /**
     * Run a mysql CLI statement without failing on error.
     * @return array{0: int, 1: string} [exit code, combined output]
     */
    private static function rawSql(string $statement, ?string $db = null): array
    {
        $cmd = 'mysql -N'
            . ($db !== null ? ' ' . escapeshellarg($db) : '')
            . ' -e ' . escapeshellarg($statement) . ' 2>&1';
        $out = [];
        $code = 0;
        exec($cmd, $out, $code);

        return [$code, implode("\n", $out)];
    }

    /** Fetch a single scalar via the mysql CLI (batch mode, no headers). */
    private static function scalar(string $select, string $db): int
    {
        return (int) trim(self::sql($select, $db));
    }

    /** Fetch a single raw string value via the mysql CLI. */
    private static function value(string $select, string $db): string
    {
        return trim(self::sql($select, $db));
    }

    /**
     * Import legacy database/debug.sql into a fresh scratch database, grant the
     * app user access (decode step connects with app credentials), hand the db
     * name to $fn, drop the database afterwards.
     *
     * @param callable(string):void $fn
     */
    private function withLegacyScratch(callable $fn): void
    {
        $db = 'debug_test_p3_' . bin2hex(random_bytes(4));
        self::sql("CREATE DATABASE `$db`");
        try {
            $importCmd = 'mysql ' . escapeshellarg($db) . ' < '
                . escapeshellarg(dirname(__DIR__) . '/database/debug.sql');
            [$importCode, $importOut] = self::shell($importCmd);
            self::assertSame(0, $importCode, 'legacy dump import failed: ' . $importOut);

            self::sql("GRANT ALL PRIVILEGES ON `$db`.* TO 'quiz'@'localhost'");

            $fn($db);
        } finally {
            self::rawSql("DROP DATABASE IF EXISTS `$db`");
        }
    }

    /**
     * Run database/migrate.sh against the named database.
     * @return array{0: int, 1: string} [exit code, output]
     */
    private static function runMigrate(string $db): array
    {
        return self::shell(sprintf(
            'DB_NAME=%s bash %s',
            escapeshellarg($db),
            escapeshellarg(dirname(__DIR__) . '/database/migrate.sh')
        ));
    }

    /**
     * @return array{0: int, 1: string} [exit code, combined output]
     */
    private static function shell(string $cmd): array
    {
        $out = [];
        $code = 0;
        exec($cmd . ' 2>&1', $out, $code);

        return [$code, implode("\n", $out)];
    }

    // ------------------------------------------------------------ fresh path

    public function testFreshImportMigratesFullyAndIsIdempotent(): void
    {
        $this->withLegacyScratch(function (string $db): void {
            // Entity-decode probe seeded BEFORE migration, decoded after.
            self::sql(
                "INSERT INTO answers (quiz_id, question_id, answer, correct) VALUES (1, 1, '&amp;', '0')",
                $db
            );

            [$code, $out] = self::runMigrate($db);
            $this->assertSame(0, $code, "first migrate.sh run failed:\n$out");

            // -- charset: all five tables utf8mb4 / utf8mb4_unicode_ci --------
            $tables = "('admins','answers','questions','quizes','quiz_takers')";
            $this->assertSame(5, $this->scalar(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME IN $tables
                   AND TABLE_COLLATION = 'utf8mb4_unicode_ci'",
                $db
            ), 'all five tables must be utf8mb4_unicode_ci');
            $this->assertSame(0, $this->scalar(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME IN $tables
                   AND CHARACTER_SET_NAME IS NOT NULL
                   AND CHARACTER_SET_NAME <> 'utf8mb4'",
                $db
            ), 'no text column may remain non-utf8mb4');

            // -- constraints: two UNIQUE keys ---------------------------------
            $this->assertSame(3, $this->scalar(
                "SELECT COUNT(*) FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = '$db'
                   AND INDEX_NAME IN ('uq_admins_username', 'uq_takers_user_quiz')
                   AND NON_UNIQUE = 0",
                $db
            ), 'uq_admins_username (1 col) + uq_takers_user_quiz (2 cols) must be unique indexes');

            // -- indexes: three named secondaries ------------------------------
            // (DISTINCT: information_schema.STATISTICS carries one row per
            // indexed column, and FK creation may add its own helper indexes.)
            $this->assertSame(3, $this->scalar(
                "SELECT COUNT(DISTINCT INDEX_NAME) FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = '$db' AND INDEX_NAME IN (
                     'idx_answers_question_id', 'idx_questions_quiz_id', 'idx_takers_quiz_id')",
                $db
            ), 'the three secondary indexes must exist');

            // -- foreign keys: four cascading ----------------------------------
            $this->assertSame(4, $this->scalar(
                "SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = '$db'
                   AND CONSTRAINT_NAME IN (
                       'fk_answers_quiz', 'fk_answers_question',
                       'fk_questions_quiz', 'fk_takers_quiz')
                   AND DELETE_RULE = 'CASCADE'",
                $db
            ), 'the four FKs must exist with ON DELETE CASCADE');

            // -- audit_log ------------------------------------------------------
            $this->assertSame(
                ['id', 'actor', 'action', 'detail', 'created_at'],
                array_map(
                    'strval',
                    explode("\n", self::sql(
                        "SELECT COLUMN_NAME FROM information_schema.COLUMNS
                         WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'audit_log'
                         ORDER BY ORDINAL_POSITION",
                        $db
                    ))
                ),
                'audit_log must have exactly the specified columns'
            );

            // -- 005a safe defaults on every NOT NULL column the fatal INSERTs hit
            $this->assertSame(6, $this->scalar(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = '$db'
                   AND (
                       (TABLE_NAME = 'questions' AND COLUMN_NAME = 'question_id')
                       OR (TABLE_NAME = 'quizes' AND COLUMN_NAME IN ('quiz_id', 'total_questions', 'set_default'))
                       OR (TABLE_NAME = 'quiz_takers' AND COLUMN_NAME IN ('marks', 'duration'))
                   )
                   AND COLUMN_DEFAULT = '0'",
                $db
            ), 'questions.question_id, quizes.quiz_id/total_questions/set_default, '
                . 'quiz_takers.marks/duration must default to 0');

            // -- entity decode: live decoded, backup kept ----------------------
            $this->assertSame(1, $this->scalar(
                "SELECT COUNT(*) FROM answers WHERE quiz_id = 1 AND question_id = 1 AND answer = '&'",
                $db
            ), "'&amp;' probe must be decoded to '&' in answers");
            $this->assertSame(1, $this->scalar(
                "SELECT COUNT(*) FROM answers_entitybak WHERE quiz_id = 1 AND question_id = 1 AND answer = '&amp;'",
                $db
            ), "original '&amp;' probe must survive in answers_entitybak");

            // -- dedupe kept lowest id, orphans purged, seeds sacred -----------
            $this->assertSame(13, $this->scalar(
                'SELECT COUNT(*) FROM quiz_takers',
                $db
            ), '14 legacy taker rows minus the one duplicate pair member = 13');
            $this->assertSame(0, $this->scalar('SELECT COUNT(*) FROM quiz_takers WHERE id = 13', $db),
                'dedupe must keep lowest id (7) and drop id 13');
            $this->assertSame(1, $this->scalar('SELECT COUNT(*) FROM quiz_takers WHERE id = 7', $db));
            $this->assertSame(0, $this->scalar(
                'SELECT COUNT(*) FROM answers a LEFT JOIN questions q ON a.question_id = q.id WHERE q.id IS NULL',
                $db
            ), 'orphaned answers must be purged before FKs');
            $this->assertSame(2, $this->scalar('SELECT COUNT(*) FROM quizes', $db));
            $this->assertSame(
                "LEVEL1(EASY)\nLEVEL2(HARD)",
                self::sql('SELECT quiz_name FROM quizes ORDER BY id', $db),
                'seed quiz names must survive unchanged'
            );
            $this->assertStringStartsWith('$2y$10$Fp6Ozh0DYIp2hoDQ08wqx.', self::value(
                'SELECT password FROM admins WHERE username = \'admin\'',
                $db
            ), 'admin bcrypt hash must survive unchanged');
            $this->assertSame(31, $this->scalar('SELECT COUNT(*) FROM questions', $db));

            // -- idempotency: second run applies nothing -----------------------
            $before = $this->scalar('SELECT COUNT(*) FROM schema_migrations', $db);
            $this->assertSame(7, $before, 'seven migrations must be recorded after the first run');
            [$code, $out] = self::runMigrate($db);
            $this->assertSame(0, $code, "second migrate.sh run failed:\n$out");
            $this->assertStringContainsString('applied=0', $out, 'second run must apply nothing');
            $this->assertSame($before, $this->scalar('SELECT COUNT(*) FROM schema_migrations', $db));
        });
    }

    // ------------------------------------------------------ constraint proofs

    public function testDuplicateTakerInsertRejectedByUniqueKey(): void
    {
        $this->withLegacyScratch(function (string $db): void {
            self::runMigrate($db);

            [$code, $out] = self::rawSql(
                "INSERT INTO quiz_takers (username, quiz_id, marks, percentage, date_time, duration)
                 VALUES ('1139113', 1, 0, '0', now(), 0)",
                $db
            );
            $this->assertNotSame(0, $code, 'duplicate (username, quiz_id) insert must fail');
            $this->assertStringContainsStringIgnoringCase('duplicate entry', $out);
        });
    }

    public function testForeignKeyViolationOnAnswersRejected(): void
    {
        $this->withLegacyScratch(function (string $db): void {
            self::runMigrate($db);

            [$code, $out] = self::rawSql(
                "INSERT INTO answers (quiz_id, question_id, answer, correct) VALUES (1, 999999, 'x', '0')",
                $db
            );
            $this->assertNotSame(0, $code, 'answer referencing missing question must fail');
            $this->assertStringContainsStringIgnoringCase('foreign key constraint fails', $out);
        });
    }

    // ------------------------------------------------------- strict-mode proof

    public function testStrictModeDefaultsSurviveHttpQuestionCreate(): void
    {
        $src = (string) file_get_contents(dirname(__DIR__) . '/scripts/db.php');
        $this->assertStringNotContainsString(
            'MYSQL_ATTR_INIT_COMMAND',
            $src,
            'transitional sql_mode shim must be removed from scripts/db.php'
        );

        // A plain app-style connection (no init command) must inherit strict mode.
        $probe = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $mode = (string) $probe->query('SELECT @@SESSION.sql_mode')->fetchColumn();
        $this->assertStringContainsString(
            'STRICT_TRANS_TABLES',
            $mode,
            'app connections must run under default strict sql_mode'
        );

        // The migrated main database must carry the 005a defaults.
        $defaults = $this->mainColumnDefaults();
        foreach ([
            'questions.question_id',
            'quizes.quiz_id',
            'quizes.total_questions',
            'quizes.set_default',
            'quiz_takers.marks',
            'quiz_takers.duration',
        ] as $key) {
            $this->assertSame('0', $defaults[$key] ?? null, "$key must have DEFAULT 0 in the live database");
        }

        self::ensureServer();
        self::login();
        $token = self::sessionToken();

        $desc = 'P3 STRICT PROBE ' . bin2hex(random_bytes(4));
        self::$probeDesc = $desc;
        [$status, $redirect] = self::request('POST', 'admin.php', [
            'desc' => $desc,
            'code_desc' => '',
            'prog-lang' => 'plain',
            'type' => 'tf',
            'quizID' => '1',
            'answer1' => 'True',
            'answer2' => 'False',
            'answer3' => '',
            'answer4' => '',
            'iscorrect' => 'answer1',
            'csrf_token' => $token,
        ]);

        $this->assertSame(
            302,
            $status,
            'create-question must not fatal under strict sql_mode (redirect expected)'
        );
        $this->assertStringContainsString('msg=', (string) $redirect);
        $this->assertStringContainsString('Thanks%2C+question+no.', (string) $redirect);

        $stmt = self::$pdo->prepare('SELECT id FROM questions WHERE question = :question LIMIT 1');
        $stmt->execute(['question' => $desc]);
        $probeId = $stmt->fetchColumn();
        $this->assertNotFalse($probeId, 'created question row must be present');

        $stmt = self::$pdo->prepare('SELECT COUNT(*) FROM answers WHERE question_id = :qid');
        $stmt->execute(['qid' => $probeId]);
        $this->assertSame(2, (int) $stmt->fetchColumn(), 'both tf answers must be stored');
    }

    protected function onNotSuccessfulTest(\Throwable $t): never
    {
        // Best-effort cleanup of the HTTP probe so failures do not leak rows.
        if (self::$probeDesc !== null && self::$pdo !== null) {
            try {
                $stmt = self::$pdo->prepare('SELECT id FROM questions WHERE question = :question');
                $stmt->execute(['question' => self::$probeDesc]);
                foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
                    self::$pdo->prepare('DELETE FROM answers WHERE question_id = :id')->execute(['id' => $id]);
                    self::$pdo->prepare('DELETE FROM questions WHERE id = :id')->execute(['id' => $id]);
                    self::$pdo->exec(
                        'UPDATE quizes SET total_questions = total_questions - 1 WHERE id = 1 AND total_questions > 0'
                    );
                }
            } catch (\Throwable) {
                // never mask the original failure
            }
        }
        parent::onNotSuccessfulTest($t);
    }

    /**
     * @return array<string, string> "table.column" => COLUMN_DEFAULT for the live DB
     */
    private function mainColumnDefaults(): array
    {
        $stmt = self::$pdo->query(
            "SELECT CONCAT(TABLE_NAME, '.', COLUMN_NAME) AS k, COLUMN_DEFAULT
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND (
                   (TABLE_NAME = 'questions' AND COLUMN_NAME = 'question_id')
                   OR (TABLE_NAME = 'quizes' AND COLUMN_NAME IN ('quiz_id', 'total_questions', 'set_default'))
                   OR (TABLE_NAME = 'quiz_takers' AND COLUMN_NAME IN ('marks', 'duration'))
               )"
        );
        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(string) $row['k']] = $row['COLUMN_DEFAULT'] === null ? '' : (string) $row['COLUMN_DEFAULT'];
        }

        return $map;
    }

    // --------------------------------------------------- server/http helpers

    private static function ensureServer(): void
    {
        if (self::$server !== null) {
            return;
        }

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
            [, , $body] = self::request('GET', 'login.php');
            if ($body !== '') {
                $ready = true;
                break;
            }
            usleep(100_000);
        }
        self::assertTrue($ready, 'PHP built-in server did not become ready on ' . self::$base);
    }

    private static function login(): void
    {
        @unlink(self::$authJar);
        touch(self::$authJar);
        [$status, $redirect] = self::request('POST', 'login_check.php', [
            'login' => 'admin',
            'password' => '12345',
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
            '-A', 'MigrationTest/1.0',
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
