<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';

/**
 * CSRF token helpers: a per-session token embedded in every admin form and
 * XHR body, verified once on each state-changing (POST) request.
 * The token is minted lazily on first render and rotated on login
 * re-authentication by login_check.php.
 */

function csrf_token(): string
{
    secure_session_start();

    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

function csrf_verify(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $sessionToken = isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token'])
        ? $_SESSION['csrf_token']
        : '';
    $postedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';

    if ($sessionToken === '' || !hash_equals($sessionToken, $postedToken)) {
        http_response_code(403);
        echo 'Invalid request token.';
        exit;
    }
}
