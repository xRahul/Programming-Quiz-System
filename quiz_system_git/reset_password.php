<?php

declare(strict_types=1);

    /*
    Short Programming Quiz Framework
        Copyright (C) 2014  Rahul Jain
    */

// T5.5 admin-assisted password reset: a logged-in admin rotates another
// admin's password (e.g. lost-credential recovery).

    require_once __DIR__ . '/lib/auth.php';
    require_admin();

    require_once __DIR__ . '/lib/csrf.php';
    csrf_verify();

    require_once __DIR__ . '/lib/audit.php';

 // $pdo is available via lib/auth.php -> scripts/db.php when not already up

    $back = static function (string $msg): never {
        header('location: admin.php?msg=' . urlencode($msg));
        exit();
    };

    $username = isset($_POST['username']) && is_string($_POST['username']) ? trim($_POST['username']) : '';
    $newPassword = isset($_POST['new_password']) && is_string($_POST['new_password']) ? $_POST['new_password'] : '';

    if ($username === '' || $newPassword === '') {
        $back('Sorry, but both the username and the new password are required.');
    }

    if (strlen($newPassword) < 6) {
        $back('Sorry, but the new password must be at least 6 characters long.');
    }

    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = :username");
    $stmt->execute(['username' => $username]);

    if ($stmt->fetch() === false) {
        $back('Sorry, but no admin named ' . $username . ' exists!');
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmtUpdate = $pdo->prepare("UPDATE admins SET password = :password WHERE username = :username");
    $stmtUpdate->execute(['password' => $hashedPassword, 'username' => $username]);

    audit_log('password_reset', $username);

    $back('Password reset successfully for ' . $username . '.');
