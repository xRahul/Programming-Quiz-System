<?php

declare(strict_types=1);

/**
 * Security headers shared by every page-rendering entry point.
 * Call once, before any output is sent.
 */
function send_security_headers(): void
{
    // 'unsafe-inline' in CSP is required until T4.5 extracts inline JS/CSS;
    // tighten script-src/style-src there.
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:");
}
