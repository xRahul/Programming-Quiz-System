<?php

declare(strict_types=1);

/**
 * Migration 005b helper: decode double-encoded HTML entities that legacy code
 * stored via htmlspecialchars(). Backs up every touched table into a
 * <table>_entitybak snapshot first (rerun-safe), then decodes only rows whose
 * value still contains '&'.
 *
 * Target database follows lib/config.php (honors DB_* environment overrides),
 * so this works against both the live debug DB and migration-test scratch DBs.
 */

require_once __DIR__ . '/../lib/config.php';

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

/** @var array<string, list<string>> $targets */
$targets = [
    'questions' => ['question', 'code'],
    'answers' => ['answer'],
    'quizes' => ['quiz_name'],
];

foreach ($targets as $table => $columns) {
    $bak = $table . '_entitybak';
    $pdo->exec("CREATE TABLE IF NOT EXISTS `$bak` LIKE `$table`");
    $pdo->exec("INSERT INTO `$bak` SELECT t.* FROM `$table` t WHERE t.id NOT IN (SELECT id FROM `$bak`)");
}

$decoded = 0;
foreach ($targets as $table => $columns) {
    foreach ($columns as $column) {
        $stmt = $pdo->query("SELECT id, `$column` AS v FROM `$table` WHERE `$column` LIKE '%&%'");
        $update = $pdo->prepare("UPDATE `$table` SET `$column` = :v WHERE id = :id");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $value = html_entity_decode((string) $row['v'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($value === (string) $row['v']) {
                continue;
            }
            $update->execute(['v' => $value, 'id' => $row['id']]);
            $decoded++;
        }
    }
}

echo "decode-stored-entities: {$decoded} value(s) updated\n";
