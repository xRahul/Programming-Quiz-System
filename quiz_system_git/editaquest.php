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
		$question = htmlspecialchars($question);
		$program = htmlspecialchars($program);
		$answer1 = htmlspecialchars($answer1);
		$answer2 = htmlspecialchars($answer2);
		$answer3 = htmlspecialchars($answer3);
		$answer4 = htmlspecialchars($answer4);

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

	 //if inserting a true/false question, insert answers by this-
		if($type == 'tf'){
            $stmtInsert = $pdo->prepare("INSERT INTO answers (quiz_id, question_id, answer, correct) VALUES (:quizID1, :q_id1, :answer1, :correct1), (:quizID2, :q_id2, :answer2, :correct2)");
		 //if answer1 is marked correct, do this--
			if($isCorrect == "answer1"){
                $stmtInsert->execute([
                    'quizID1' => $quizID, 'q_id1' => $q_id, 'answer1' => $answer1, 'correct1' => '1',
                    'quizID2' => $quizID, 'q_id2' => $q_id, 'answer2' => $answer2, 'correct2' => '0'
                ]);
				$msg = 'Thanks, question no.'.$q_id.' has been edited';
				header('location: admin.php?msg='.urlencode($msg));
				exit();
			}
		 //if answer2 is marked correct, do this--
			if($isCorrect == "answer2"){
                $stmtInsert->execute([
                    'quizID1' => $quizID, 'q_id1' => $q_id, 'answer1' => $answer2, 'correct1' => '1',
                    'quizID2' => $quizID, 'q_id2' => $q_id, 'answer2' => $answer1, 'correct2' => '0'
                ]);
				$msg = 'Thanks, question no.'.$q_id.' has been edited';
				header('location: admin.php?msg='.urlencode($msg));
				exit();
			}	
		}

	 //if inserting a multiple choice question, insert answers by this-
		if($type == 'mc'){
            $stmtInsert = $pdo->prepare("INSERT INTO answers (quiz_id, question_id, answer, correct) VALUES
                (:quizID1, :q_id1, :answer1, :correct1),
                (:quizID2, :q_id2, :answer2, :correct2),
                (:quizID3, :q_id3, :answer3, :correct3),
                (:quizID4, :q_id4, :answer4, :correct4)");
		 //if answer1 is marked correct, do this--
			if($isCorrect == "answer1"){
                $stmtInsert->execute([
                    'quizID1' => $quizID, 'q_id1' => $q_id, 'answer1' => $answer1, 'correct1' => '1',
                    'quizID2' => $quizID, 'q_id2' => $q_id, 'answer2' => $answer2, 'correct2' => '0',
                    'quizID3' => $quizID, 'q_id3' => $q_id, 'answer3' => $answer3, 'correct3' => '0',
                    'quizID4' => $quizID, 'q_id4' => $q_id, 'answer4' => $answer4, 'correct4' => '0'
                ]);
				$msg = 'Thanks, question no.'.$q_id.' has been edited';
				header('location: admin.php?msg='.urlencode($msg));
				exit();
			}
		 //if answer2 is marked correct, do this--
			if($isCorrect == "answer2"){
                $stmtInsert->execute([
                    'quizID1' => $quizID, 'q_id1' => $q_id, 'answer1' => $answer2, 'correct1' => '1',
                    'quizID2' => $quizID, 'q_id2' => $q_id, 'answer2' => $answer1, 'correct2' => '0',
                    'quizID3' => $quizID, 'q_id3' => $q_id, 'answer3' => $answer3, 'correct3' => '0',
                    'quizID4' => $quizID, 'q_id4' => $q_id, 'answer4' => $answer4, 'correct4' => '0'
                ]);
				$msg = 'Thanks, question no.'.$q_id.' has been edited';
				header('location: admin.php?msg='.urlencode($msg));
				exit();
			}
		 //if answer3 is marked correct, do this--
			if($isCorrect == "answer3"){
                $stmtInsert->execute([
                    'quizID1' => $quizID, 'q_id1' => $q_id, 'answer1' => $answer3, 'correct1' => '1',
                    'quizID2' => $quizID, 'q_id2' => $q_id, 'answer2' => $answer1, 'correct2' => '0',
                    'quizID3' => $quizID, 'q_id3' => $q_id, 'answer3' => $answer2, 'correct3' => '0',
                    'quizID4' => $quizID, 'q_id4' => $q_id, 'answer4' => $answer4, 'correct4' => '0'
                ]);
				$msg = 'Thanks, question no.'.$q_id.' has been edited';
				header('location: admin.php?msg='.urlencode($msg));
				exit();
			}
		 //if answer4 is marked correct, do this--
			if($isCorrect == "answer4"){
                $stmtInsert->execute([
                    'quizID1' => $quizID, 'q_id1' => $q_id, 'answer1' => $answer4, 'correct1' => '1',
                    'quizID2' => $quizID, 'q_id2' => $q_id, 'answer2' => $answer1, 'correct2' => '0',
                    'quizID3' => $quizID, 'q_id3' => $q_id, 'answer3' => $answer2, 'correct3' => '0',
                    'quizID4' => $quizID, 'q_id4' => $q_id, 'answer4' => $answer3, 'correct4' => '0'
                ]);
				$msg = 'Thanks, question no.'.$q_id.' has been edited';
				header('location: admin.php?msg='.urlencode($msg));
				exit();
			}
		}
	}else{
        $user_msg = 'Sorry, but Something went wrong';
        header('location: admin.php?msg='.urlencode($user_msg));
        exit();
    }
?>