<?php

declare(strict_types=1);

    /*
    Short Programming Quiz Framework
        Copyright (C) 2014  Rahul Jain
    */

// T5.4 question-bank import. State-changing POST: require_admin() first,
// csrf_verify() immediately after (established convention). The whole import
// is transactional; every validation failure answers 422 with a JSON error
// body and writes zero rows.

    require_once __DIR__ . '/lib/auth.php';
    require_admin();

    require_once __DIR__ . '/lib/csrf.php';
    csrf_verify();

    require_once __DIR__ . '/lib/audit.php';

    header('Content-Type: application/json; charset=utf-8');

 //every rejection path funnels through here so both outcomes always audit
    $reject = static function (string $message) use ($pdo): never {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        audit_log('import_questions', 'rejected: ' . $message);
        http_response_code(422);
        echo json_encode(['error' => $message]);
        exit;
    };

 // ---------- upload-level checks ----------
    if (!isset($_FILES['jsonfile']) || !is_array($_FILES['jsonfile'])) {
        $reject('No jsonfile upload.');
    }

    $upload = $_FILES['jsonfile'];

    if (!isset($upload['error']) || (int) $upload['error'] !== UPLOAD_ERR_OK) {
        $reject('Upload failed with code ' . (string) ($upload['error'] ?? -1) . '.');
    }

    if (!isset($upload['size']) || (int) $upload['size'] > 2 * 1024 * 1024) {
        $reject('File exceeds the 2MB limit.');
    }

    $data = json_decode((string) file_get_contents((string) $upload['tmp_name']), true);
    if (!is_array($data)) {
        $reject('Malformed JSON.');
    }

 // ---------- schema checks ----------
    $quizMeta = $data['quiz'] ?? null;
    $questionsIn = $data['questions'] ?? null;

    if (!is_array($quizMeta) || !is_array($questionsIn)) {
        $reject('Schema requires "quiz" and "questions" objects.');
    }

    $quizName = isset($quizMeta['name']) && is_string($quizMeta['name']) ? trim($quizMeta['name']) : '';
    if ($quizName === '') {
        $reject('Quiz name must be a non-empty string.');
    }

    $timeAllotted = filter_var($quizMeta['time_allotted'] ?? null, FILTER_VALIDATE_INT);
    $displayQuestions = filter_var($quizMeta['display_questions'] ?? null, FILTER_VALIDATE_INT);
    if ($timeAllotted === false || $displayQuestions === false) {
        $reject('time_allotted and display_questions must be integers.');
    }

    $questions = array();
    foreach ($questionsIn as $questionIn) {
        if (!is_array($questionIn)) {
            $reject('Each question must be an object.');
        }

        $text = isset($questionIn['question']) && is_string($questionIn['question']) ? trim($questionIn['question']) : '';
        if ($text === '') {
            $reject('Question text must be a non-empty string.');
        }

        $type = isset($questionIn['type']) && is_string($questionIn['type']) ? $questionIn['type'] : '';
        if ($type !== 'tf' && $type !== 'mc') {
            $reject("Question type must be tf or mc, got '$type'.");
        }

        $code = isset($questionIn['code']) && is_string($questionIn['code']) ? $questionIn['code'] : '';
        $codeType = isset($questionIn['code_type']) && is_string($questionIn['code_type']) ? $questionIn['code_type'] : '';

        $answersIn = $questionIn['answers'] ?? null;
        if (!is_array($answersIn)) {
            $reject('Each question needs an answers array.');
        }

        if ($type === 'tf' && count($answersIn) !== 2) {
            $reject('True/false questions need exactly 2 answers.');
        }
        if ($type === 'mc' && (count($answersIn) < 2 || count($answersIn) > 4)) {
            $reject('Multiple choice questions need 2-4 answers.');
        }

        $correctCount = 0;
        $answers = array();
        foreach ($answersIn as $answerIn) {
            if (!is_array($answerIn)
                || !isset($answerIn['text']) || !is_string($answerIn['text']) || trim($answerIn['text']) === ''
                || !array_key_exists('correct', $answerIn) || !is_bool($answerIn['correct'])
            ) {
                $reject('Each answer needs a non-empty text and a boolean correct flag.');
            }

            if ($answerIn['correct']) {
                $correctCount++;
            }

            $answers[] = array('text' => $answerIn['text'], 'correct' => $answerIn['correct']);
        }

        if ($correctCount !== 1) {
            $reject('Exactly one answer per question must have correct: true.');
        }

        $questions[] = array(
            'question' => $text,
            'type' => $type,
            'code' => $code,
            'code_type' => $codeType,
            'answers' => $answers,
        );
    }

 // ---------- transactional write ----------
    try {
        $pdo->beginTransaction();

     //unique-name ladder: base -> base-imported -> base-imported2 -> ...
        $candidate = $quizName;
        $suffixN = 0;
        while (true) {
            $stmtName = $pdo->prepare("SELECT id FROM quizes WHERE quiz_name = :name");
            $stmtName->execute(['name' => $candidate]);
            if ($stmtName->fetch() === false) {
                break;
            }
            $suffixN++;
            $candidate = $quizName . '-imported' . ($suffixN === 1 ? '' : (string) $suffixN);
        }

        $stmtQuiz = $pdo->prepare(
            "INSERT INTO quizes (quiz_name, total_questions, display_questions, time_allotted)
             VALUES (:name, :total, :disp, :time)"
        );
        $stmtQuiz->execute([
            'name' => $candidate,
            'total' => count($questions),
            'disp' => $displayQuestions,
            'time' => $timeAllotted,
        ]);

        $newQuizId = (int) $pdo->lastInsertId();

        foreach ($questions as $question) {
            $stmtQ = $pdo->prepare(
                "INSERT INTO questions (quiz_id, question, code, code_type, type)
                 VALUES (:quizID, :question, :code, :codeType, :type)"
            );
            $stmtQ->execute([
                'quizID' => $newQuizId,
                'question' => $question['question'],
                'code' => $question['code'],
                'codeType' => $question['code_type'],
                'type' => $question['type'],
            ]);

            $lastQuestionId = (int) $pdo->lastInsertId();

            $stmtA = $pdo->prepare(
                "INSERT INTO answers (quiz_id, question_id, answer, correct)
                 VALUES (:quizID, :questionID, :answer, :correct)"
            );
            foreach ($question['answers'] as $answer) {
                $stmtA->execute([
                    'quizID' => $newQuizId,
                    'questionID' => $lastQuestionId,
                    'answer' => $answer['text'],
                    'correct' => $answer['correct'] ? 1 : 0,
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $reject('Database error during import.');
    }

    audit_log('import_questions', 'imported ' . count($questions) . " question(s) into \"$candidate\"");

    echo json_encode(['imported' => count($questions), 'quizId' => $newQuizId]);
    exit();
