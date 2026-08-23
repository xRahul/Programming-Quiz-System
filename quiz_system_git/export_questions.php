<?php

declare(strict_types=1);

    /*
    Short Programming Quiz Framework
        Copyright (C) 2014  Rahul Jain
    */

// T5.4 question-bank export. Read-only GET: require_admin() gates it, but no
// csrf_verify() -- nothing state-changing happens here (established rule for
// session-read-only exports).

    require_once __DIR__ . '/lib/auth.php';
    require_admin();

    require_once __DIR__ . '/lib/render.php';

    $jsonError = static function (int $status, string $message): never {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message]);
        exit;
    };

    $quizId = isset($_GET['quiz']) ? (int) $_GET['quiz'] : 0;
    if ($quizId <= 0) {
        $jsonError(404, 'Unknown quiz.');
    }

    $stmt = $pdo->prepare("SELECT id, quiz_name, total_questions, display_questions, time_allotted FROM quizes WHERE id = :id");
    $stmt->execute(['id' => $quizId]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($quiz === false) {
        $jsonError(404, 'Unknown quiz.');
    }

    $stmtQ = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = :id ORDER BY id");
    $stmtQ->execute(['id' => $quizId]);
    $questionRows = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

    $answersByQid = \App\fetch_answers_by_question_ids($pdo, array_column($questionRows, 'question_id'));

    $questions = array();
    foreach ($questionRows as $row) {
        $answers = array();
        foreach ($answersByQid[(int) $row['question_id']] ?? array() as $answerRow) {
            $answers[] = array(
                'text' => (string) $answerRow['answer'],
                'correct' => ((int) $answerRow['correct']) === 1,
            );
        }

        $questions[] = array(
            'question' => (string) $row['question'],
            'type' => (string) $row['type'],
            'code' => (string) $row['code'],
            'code_type' => (string) $row['code_type'],
            'answers' => $answers,
        );
    }

    $payload = array(
        'quiz' => array(
            'name' => (string) $quiz['quiz_name'],
            'time_allotted' => (int) $quiz['time_allotted'],
            'display_questions' => (int) $quiz['display_questions'],
        ),
        'questions' => $questions,
    );

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit();
