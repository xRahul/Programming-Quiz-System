<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/TestEnv.php';

/**
 * Tests that touch a live database authenticate as DB_USER/DB_PASS. Call at
 * the top of setUpBeforeClass (or a test method) to skip gracefully when no
 * credentials are configured (e.g. CI before secrets are provided).
 *
 * @throws \PHPUnit\Framework\SkippedWithMessageException when DB_PASS is empty
 */
function require_live_db_credentials(): void
{
    if ((string) getenv('DB_PASS') === '') {
        throw new PHPUnit\Framework\SkippedWithMessageException(
            'Live-DB credentials not configured: export DB_USER/DB_PASS (see CONTRIBUTING.md)'
        );
    }
}
