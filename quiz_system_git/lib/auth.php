<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';

/**
 * Hardened admin gate: ensures an active session, a logged-in username,
 * and that the username still exists in the admins table.
 * Redirects to login.php and exits on failure; returns the admin username
 * on success and populates $login_session for legacy code.
 */
function require_admin(): string
{
    secure_session_start();

    global $pdo;
    if (!$pdo instanceof PDO) {
        require_once __DIR__ . '/../scripts/db.php';
    }

    $redirect = static function (): never {
        header('Location: login.php?user_msg=' . urlencode('Please log in to access that page.'));
        exit;
    };

    $username = $_SESSION['login_username'] ?? '';
    if (!is_string($username) || $username === '') {
        $redirect();
    }

    $stmt = $pdo->prepare('SELECT username FROM admins WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $row = $stmt->fetch();

    if (!$row) {
        unset($_SESSION['login_username']);
        $redirect();
    }

    $GLOBALS['login_session'] = (string) $row['username'];

    return $row['username'];
}
