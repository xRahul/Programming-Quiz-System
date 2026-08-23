<?php

	require_once __DIR__ . '/lib/auth.php';
	require_admin();

	require_once __DIR__ . '/lib/csrf.php';
	csrf_verify();

	require_once('scripts/connect_db.php');

        if(isset($_POST['quizName']) && $_POST['quizName'] != ""
        && isset($_POST['quizTime']) && $_POST['quizTime'] != ""
        && isset($_POST['numQues']) && $_POST['numQues'] != ""){

            $qName = $_POST['quizName'];
            $qTime = $_POST['quizTime'];
            $nQues = $_POST['numQues'];

            $qTime = preg_replace('/[^0-9]/', "", $qTime);
            $nQues = preg_replace('/[^0-9]/', "", $nQues);

            $stmt = $pdo->prepare("SELECT id FROM quizes WHERE quiz_name=:quizName");
            $stmt->execute(['quizName' => $qName]);

            if($stmt->rowCount() > 0)
            {
		$user_msg = 'Sorry, but '.$qName.' already exists!';
                header('location: admin.php?msg='.urlencode($user_msg));
                exit();
            }else{
                $stmtInsert = $pdo->prepare("INSERT INTO quizes (quiz_name, display_questions, time_allotted) VALUES (:quizName, :nQues, :qTime)");
                $stmtInsert->execute(['quizName' => $qName, 'nQues' => $nQues, 'qTime' => $qTime]);
                
		$user_msg = 'Quiz, '.$qName.' has been created!';
                header('location: admin.php?msg='.urlencode($user_msg));
                exit();
            }
        }else{
            $user_msg = 'Sorry, but Something went wrong';
            header('location: admin.php?msg='.urlencode($user_msg));
            exit();
        }
?>