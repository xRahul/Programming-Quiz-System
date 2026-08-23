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
            // Transitional until Phase 3 migrations land: legacy INSERTs omit
            // columns (admin.php create-question, quiz.php, addNewQuiz.php) and
            // STRICT_TRANS_TABLES fatals them. Relax to pre-migration semantics.
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode = REPLACE(@@sql_mode,'STRICT_TRANS_TABLES','')",
        ]
    );
} catch (PDOException $e) {
    error_log('[db] ' . $e->getMessage());
    if (APP_ENV === 'production') {
        die('Database connection failed.');
    }
    die('Database connection failed: ' . $e->getMessage());
}
