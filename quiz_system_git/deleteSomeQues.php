<?php

	require_once __DIR__ . '/lib/auth.php';
	require_admin();

	require_once __DIR__ . '/lib/csrf.php';
	csrf_verify();

	require_once("scripts/connect_db.php");

	if(isset($_POST['total_ques']) && $_POST['total_ques'] != ""){
		$total_questions = $_POST["total_ques"];

		$questIDs='';

	 //collect the requested question ids, keeping the qu{i} index order
		$requestedIDs = array();
		for($i=1 ; $i <= $total_questions ; $i++) {
			@$fetch_ID = "qu".$i;

	        if(isset($_POST[$fetch_ID])) {
				@$php_id = $_POST[$fetch_ID];

				if($php_id){
					$requestedIDs[$i] = $php_id;
				}
			}
		}

		try{
			$pdo->beginTransaction();

		 //resolve which requested questions actually exist, where they live,
		 //and how many deletions each affected quiz must be decremented by
			$idList = array_values($requestedIDs);
			$quizMap = array();
			$countsPerQuiz = array();
			foreach(array_chunk($idList, 500) as $chunk){
				$placeholders = implode(',', array_fill(0, count($chunk), '?'));
				$stmtLookup = $pdo->prepare("SELECT question_id, quiz_id FROM questions WHERE question_id IN ($placeholders)");
				$stmtLookup->execute($chunk);
				while($row = $stmtLookup->fetch()){
					$quizMap[(string) $row['question_id']] = $row['quiz_id'];
					$countsPerQuiz[$row['quiz_id']] = ($countsPerQuiz[$row['quiz_id']] ?? 0) + 1;
				}
			}

		 //set-based deletes: one DELETE per table instead of 4 queries per row
			if(!empty($idList)){
				foreach(array_chunk($idList, 500) as $chunk){
					$placeholders = implode(',', array_fill(0, count($chunk), '?'));

					$stmtDelQ = $pdo->prepare("DELETE FROM questions WHERE question_id IN ($placeholders)");
					$stmtDelQ->execute($chunk);

					$stmtDelA = $pdo->prepare("DELETE FROM answers WHERE question_id IN ($placeholders)");
					$stmtDelA->execute($chunk);
				}

			 //decrement total_questions once per affected quiz
				$stmtUpdate = $pdo->prepare("UPDATE quizes SET total_questions=total_questions-:n WHERE id=:quizID LIMIT 1");
				foreach($countsPerQuiz as $qzId => $n){
					$stmtUpdate->execute(['n' => $n, 'quizID' => $qzId]);
				}
			}

			$pdo->commit();
		}catch(Throwable $e){
			if($pdo->inTransaction()){
				$pdo->rollBack();
			}
			$user_msg = 'Sorry, something went wrong while deleting the questions. Please try again.';
			header('location: admin.php?msg='.urlencode($user_msg));
			exit();
		}

	 //preserve legacy message semantics: list the indexes whose question existed
		foreach($requestedIDs as $idx => $phpId){
			if(isset($quizMap[(string) $phpId])){
				$questIDs .= $idx.', ';
			}
		}

		$user_msg = 'Questions, '.$questIDs.' have been deleted!';
	    header('location: admin.php?msg='.urlencode($user_msg));
        exit();
	}else{
		$user_msg = 'Sorry, but Something went wrong';
		header('location: admin.php?msg='.urlencode($user_msg));
        exit();
    }
?>
