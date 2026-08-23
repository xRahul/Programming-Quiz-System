<?php

	require_once __DIR__ . '/lib/auth.php';
	require_admin();

	require_once __DIR__ . '/lib/csrf.php';
	csrf_verify();

	require_once('scripts/connect_db.php');

        if(isset($_POST['quizName']) && $_POST['quizName'] != "" &&
           isset($_POST['quizTime']) && $_POST['quizTime'] != "" &&
           isset($_POST['numQues']) && $_POST['numQues'] != ""){

            $qName = $_POST['quizName'];
            $qTime = $_POST['quizTime'];
            $nQues = $_POST['numQues'];

            $qTime = preg_replace('/[^0-9]/', "", $qTime);
            $nQues = preg_replace('/[^0-9]/', "", $nQues);

            $stmt = $pdo->prepare("SELECT total_questions FROM quizes WHERE quiz_name=:quizName");
            $stmt->execute(['quizName' => $qName]);

            if($stmt->rowCount() == 0)
            {
		$user_msg = 'Sorry, but '.$qName.' doesn\'t exist!';
                header('location: admin.php?msg='.urlencode($user_msg));
                exit();
            }else{

                $fetch_row = $stmt->fetch();
                $t_ques = $fetch_row['total_questions'];

                $stmtUpdate = $pdo->prepare("UPDATE quizes SET display_questions=:nQues, time_allotted= :qTime WHERE quiz_name = :quizName");
                $stmtUpdate->execute(['nQues' => $nQues, 'qTime' => $qTime, 'quizName' => $qName]);

		$user_msg = 'Quiz, '.$qName.' has been updated!';
                if($t_ques < $nQues)
                    $user_msg .= ' But, display Questions are more than the total no. of questions in the quiz('.$nQues.'/'.$t_ques.').';

                header('location: admin.php?msg='.urlencode($user_msg));
                exit();
            }
        }else{
            $user_msg = 'Sorry, but Something went wrong';
            header('location: admin.php?msg='.urlencode($user_msg));
            exit();
        }
?>