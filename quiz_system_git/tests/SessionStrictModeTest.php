<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SessionStrictModeTest extends TestCase
{
    public function testSecureSessionStartEnforcesStrictMode(): void
    {
        $out = tempnam(sys_get_temp_dir(), 'strict_out_');
        $err = tempnam(sys_get_temp_dir(), 'strict_err_');

        // Fresh-process probe: sessions may already exist in this PHPUnit
        // process, so the ini value is checked after secure_session_start()
        // runs in a child interpreter pinned to strict mode off up front.
        $probe = sprintf(
            'require %s; secure_session_start(); echo ini_get("session.use_strict_mode");',
            var_export(dirname(__DIR__) . '/lib/session.php', true)
        );
        $cmd = sprintf(
            '%s -d session.use_strict_mode=0 -r %s >%s 2>%s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($probe),
            escapeshellarg($out),
            escapeshellarg($err)
        );
        exec($cmd);

        $stdout = (string) file_get_contents($out);
        unlink($out);
        unlink($err);

        $this->assertSame(
            '1',
            $stdout,
            'secure_session_start() must turn on session.use_strict_mode before session_start()'
        );
    }
}
