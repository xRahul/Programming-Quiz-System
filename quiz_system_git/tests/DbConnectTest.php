<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DbConnectTest extends TestCase
{
    public function testConfigDefinesExpectedConstants(): void
    {
        $this->assertFileExists(__DIR__ . '/../lib/config.php');
        require_once __DIR__ . '/../lib/config.php';

        $this->assertSame('localhost', DB_HOST);
        $this->assertSame('debug', DB_NAME);
        $this->assertSame('quiz', DB_USER);
        $this->assertSame('quizpass', DB_PASS);
        $this->assertNotFalse(APP_ENV);
        $this->assertSame('Programming Quiz System', SITE_NAME);
        $this->assertSame('img/header.jpg', SITE_LOGO);
        $this->assertSame('', FOOTER_HTML);
    }

    public function testDbBootstrapProvidesPdoAndCountsQuizes(): void
    {
        require_once __DIR__ . '/../scripts/db.php';

        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertSame(2, (int) $pdo->query('SELECT COUNT(*) FROM quizes')->fetchColumn());
    }

    public function testBadCredentialsFailSanitized(): void
    {
        $root = dirname(__DIR__);
        $out = tempnam(sys_get_temp_dir(), 'dbout');
        $err = tempnam(sys_get_temp_dir(), 'dberr');

        $code = 'require ' . var_export($root . '/scripts/db.php', true) . ';';
        $cmd = sprintf(
            'DB_PASS=definitely-wrong APP_ENV=production %s -r %s >%s 2>%s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($code),
            escapeshellarg($out),
            escapeshellarg($err)
        );
        exec($cmd);

        $stdout = (string) file_get_contents($out);
        unlink($out);
        unlink($err);

        $this->assertStringContainsString('Database connection failed.', $stdout);
        $this->assertStringNotContainsString('Access denied', $stdout);
        $this->assertStringNotContainsString('Uncaught PDOException', $stdout);
        $this->assertStringNotContainsString('Stack trace', $stdout);
    }
}
