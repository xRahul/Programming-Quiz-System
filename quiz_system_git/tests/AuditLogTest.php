<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * T5.3 audit log: destructive actions record actor+action, and the
 * auditRecent viewer endpoint renders escaped rows.
 */
final class AuditLogTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 8102;
    private const ADMIN_USER = 'admin';
    private const ADMIN_PASS = '12345';

    private const SEED_QUIZ_ID = 987500;

    private static string $base;
    private static string $authJar;
    /** @var resource|null */
    private static $server = null;
    private static ?PDO $pdo = null;
    private static string $probeTag;

    public static function setUpBeforeClass(): void
    {
        self::$base = sprintf('http://%s:%d', self::HOST, self::PORT);
        self::$authJar = tempnam(sys_get_temp_dir(), 'audit_jar_');
        self::$probeTag = 'audit_probe_' . bin2hex(random_bytes(4));

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

        // stale rows from a killed earlier run
        self::cleanupSeed();

        self::$pdo->prepare(
            'INSERT INTO quizes (id, quiz_name, total_questions, display_questions, time_allotted, set_default)
             VALUES (:id, :name, 1, 1, 30, 0)'
        )->execute(['id' => self::SEED_QUIZ_ID, 'name' => 'Audit Log Fixture Quiz']);
        self::$pdo->prepare(
            "INSERT INTO quiz_takers (username, percentage, date_time, quiz_id, duration, marks)
             VALUES ('audit_taker', '0', '2026-01-02 03:04:05', :quizId, 5, 1)"
        )->execute(['quizId' => self::SEED_QUIZ_ID]);

        // a hostile detail row proving the viewer escapes on output
        self::$pdo->prepare(
            "INSERT INTO audit_log (actor, action, detail) VALUES ('admin', :tag, '<script>alert(1)</script>')"
        )->execute(['tag' => self::$probeTag]);

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
        @unlink(self::$authJar);
        self::$pdo = null;
    }

    private static function cleanupSeed(): void
    {
        self::$pdo->exec('DELETE FROM quiz_takers WHERE quiz_id = ' . self::SEED_QUIZ_ID);
        self::$pdo->exec('DELETE FROM quizes WHERE id = ' . self::SEED_QUIZ_ID);
        self::$pdo->prepare('DELETE FROM audit_log WHERE detail LIKE :marker OR action = :tag')
            ->execute(['marker' => '%' . self::SEED_QUIZ_ID . '%', 'tag' => self::$probeTag]);
    }

    public function testClearResultWritesAuditRowWithActorAndAction(): void
    {
        $before = (int) self::$pdo->query('SELECT COUNT(*) FROM audit_log')->fetchColumn();

        [$status, , $body] = self::request('POST', 'admin.php', [
            'clearResult' => (string) self::SEED_QUIZ_ID,
            'csrf_token' => self::token(),
        ]);
        $this->assertSame(200, $status);
        $this->assertStringContainsString('Result has been cleared', $body);

        $stmt = self::$pdo->prepare(
            'SELECT actor, action, detail FROM audit_log WHERE detail LIKE :marker ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['marker' => '%' . self::SEED_QUIZ_ID . '%']);
        $row = $stmt->fetch();

        $this->assertNotFalse($row, 'clear_result must write an audit row');
        $this->assertSame(self::ADMIN_USER, $row['actor']);
        $this->assertSame('clear_result', $row['action']);
        $this->assertStringContainsString((string) self::SEED_QUIZ_ID, $row['detail']);

        $this->assertSame($before + 1, (int) self::$pdo->query('SELECT COUNT(*) FROM audit_log')->fetchColumn());

        // the destructive action really happened
        $stmtCount = self::$pdo->prepare('SELECT COUNT(*) FROM quiz_takers WHERE quiz_id = :id');
        $stmtCount->execute(['id' => self::SEED_QUIZ_ID]);
        $this->assertSame(0, (int) $stmtCount->fetchColumn());
    }

    public function testAuditRecentViewerReturnsEscapedRows(): void
    {
        [, , $body] = self::request('POST', 'admin.php', [
            'auditRecent' => '1',
            'csrf_token' => self::token(),
        ]);

        $this->assertStringContainsString('clear_result', $body, 'viewer must list recorded actions');
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $body, 'viewer must escape details');
        $this->assertStringNotContainsString('<script>alert(1)', $body);
        $this->assertStringContainsString(self::ADMIN_USER, $body, 'viewer must show the actor column');
    }

    private static function token(): string
    {
        [, , $body] = self::request('GET', 'admin.php');
        self::assertSame(
            1,
            preg_match('/name="csrf_token" value="([^"]+)"/', $body, $m),
            'admin page must expose a csrf token'
        );

        return (string) $m[1];
    }

    /**
     * @param array<string, string>|null $post
     * @return array{0: int, 1: string, 2: string} [status, redirect, body]
     */
    private static function request(string $method, string $path, ?array $post = null): array
    {
        $args = [
            'curl', '-s', '--max-time', '10', '--max-redirs', '0',
            '-A', 'AuditLogTest/1.0',
            '-b', self::$authJar, '-c', self::$authJar,
            '-D', '-', '-w', "\n%{http_code}",
            '-X', $method,
            self::$base . '/' . $path,
        ];
        if ($post !== null) {
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
