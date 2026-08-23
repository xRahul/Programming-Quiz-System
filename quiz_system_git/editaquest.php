<?php

//inserting the questions into the database
 //checking if the required data has been filled
	require_once __DIR__ . '/lib/auth.php';
	require_admin();

	require_once __DIR__ . '/lib/csrf.php';
	csrf_verify();

	if(isset($_POST['desc'])){
		if(!isset($_POST['iscorrect']) || $_POST['iscorrect'] == ""){
			echo "Sorry, important data to submit your question is missing. Please press back in your browser and try again and make sure you select a correct answer for the question.";
			exit();
		}

		if(!isset($_POST['type']) || $_POST['type'] == ""){
			echo "Sorry, there was an error parsing the form. Please press back in your browser and try again";
			exit();
		}

	 //connecting to the database
		require_once("scripts/connect_db.php");

	 //initializing the variables
		$question = $_POST['desc'];
		$program = $_POST['code_desc'];
		$programType = $_POST['prog-lang'];
		$answer1 = $_POST['answer1'];
		$answer2 = $_POST['answer2'];
		$answer3 = $_POST['answer3'];
		$answer4 = $_POST['answer4'];
		$type = $_POST['type'];
		$quizID = $_POST['quizID'];
		$q_id = $_POST['questionID'];

	 //replacing everything except 0-9 with nothing as its values are - 1/2/3...
		$quizID = preg_replace('/[^0-9]/', "", $quizID);
		$q_id = preg_replace('/[^0-9]/', "", $q_id);

	 //replacing everything except a-z with nothing as its values are - mc/tf
		$type = preg_replace('/[^a-z]/', "", $type);

	 //replacing everything except 0-9 & a-z with nothhing as value is - answer1/2/3/4
		$isCorrect = preg_replace('/[^0-9a-z]/', "", $_POST['iscorrect']);

	 //getting and converting strings as they are
     // Values are stored RAW and escaped at output time (T4.4).

	 //if its a true/false question, do this-
		if($type == 'tf'){
		 //if any field is null or empty, say sorry ('0' is legitimate content)
			if($question=='' || $answer1=='' || $answer2=='' || $isCorrect=='' || $q_id=='' || $quizID==''){
				echo "Sorry, All fields must be filled in to add a new question to the quiz. Please press back in your browser and try again.";
				exit();
			}
		}

	 //if its a multiple choice question, do this-
		if($type == 'mc'){
		 //if any field is null or empty, say sorry ('0' is legitimate content)
			if($question=='' || $answer1=='' || $answer2=='' || $answer3=='' || $answer4=='' || $isCorrect=='' || $q_id=='' || $quizID==''){
				echo "Sorry, All fields must be filled in to add a new question to the quiz. Please press back in your browser and try again.";
				exit();
			}
		}

	 //resolve the answer order for the marked correct option
		$answerRows = array();
		if($type == 'tf'){
			if($isCorrect == "answer1"){
				$answerRows = array(array($answer1,'1'), array($answer2,'0'));
			}elseif($isCorrect == "answer2"){
				$answerRows = array(array($answer2,'1'), array($answer1,'0'));
			}
		}elseif($type == 'mc'){
			if($isCorrect == "answer1"){
				$answerRows = array(array($answer1,'1'), array($answer2,'0'), array($answer3,'0'), array($answer4,'0'));
			}elseif($isCorrect == "answer2"){
				$answerRows = array(array($answer2,'1'), array($answer1,'0'), array($answer3,'0'), array($answer4,'0'));
			}elseif($isCorrect == "answer3"){
				$answerRows = array(array($answer3,'1'), array($answer1,'0'), array($answer2,'0'), array($answer4,'0'));
			}elseif($isCorrect == "answer4"){
				$answerRows = array(array($answer4,'1'), array($answer1,'0'), array($answer2,'0'), array($answer3,'0'));
			}
		}

	 //transactional write: update question -> delete answers -> insert answers
		try{
			$pdo->beginTransaction();

		 //updating the question and type into table question
			$stmt = $pdo->prepare("UPDATE questions SET quiz_id=:quizID, question=:question, code=:program, code_type=:programType, type=:type WHERE question_id=:q_id");
			$stmt->execute([
				'quizID' => $quizID,
				'question' => $question,
				'program' => $program,
				'programType' => $programType,
				'type' => $type,
				'q_id' => $q_id
			]);

		 //deleting the answers
			$stmtDel = $pdo->prepare("DELETE FROM answers WHERE question_id=:q_id");
			$stmtDel->execute(['q_id' => $q_id]);

		 /// inserting answers again based on which is correct //////////////
			if(!empty($answerRows)){
				$stmtInsert = $pdo->prepare("INSERT INTO answers (quiz_id, question_id, answer, correct) VALUES (:quizID, :questionID, :answer, :correct)");
				foreach($answerRows as $answerRow){
					$stmtInsert->execute([
						'quizID' => $quizID,
						'questionID' => $q_id,
						'answer' => $answerRow[0],
						'correct' => $answerRow[1]
					]);
				}
			}

			$pdo->commit();
		}catch(Throwable $e){
			if($pdo->inTransaction()){
				$pdo->rollBack();
			}
			$msg = 'Sorry, something went wrong while saving the question. Please try again.';
			header('location: admin.php?msg='.urlencode($msg));
			exit();
		}

		if(!empty($answerRows)){
			$msg = 'Thanks, question no.'.$q_id.' has been edited';
			header('location: admin.php?msg='.urlencode($msg));
			exit();
		}
	}else{
        $user_msg = 'Sorry, but Something went wrong';
        header('location: admin.php?msg='.urlencode($user_msg));
        exit();
    }
?>