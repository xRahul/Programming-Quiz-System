<?php

declare(strict_types=1);

/**
 * Single hardened session bootstrap shared by every entry point so all
 * sessions get identical cookie flags (session cookies, HttpOnly,
 * SameSite=Lax, Secure on HTTPS).
 */
function secure_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    // Must precede session_start(): strict mode only applies when set before
    // the session starts, and it stops PHP accepting attacker-supplied ids.
    ini_set('session.use_strict_mode', '1');
    session_start();
}
