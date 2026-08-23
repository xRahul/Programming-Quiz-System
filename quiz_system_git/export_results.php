<?php

declare(strict_types=1);

    /*
    Short Programming Quiz Framework
        Copyright (C) 2014  Rahul Jain
    */

// T5.2 results CSV export. Read-only GET: require_admin() gates it, but no
// csrf_verify() -- nothing state-changing happens here (established rule for
// session-read-only exports).

    require_once __DIR__ . '/lib/auth.php';
    require_admin();

 // $pdo is available via lib/auth.php -> scripts/db.php when not already up

    $quizId = isset($_GET['quiz']) ? (int) $_GET['quiz'] : 0;
    $scope = (($_GET['scope'] ?? 'top') === 'all') ? 'all' : 'top';

 //JSON errors for malformed requests; this endpoint is API-ish, not a page
    $jsonError = static function (int $status, string $message): never {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message]);
        exit;
    };

    if ($quizId <= 0) {
        $jsonError(404, 'Unknown quiz.');
    }

    $stmt = $pdo->prepare("SELECT id, quiz_name, total_questions, display_questions FROM quizes WHERE id = :id");
    $stmt->execute(['id' => $quizId]);
    $quiz = $stmt->fetch();

    if ($quiz === false) {
        $jsonError(404, 'Unknown quiz.');
    }

 //percentage denominator: display_questions when positive, else total_questions
    $displayQuestions = (int) $quiz['display_questions'];
    $totalQuestions = (int) $quiz['total_questions'];
    $divisor = $displayQuestions > 0 ? $displayQuestions : $totalQuestions;

    $sql = "SELECT username, marks, date_time, duration FROM quiz_takers WHERE quiz_id = :id ORDER BY marks DESC, duration ASC";
    if ($scope === 'top') {
        $sql .= " LIMIT 20";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $quizId]);

 //attachment filename carries a slug of the quiz name; only [a-z0-9-] survive
    $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $quiz['quiz_name'])), '-');
    if ($slug === '') {
        $slug = 'quiz';
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="results-' . $slug . '-' . date('Ymd') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['username', 'marks', 'percentage', 'date_time', 'duration']);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $marks = (int) $row['marks'];
        $percentage = $divisor > 0 ? round(($marks / $divisor) * 100, 2) : 0;

        fputcsv($out, [
            (string) $row['username'],
            (string) $row['marks'],
            (string) $percentage,
            (string) $row['date_time'],
            (string) $row['duration'],
        ]);
    }

    fclose($out);
    exit();
