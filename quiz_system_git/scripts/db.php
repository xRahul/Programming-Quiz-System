<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    error_log('[db] ' . $e->getMessage());
    if (APP_ENV === 'production') {
        die('Database connection failed.');
    }
    die('Database connection failed: ' . $e->getMessage());
}
