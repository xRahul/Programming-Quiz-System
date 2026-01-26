<?php

	require_once("scripts/connect_db.php");

	if(isset($_POST['total_ques']) && $_POST['total_ques'] != ""){
		$total_questions = $_POST["total_ques"];

		$questIDs='';


		for($i=1 ; $i <= $total_questions ; $i++) {
	        @$fetch_ID = "qu".$i;

	        if(isset($_POST[$fetch_ID])) {
				@$php_id = $_POST[$fetch_ID];

				if($php_id){
                    $stmt = $pdo->prepare("SELECT quiz_id FROM questions WHERE question_id=:questionID");
                    $stmt->execute(['questionID' => $php_id]);
					$qz_id_array = $stmt->fetch();

                    if ($qz_id_array) {
                        $qz_id = $qz_id_array['quiz_id'];

                        $stmtDelQ = $pdo->prepare("DELETE FROM questions WHERE question_id=:questionID");
                        $stmtDelQ->execute(['questionID' => $php_id]);

                        $stmtDelA = $pdo->prepare("DELETE FROM answers WHERE question_id=:questionID");
                        $stmtDelA->execute(['questionID' => $php_id]);

                        $stmtUpdate = $pdo->prepare("UPDATE quizes SET total_questions=total_questions-1 WHERE id=:quizID LIMIT 1");
                        $stmtUpdate->execute(['quizID' => $qz_id]);

                        $questIDs .= $i.', ';
                    }
				}
			}
		}

		$user_msg = 'Questions, \ '.htmlspecialchars($questIDs).' \ have been deleted!';
	    header('location: admin.php?msg='.urlencode($user_msg));
        exit();
	}else{
		$user_msg = 'Sorry, but Something went wrong';
		header('location: admin.php?msg='.urlencode($user_msg));
        exit();
    }
?>
