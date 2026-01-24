<?php

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
		$user_msg = 'Sorry, but \ '.htmlspecialchars($qName).' \ already exists!';
                header('location: admin.php?msg='.urlencode($user_msg));
                exit();
            }else{
                $stmtInsert = $pdo->prepare("INSERT INTO quizes (quiz_name, display_questions, time_allotted) VALUES (:quizName, :nQues, :qTime)");
                $stmtInsert->execute(['quizName' => $qName, 'nQues' => $nQues, 'qTime' => $qTime]);
                
                $lastId = $pdo->lastInsertId();
                $stmtUpdate = $pdo->prepare("UPDATE quizes SET quiz_id=:lastId WHERE id=:lastId LIMIT 1");
                $stmtUpdate->execute(['lastId' => $lastId]);

		$user_msg = 'Quiz, \ '.htmlspecialchars($qName).' \ has been created!';
                header('location: admin.php?msg='.urlencode($user_msg));
                exit();
            }
        }else{
            $user_msg = 'Sorry, but Something went wrong';
            header('location: admin.php?msg='.urlencode($user_msg));
            exit();
        }
?>