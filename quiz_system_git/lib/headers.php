<?php

declare(strict_types=1);

/**
 * Security headers shared by every page-rendering entry point.
 * Call once, before any output is sent.
 */
function send_security_headers(): void
{
    // style-src keeps 'unsafe-inline': the pages still ship legacy <style>
    // blocks and style="" attributes (scheduled for a later phase).
    // script-src is strict since T4.5 moved admin's JS to assets/js/admin.js;
    // the remaining tiny inline scripts on quiz/index/login/result are
    // inert-by-CSP until their own extraction task lands.
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:");
}
