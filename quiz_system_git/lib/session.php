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
    session_start();
}
