<?php

	/*
    Short Programming Quiz Framework
        Copyright (C) 2014  Rahul Jain
    */

    require_once("session.php");
    require_once __DIR__ . '/lib/csrf.php';
    csrf_verify();

    // $pdo is available from session.php -> connect_db.php -> db.php

//inserting the questions into the database
 //checking if the required data has been filled
	if(isset($_POST['desc'])){
		if(!isset($_POST['iscorrect']) || $_POST['iscorrect'] == ""){
			echo "Sorry, important data to submit your question is missing. Please press back in your browser and try again and make sure you select a correct answer for the question.";
			exit();
		}

		if(!isset($_POST['type']) || $_POST['type'] == ""){
			echo "Sorry, there was an error parsing the form. Please press back in your browser and try again";
			exit();
		}

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

	 //replacing everything except 0-9 with nothing as its values are - 1/2/3...
		$quizID = preg_replace('/[^0-9]/', "", $quizID);

	 //replacing everything except a-z with nothing as its values are - mc/tf
		$type = preg_replace('/[^a-z]/', "", $type);

	 //replacing everything except 0-9 & a-z with nothhing as value is - answer1/2/3/4
		$isCorrect = preg_replace('/[^0-9a-z]/', "", $_POST['iscorrect']);

	 //getting and converting strings as they are
     // NOTE: We don't use mysql_real_escape_string with PDO prepared statements.
     // We DO use htmlspecialchars to prevent XSS when displaying, but usually we store raw and escape on output.
     // However, the original code called htmlspecialchars BEFORE storing. I will keep that behavior to minimize breakage,
     // although ideally we store raw.
		$question = htmlspecialchars($question);
		$program = htmlspecialchars($program);
		$answer1 = htmlspecialchars($answer1);
		$answer2 = htmlspecialchars($answer2);
		$answer3 = htmlspecialchars($answer3);
		$answer4 = htmlspecialchars($answer4);

	 //if its a true/false question, do this-
		if($type == 'tf'){
		 //if any field is null or empty, say sorry
			if((!$question) || (!$answer1) || (!$answer2) || (!$isCorrect)){
				if($answer1=='0' || $answer2=='0')
				{
					//do nothing
				}else{
					echo "Sorry, All fields must be filled in to add a new question to the quiz. Please press back in your browser and try again.";
					exit();
				}
			}
		}

	 //if its a multiple choice question, do this-
		if($type == 'mc'){
		 //if any field is null or empty, say sorry
			if((!$question) || (!$answer1) || (!$answer2) || (!$answer3) || (!$answer4) || (!$isCorrect)){
				if($question=='0' || $answer1=='0' || $answer2=='0' || $answer3=='0' || $answer4=='0')
				{
					//do nothing
				}else{
					echo "Sorry, All fields must be filled in to add a new question to the quiz. Please press back in your browser and try again.";
					exit();
				}
			}
		}

	 //inserting the question and type into table question
        $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question, code, code_type, type) VALUES (:quizID, :question, :program, :programType, :type)");
        $stmt->execute([
            'quizID' => $quizID,
            'question' => $question,
            'program' => $program,
            'programType' => $programType,
            'type' => $type
        ]);

		//lastId is there, so we can insert the id, question_id in our table
			$lastId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("UPDATE questions SET question_id=:lastId WHERE id=:lastId LIMIT 1");
            $stmt->execute(['lastId' => $lastId]);

	 ///////Updating value of total questions in quizes
        $stmt = $pdo->prepare("UPDATE quizes SET total_questions=total_questions+1 WHERE id=:quizID LIMIT 1");
        $stmt->execute(['quizID' => $quizID]);


	 /// Update answers based on which is correct //////////////

	 //if inserting a true/false question, insert answers by this-
		if($type == 'tf'){
            $stmtInsert = $pdo->prepare("INSERT INTO answers (quiz_id, question_id, answer, correct) VALUES (:quizID, :questionID, :answer, :correct)");
		 //if answer1 is marked correct, do this--
			if($isCorrect == "answer1"){
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer1, 'correct' => '1']);
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer2, 'correct' => '0']);

				$msg = 'Thanks, question no.'.$lastId.' has been added';
				header('location: admin.php?msg='.urlencode($msg));
				exit();
			}
		 //if answer2 is marked correct, do this--
			if($isCorrect == "answer2"){
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer2, 'correct' => '1']);
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer1, 'correct' => '0']);

				$msg = 'Thanks, question no.'.$lastId.' has been added';
				header('location: admin.php?msg='.urlencode($msg));
				exit();
			}
		}

	 //if inserting a multiple choice question, insert answers by this-
		if($type == 'mc'){
            $stmtInsert = $pdo->prepare("INSERT INTO answers (quiz_id, question_id, answer, correct) VALUES (:quizID, :questionID, :answer, :correct)");

		 //if answer1 is marked correct, do this--
			if($isCorrect == "answer1"){
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer1, 'correct' => '1']);
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer2, 'correct' => '0']);
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer3, 'correct' => '0']);
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer4, 'correct' => '0']);

				$msg = 'Thanks, question no.'.$lastId.' has been added';
				header('location: admin.php?msg='.urlencode($msg));
				exit();
			}
		 //if answer2 is marked correct, do this--
			if($isCorrect == "answer2"){
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer2, 'correct' => '1']);
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer1, 'correct' => '0']);
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer3, 'correct' => '0']);
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer4, 'correct' => '0']);

				$msg = 'Thanks, question no.'.$lastId.' has been added';
				header('location: admin.php?msg='.urlencode($msg));
				exit();
			}
		 //if answer3 is marked correct, do this--
			if($isCorrect == "answer3"){
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer3, 'correct' => '1']);
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer1, 'correct' => '0']);
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer2, 'correct' => '0']);
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer4, 'correct' => '0']);

				$msg = 'Thanks, question no.'.$lastId.' has been added';
				header('location: admin.php?msg='.urlencode($msg));
				exit();
			}
		 //if answer4 is marked correct, do this--
			if($isCorrect == "answer4"){
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer4, 'correct' => '1']);
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer1, 'correct' => '0']);
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer2, 'correct' => '0']);
                $stmtInsert->execute(['quizID' => $quizID, 'questionID' => $lastId, 'answer' => $answer3, 'correct' => '0']);

				$msg = 'Thanks, question no.'.$lastId.' has been added';
				header('location: admin.php?msg='.urlencode($msg));
				exit();
			}
		}
	}
?>


<?php
//showing the message back to the user after a transaction is completed
	$msg = "";
	if(isset($_GET['msg'])){
		$msg = htmlspecialchars($_GET['msg']);
	}

	if(isset($_POST['msg'])){
		$msg = htmlspecialchars($_POST['msg']);
	}
?>




<?php
//if resetTables is clicked
	if(isset($_POST['resetTables']) && $_POST['resetTables'] != ""){
		$resetT = $_POST['resetTables'];

		$resetT = preg_replace('/[^a-z]/', "", $resetT);

		if($resetT=='yes'){
			$pdo->exec("TRUNCATE TABLE admins");
			$pdo->exec("TRUNCATE TABLE answers");
			$pdo->exec("TRUNCATE TABLE questions");
			$pdo->exec("TRUNCATE TABLE quizes");
			$pdo->exec("TRUNCATE TABLE quiz_takers");

            // Default password hash for '12345'
            $default_pass_hash = password_hash('12345', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES ('admin', :password)");
            $stmt->execute(['password' => $default_pass_hash]);
		}

		echo "Alright then, your database is now reset! Just re-login with new ID.";
		exit();

	}

?>



<?php
//When editaquestion is clicked
	if(isset($_POST['editaquestion']) && $_POST['editaquestion'] != ""){
		$editQ = $_POST['editaquestion'];

        $get_quiz_id = '';
        $m_quiz_name = '';

        if($editQ != 'allthequestions') {
		$stmt = $pdo->prepare("SELECT id as quiz_id FROM quizes WHERE quiz_name = :quizName");
            $stmt->execute(['quizName' => $editQ]);
            $row = $stmt->fetch();
		$get_quiz_id = $row['quiz_id'];
        }

		$m_output='';

		if($editQ=='allthequestions') {
			$stmt = $pdo->query("SELECT questions.*, quizes.quiz_name FROM questions LEFT JOIN quizes ON questions.quiz_id = quizes.quiz_id");
        } else {
            $stmt = $pdo->prepare("SELECT questions.*, quizes.quiz_name FROM questions LEFT JOIN quizes ON questions.quiz_id = quizes.quiz_id WHERE questions.quiz_id = :quizID");
            $stmt->execute(['quizID' => $get_quiz_id]);
        }

			$m_display_ID = 1;

            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Collect IDs
            $questionIDs = [];
            foreach ($questions as $q) {
                $questionIDs[] = $q['question_id'];
            }

            // Batch fetch answers
            $answersByQuestion = [];
            if (!empty($questionIDs)) {
                $chunks = array_chunk($questionIDs, 500);
                foreach ($chunks as $chunk) {
                    $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                    $stmtAns = $pdo->prepare("SELECT * FROM answers WHERE question_id IN ($placeholders)");
                    $stmtAns->execute($chunk);
                    while ($row = $stmtAns->fetch(PDO::FETCH_ASSOC)) {
                        $answersByQuestion[$row['question_id']][] = $row;
                    }
                }
            }

			foreach ($questions as $m_row){
				$m_answers='';
			 //id var = id column and so on
				$m_id = $m_row['id'];
				$m_thisQuestion = $m_row['question'];
				$m_type = $m_row['type'];
				$m_question_id = $m_row['question_id'];
				$m_code = $m_row['code'];
				$m_code_type = $m_row['code_type'];
				$m_quiz_id = $m_row['quiz_id'];
				$m_quiz_name = $m_row['quiz_name'];


				$m_q = '<tr>
							<td width="40px" rowspan="1" align="center">';

				if($editQ=='allthequestions'){
					$m_q .=	'<br>
								<strong>'.$m_display_ID.'.</strong>
							</td>
							<td>
								<input type="radio" name="editAQ" value="'.$m_question_id.'">
								<small><i>('.$m_quiz_name.')</i></small><br>';
				}else{
					$m_q .= '	<strong>'.$m_display_ID.'.</strong>
							</td>
							<td>
								<input type="radio" name="editAQ" value="'.$m_question_id.'">
							';
				}
						$m_q .=	'<pre class="question_style"><strong><div style="width: 730px; word-wrap: break-word;">'.$m_thisQuestion.'</div></strong></pre>
							</td>
						</tr>';

				if($m_code != "" && $m_code_type != ""){
					$m_q .='<tr>
							<td>
							</td>
							<td>
								<pre class="brush: '.$m_code_type.';">'.$m_code.'</pre>
							</td>
						</tr>
						';
				}

			 //gathering answers of question here
                $currentAnswers = isset($answersByQuestion[$m_question_id]) ? $answersByQuestion[$m_question_id] : [];

				$m_answers .=  '<tr>
									<td></td>
									<td>
										<ol type="a">
								';

					foreach ($currentAnswers as $m_row2){
					//putting column values in variables
						$m_answer = $m_row2['answer'];
						$m_correct = $m_row2['correct'];

						if($m_correct == 1)
							$m_answers .= '<u><i>';
						$m_answers .= '<div style="width: 730px; word-wrap: break-word;"><li>'.$m_answer.'</li></div>';
						if($m_correct == 1)
							$m_answers .= '</i></u>';
					}

				$m_answers .=  '		</ol>
									</td>
								</tr>
								<tr height="20px"></tr>
								';

			 // the complete div that is sent back to quiz.php
				$m_output .= ''.$m_q.$m_answers;

				$m_display_ID++;
			}

			$m_display_ID--;

			$m_output .= '  <tr>
							<td colspan="2" align="center">

								<input type="hidden" name="total_ques" value="'.$m_display_ID.'">

								<span id="m_btnSpan">
									<a href="javascript:{}" onclick="editQ_submit()" class="myButton">Submit</a>
								</span>
							</td>
						</tr>';

			echo $m_output;
			exit();
	}
?>



<?php
//When editAQ is clicked
	$editQoutput='';
	$gaq_question_id='';

	if(isset($_POST['editAQ']) && $_POST['editAQ'] != ""){
		$editAQ = $_POST['editAQ'];
		$editAQ = preg_replace('/[^0-9]/', "", $editAQ);

	 //getting everything about the question
        $stmt = $pdo->prepare("SELECT * FROM questions WHERE question_id=:questionID");
        $stmt->execute(['questionID' => $editAQ]);
		$getaquestion_row = $stmt->fetch();

		$gaq_id = $getaquestion_row['id'];
		$gaq_quiz_id = $getaquestion_row['quiz_id'];
		$gaq_question_id = $getaquestion_row['question_id'];
		$gaq_question = $getaquestion_row['question'];
		$gaq_code_editor = $getaquestion_row['code'];
		$gaq_code_type = $getaquestion_row['code_type'];
		$gaq_type = $getaquestion_row['type'];
	 //converting program into what it ought to be
		$gaq_code_editor = htmlspecialchars_decode($gaq_code_editor);

        $stmtAns = $pdo->prepare("SELECT * FROM answers WHERE question_id=:questionID");
        $stmtAns->execute(['questionID' => $editAQ]);


	 //if question is true/false type
		if($gaq_type=='tf'){
			$editQoutput .= '<script>
								showDiv(\'tf\', \'mc\', \'quesans\');
								document.getElementById(\'quizIDtf\').value = '.json_encode($gaq_quiz_id).';
								document.getElementById(\'tfDesc\').value = '.json_encode($gaq_question).';
							 </script>
							';
		 //if there's programming code attached to the question, add this
			if($gaq_code_type!=""){
				$editQoutput .=	'<script>
									document.getElementById(\'prog-lang-tf\').value = '.json_encode($gaq_code_type).';
									change_editor('.json_encode($gaq_code_type).');
									tfeditor.setValue('.json_encode($gaq_code_editor).');
								</script>
								';
			}
		 //getting answers of T/F questions
			$ga_index=1;
			while($getanswers_row = $stmtAns->fetch()){
				$ga_answer = $getanswers_row['answer'];
				$ga_correct = $getanswers_row['correct'];

				if($ga_correct==1 && $ga_answer=="True"){
					$editQoutput .= '<script>
										document.getElementById(\'tfans1\').checked=true;
									 </script>
									';
				}
				else if($ga_correct==1 && $ga_answer=="False"){
					$editQoutput .= '<script>
										document.getElementById(\'tfans2\').checked=true;
									 </script>
									';
				}
				$ga_index++;
			}

		 //changing the submit button and action
			$editQoutput .= '<script>
								document.addQuestion.action = "editaquest.php";
								document.getElementById(\'addToQuizTF\').value = "Save";
							 </script>
							';

		}

	 //if the question is multiple choice!
		else if($gaq_type=='mc'){
			$editQoutput .= '<script>
								showDiv(\'mc\', \'tf\', \'quesans\');
								document.getElementById(\'quizIDmc\').value = '.json_encode($gaq_quiz_id).';
								document.getElementById(\'mcdesc\').value = '.json_encode($gaq_question).';
							 </script>
							';
			if($gaq_code_type!=""){
				$editQoutput .=	'<script>
									document.getElementById(\'prog-lang-mc\').value = '.json_encode($gaq_code_type).';
									change_editor('.json_encode($gaq_code_type).');
									mceditor.setValue('.json_encode($gaq_code_editor).');
								 </script>
								';
			}

			$ga_index=1;
			while($getanswers_row = $stmtAns->fetch()){
				$ga_answer = $getanswers_row['answer'];
				$ga_correct = $getanswers_row['correct'];

				if($ga_correct==1){
					$editQoutput .= '<script>
										document.getElementById(\'mcans'.$ga_index.'\').checked=true;
									 </script>
									';
				}

				$editQoutput .= '<script>
									document.getElementById(\'mcanswer'.$ga_index.'\').value = '.json_encode($gaq_answer).';
								 </script>
								';

				$ga_index++;
			}

			$editQoutput .= '<script>
								document.addMcQuestion.action = "editaquest.php";
								document.getElementById(\'addToQuizMC\').value = "Save";
							 </script>
							';

		}
	}
?>




<?php
//When deleteSomeQuestions is clicked
	if(isset($_POST['deleteSomeQuestions']) && $_POST['deleteSomeQuestions'] != ""){
		$deleteSQ = $_POST['deleteSomeQuestions'];

        $get_quiz_id = '';
        if($deleteSQ != 'allthequestions') {
            $stmt = $pdo->prepare("SELECT id as quiz_id FROM quizes WHERE quiz_name = :quizName");
            $stmt->execute(['quizName' => $deleteSQ]);
            $row = $stmt->fetch();
            $get_quiz_id = $row['quiz_id'];
        }

		$m_output='';

		if($deleteSQ=='allthequestions') {
			$stmt = $pdo->query("SELECT questions.*, quizes.quiz_name FROM questions LEFT JOIN quizes ON questions.quiz_id = quizes.quiz_id");
        } else {
            $stmt = $pdo->prepare("SELECT questions.*, quizes.quiz_name FROM questions LEFT JOIN quizes ON questions.quiz_id = quizes.quiz_id WHERE questions.quiz_id = :quizID");
            $stmt->execute(['quizID' => $get_quiz_id]);
        }

			$m_display_ID = 1;

			while($m_row = $stmt->fetch()){
				$m_answers='';
			 //id var = id column and so on
				$m_id = $m_row['id'];
				$m_thisQuestion = $m_row['question'];
				$m_type = $m_row['type'];
				$m_question_id = $m_row['question_id'];
				$m_code = $m_row['code'];
				$m_code_type = $m_row['code_type'];
				$m_quiz_id = $m_row['quiz_id'];

				$m_quiz_name = $m_row['quiz_name'];


				$m_q = '<tr>
							<td width="40px" rowspan="1" align="center">';

				if($deleteSQ=='allthequestions'){
					$m_q .=	'<br>
								<strong>'.$m_display_ID.'.</strong>
							</td>
							<td>
								<input type="checkbox" name="qu'.$m_display_ID.'" value="'.$m_question_id.'">
								<small><i>('.$m_quiz_name.')</i></small><br>';
				}else{
					$m_q .= '	<strong>'.$m_display_ID.'.</strong>
							</td>
							<td>
								<input type="checkbox" name="qu'.$m_display_ID.'" value="'.$m_question_id.'">
							';
				}
						$m_q .=	'<pre class="question_style"><strong><div style="width: 730px; word-wrap: break-word;">'.$m_thisQuestion.'</div></strong></pre>
							</td>
						</tr>';

				if($m_code != "" && $m_code_type != ""){
					$m_q .='<tr>
							<td>
							</td>
							<td>
								<pre class="brush: '.$m_code_type.';">'.$m_code.'</pre>
							</td>
						</tr>
						';
				}

			 //gathering answers of question here
                $stmtAns = $pdo->prepare("SELECT * FROM answers WHERE question_id=:questionID");
                $stmtAns->execute(['questionID' => $m_question_id]);
				//running loop on all the answers

				$m_answers .=  '<tr>
									<td></td>
									<td>
										<ol type="a">
								';

					while($m_row2 = $stmtAns->fetch()){
					//putting column values in variables
						$m_answer = $m_row2['answer'];
						$m_correct = $m_row2['correct'];

						if($m_correct == 1)
							$m_answers .= '<u><i>';
						$m_answers .= '<div style="width: 730px; word-wrap: break-word;"><li>'.$m_answer.'</li></div>';
						if($m_correct == 1)
							$m_answers .= '</i></u>';
					}

				$m_answers .=  '		</ol>
									</td>
								</tr>
								<tr height="20px"></tr>
								';

			 // the complete div that is sent back to quiz.php
				$m_output .= ''.$m_q.$m_answers;

				$m_display_ID++;
			}

			$m_display_ID--;

			$m_output .= '  <tr>
							<td colspan="2" align="center">

								<input type="hidden" name="total_ques" value="'.$m_display_ID.'">

								<span id="m_btnSpan">
									<a href="javascript:{}" onclick="quiz_submit()" class="myButton">Submit</a>
								</span>
							</td>
						</tr>';

			echo $m_output;
			exit();
	}
?>



<?php
//if deleteAdmin is clicked, check--
	if(isset($_POST['deleteAdmin']) && $_POST['deleteAdmin'] != ""){
		$deleteA = $_POST['deleteAdmin'];

        $stmt = $pdo->prepare("DELETE FROM admins WHERE username = :username");
        $stmt->execute(['username' => $deleteA]);

	//checking the admins table
        $stmtCheck = $pdo->prepare("SELECT id FROM admins WHERE username = :username");
        $stmtCheck->execute(['username' => $deleteA]);

		if($stmtCheck->rowCount() > 0){
			echo "Sorry, there was a problem deleting the /".htmlspecialchars($deleteA)."/ admin. Please try again later.";
			exit();
		}else{
			echo "Alright! The admin /".htmlspecialchars($deleteA)."/ has now been deleted. You just have to logout now!";
			exit();
		}
	}
?>




<?php
//if defaultQuiz is clicked, check--
	if(isset($_POST['defaultQuiz']) && $_POST['defaultQuiz'] != ""){
		$defaultQ = $_POST['defaultQuiz'];

	 ///////Updating value of set_default in quizes
        $pdo->exec("UPDATE quizes SET set_default=0 WHERE set_default=1");

        $stmt = $pdo->prepare("UPDATE quizes SET set_default=1 WHERE quiz_name=:quizName");
        $stmt->execute(['quizName' => $defaultQ]);


 //checking if update is successful
	//getting rows from tables
		$stmtCheck = $pdo->query("SELECT id FROM quizes WHERE set_default=1");
	//getting number of rows that were returned
		$numDefaults = $stmtCheck->rowCount();
	//checking if the number of rows==0
		if($numDefaults < 1 || $numDefaults > 1){
			echo "Sorry, there was a problem setting /".htmlspecialchars($defaultQ)."/ default. Please try again later.";
			exit();
		}else{
			echo "Thanks! The quiz, /".htmlspecialchars($defaultQ)."/ has now been set as default.";
			exit();
		}
	}
?>






<?php
//if clearResult is clicked, check--
	if(isset($_POST['clearResult']) && $_POST['clearResult'] != ""){
		$clearR = preg_replace('/^[a-z]/', "", $_POST['clearResult']);

	//deleting
        $stmt = $pdo->prepare("DELETE FROM quiz_takers WHERE quiz_id=:quizID");
        $stmt->execute(['quizID' => $clearR]);

 //checking if delete is successful
	//getting rows from tables
        $stmtCheck = $pdo->prepare("SELECT id FROM quiz_takers WHERE quiz_id=:quizID LIMIT 1");
        $stmtCheck->execute(['quizID' => $clearR]);
	//getting number of rows that were returned
		$numQuizTakers = $stmtCheck->rowCount();
	//checking if the number of rows==0
		if($numQuizTakers > 0){
			echo "Sorry, there was a problem clearing the result. Please try again later.";
			exit();
		}else{
			echo "Result has been cleared!";
			exit();
		}
	}
?>






<?php
//if reset is clicked, check--
	if(isset($_POST['reset']) && $_POST['reset'] != ""){
		$reset = preg_replace('/^[a-z]/', "", $_POST['reset']);

	//resetting the tables
		$pdo->exec("TRUNCATE TABLE questions");
		$pdo->exec("TRUNCATE TABLE answers");

	 ///////Updating value of total questions in quizes
		$pdo->exec("UPDATE quizes SET total_questions=0");


 //checking if truncate is successful
	//getting rows from tables
        $stmt1 = $pdo->query("SELECT id FROM questions LIMIT 1");
        $stmt2 = $pdo->query("SELECT id FROM answers LIMIT 1");

	//getting number of rows that were returned
		$numQuestions = $stmt1->rowCount();
		$numAnswers = $stmt2->rowCount();
	//checking if the number of rows==0
		if($numQuestions > 0 || $numAnswers > 0){
			echo "Sorry, there was a problem reseting the quiz. Please try again later.";
			exit();
		}else{
			echo "Thanks! The all quizes have now been reset back to 0 questions.";
			exit();
		}
	}
?>




<?php
//if deleteQuiz is clicked, check--
	if(isset($_POST['deleteQuiz']) && $_POST['deleteQuiz'] != ""){
		$deleteQ = $_POST['deleteQuiz'];

	//resetting the tables
        $stmt = $pdo->prepare("SELECT id as quiz_id FROM quizes WHERE quiz_name = :quizName");
        $stmt->execute(['quizName' => $deleteQ]);
        $row = $stmt->fetch();
        $qz_id = $row['quiz_id'];

        $stmtDel = $pdo->prepare("DELETE FROM quizes WHERE id = :quizID");
        $stmtDel->execute(['quizID' => $qz_id]);

        $stmtDelQ = $pdo->prepare("DELETE FROM questions WHERE quiz_id = :quizID");
        $stmtDelQ->execute(['quizID' => $qz_id]);

        $stmtDelA = $pdo->prepare("DELETE FROM answers WHERE quiz_id = :quizID");
        $stmtDelA->execute(['quizID' => $qz_id]);

 //checking if delete is successful
	//getting rows from tables
        $stmt1 = $pdo->prepare("SELECT id FROM questions WHERE quiz_id = :quizID LIMIT 1");
        $stmt1->execute(['quizID' => $qz_id]);

        $stmt2 = $pdo->prepare("SELECT id FROM answers WHERE quiz_id = :quizID LIMIT 1");
        $stmt2->execute(['quizID' => $qz_id]);

        $stmt3 = $pdo->prepare("SELECT id FROM quizes WHERE id = :quizID LIMIT 1");
        $stmt3->execute(['quizID' => $qz_id]);

	//getting number of rows that were returned
		$qz_numQuestions = $stmt1->rowCount();
		$qz_numAnswers = $stmt2->rowCount();
		$qz_numQuizes = $stmt3->rowCount();
	//checking if the number of rows==0
		if($qz_numQuestions > 0 || $qz_numAnswers > 0 || $qz_numQuizes > 0)
			echo "Sorry, there was a problem deleting the /".htmlspecialchars($deleteQ)."/ quiz. Please try again later.";
		else
			echo "Thanks! The quiz /".htmlspecialchars($deleteQ)."/ has now been deleted.";

		exit();
	}
?>





<?php
//PHP to get everything for menu quiz Management!

	$quizSelect = "";
	$quizesMenu = "";

	$stmt = $pdo->query("SELECT id as quiz_id, quiz_name, display_questions, time_allotted FROM quizes");

	 //getting individual quiz's info!
		while($quizID_row = $stmt->fetch()){
			$m_quizID = $quizID_row['quiz_id'];
			$m_quiz_name = $quizID_row['quiz_name'];
			$m_disp_ques = $quizID_row['display_questions'];
			$m_time_alot = $quizID_row['time_allotted'];
		 //getting options for the selecting quiz part of create/edit question
			$quizSelect .= ' <option value="'.$m_quizID.'">'.$m_quiz_name.'</option>';
		 //getting the quiz menu!
			$quizesMenu .= '<li>'.$m_quiz_name.' (Q='.$m_disp_ques.', T='.$m_time_alot.')
								<ul>
									<li>Quiz Settings
										<ul>
											<a href="javascript:default_quiz(\''.$m_quiz_name.'\')">
												<li>Set Default</li>
											</a>
											<a href="javascript:update_quiz(\''.$m_quiz_name.'\')">
												<li>Update Metadata</li>
											</a>
											<a href="javascript:delete_quiz(\''.$m_quiz_name.'\')">
												<li>Delete this Quiz</li>
											</a>
										</ul>
									</li>

									<li>Manage Questions
										<ul>
											<a href="javascript:view_questions(\''.$m_quiz_name.'\')">
												<li>View all Questions</li>
											</a>
											<a href="javascript:edit_question(\''.$m_quiz_name.'\')">
												<li>Edit a Question</li>
											</a>
											<a href="javascript:delete_some_questions(\''.$m_quiz_name.'\')">
												<li>Delete Some Questions</li>
											</a>
										</ul>
									</li>



									<li>Results
										<ul>
											<a href="javascript:top_users(\''.$m_quiz_name.'\')">
												<li>Result(Top 20)</li>
											</a>
											<a href="javascript:all_users(\''.$m_quiz_name.'\')">
												<li>Result(All)</li>
											</a>
											<a href="javascript:clear_result(\''.$m_quizID.'\')">
												<li>Clear the Result</li>
											</a>
										</ul>
									</li>
								</ul>
							</li>';
		}
?>




<?php
//PHP for showing Top 20 Users

	if(isset($_POST['usersQuiz']) && $_POST['usersQuiz'] != ""){
		$usersQ = $_POST['usersQuiz'];

        $stmt = $pdo->prepare("SELECT id as quiz_id FROM quizes WHERE quiz_name = :quizName");
        $stmt->execute(['quizName' => $usersQ]);
        $row = $stmt->fetch();
		$get_quiz_id = $row['quiz_id'];

		$m_output=' <tr align="center">
						<th>Rank</th>
						<th>Roll No.</th>
						<th>Marks</th>
						<th>Percentage</th>
						<th>Time Taken</th>
						<th>TimeStamp</th>
					</tr>
				 ';

        $stmt = $pdo->prepare("SELECT * FROM quiz_takers WHERE quiz_id = :quizID ORDER BY marks desc, duration asc LIMIT 20");
        $stmt->execute(['quizID' => $get_quiz_id]);

			$m_display_ID = 1;

			while($m_row = $stmt->fetch()){
				$m_answers='';
			 //id var = id column and so on
				$m_id = $m_row['id'];
				$m_username = $m_row['username'];
				$m_marks = $m_row['marks'];
				$m_percentage = $m_row['percentage'];
				$m_duration = $m_row['duration'];
				$m_timestamp = $m_row['date_time'];


				$m_row = '<tr align="center">
							  <td>'.$m_display_ID.'</td>
							  <td>'.$m_username.'</td>
							  <td>'.$m_marks.'</td>
							  <td>'.$m_percentage.'</td>
							  <td>'.$m_duration.'</td>
							  <td>'.$m_timestamp.'</td>
						  </tr>
						 ';

			 // the complete div that is sent back to quiz.php
				$m_output .= $m_row;

				$m_display_ID++;
			}
			echo $m_output;
			exit();
	}
?>




<?php
//PHP for showing All Users

	if(isset($_POST['usersAll']) && $_POST['usersAll'] != ""){
		$usersQ = $_POST['usersAll'];

        $stmt = $pdo->prepare("SELECT id as quiz_id FROM quizes WHERE quiz_name = :quizName");
        $stmt->execute(['quizName' => $usersQ]);
        $row = $stmt->fetch();
		$get_quiz_id = $row['quiz_id'];

		$m_output=' <tr align="center">
						<th>Rank</th>
						<th>Roll No.</th>
						<th>Marks</th>
						<th>Percentage</th>
						<th>Time Taken</th>
						<th>TimeStamp</th>
					</tr>
				 ';

        $stmt = $pdo->prepare("SELECT * FROM quiz_takers WHERE quiz_id = :quizID ORDER BY marks desc, duration asc");
        $stmt->execute(['quizID' => $get_quiz_id]);

			$m_display_ID = 1;

			while($m_row = $stmt->fetch()){
				$m_answers='';
			 //id var = id column and so on
				$m_id = $m_row['id'];
				$m_username = $m_row['username'];
				$m_marks = $m_row['marks'];
				$m_percentage = $m_row['percentage'];
				$m_duration = $m_row['duration'];
				$m_timestamp = $m_row['date_time'];


				$m_row = '<tr align="center">
							  <td>'.$m_display_ID.'</td>
							  <td>'.$m_username.'</td>
							  <td>'.$m_marks.'</td>
							  <td>'.$m_percentage.'</td>
							  <td>'.$m_duration.'</td>
							  <td>'.$m_timestamp.'</td>
						  </tr>
						 ';

			 // the complete div that is sent back to quiz.php
				$m_output .= $m_row;

				$m_display_ID++;
			}
			echo $m_output;
			exit();
	}
?>





<?php
//PHP for showing all the questions

	if(isset($_POST['questionsQuiz']) && $_POST['questionsQuiz'] != ""){
		$questionsQ = $_POST['questionsQuiz'];

        $get_quiz_id = '';
        if($questionsQ != 'allthequestions') {
            $stmt = $pdo->prepare("SELECT id as quiz_id FROM quizes WHERE quiz_name = :quizName");
            $stmt->execute(['quizName' => $questionsQ]);
            $row = $stmt->fetch();
            $get_quiz_id = $row['quiz_id'];
        }

		$m_output='';

		if($questionsQ=='allthequestions') {
			$stmt = $pdo->query("SELECT questions.*, quizes.quiz_name FROM questions JOIN quizes ON questions.quiz_id = quizes.quiz_id");
        } else {
            $stmt = $pdo->prepare("SELECT questions.*, quizes.quiz_name FROM questions JOIN quizes ON questions.quiz_id = quizes.quiz_id WHERE questions.quiz_id = :quizID");
            $stmt->execute(['quizID' => $get_quiz_id]);
        }

            $all_questions = $stmt->fetchAll();

            // Gather all question IDs
            $question_ids = [];
            foreach ($all_questions as $q) {
                $question_ids[] = $q['question_id'];
            }

            // Fetch all answers in one go
            $answers_map = [];
            if (!empty($question_ids)) {
                $placeholders = implode(',', array_fill(0, count($question_ids), '?'));
                $stmtAns = $pdo->prepare("SELECT * FROM answers WHERE question_id IN ($placeholders)");
                $stmtAns->execute($question_ids);
                while ($row = $stmtAns->fetch()) {
                    $answers_map[$row['question_id']][] = $row;
                }
            }

			$m_display_ID = 1;

			foreach($all_questions as $m_row){
				$m_answers='';
			 //id var = id column and so on
				$m_id = $m_row['id'];
				$m_thisQuestion = $m_row['question'];
				$m_type = $m_row['type'];
				$m_question_id = $m_row['question_id'];
				$m_code = $m_row['code'];
				$m_code_type = $m_row['code_type'];
				$m_quiz_id = $m_row['quiz_id'];
				$m_quiz_name = $m_row['quiz_name'];

			 //putting the question in h2 tag
				$m_q = '<tr>
							<td width="40px" rowspan="1" align="center">';

				if($questionsQ=='allthequestions'){
					$m_q .=	'<br>
								<strong>'.$m_display_ID.'.</strong>
							</td>
							<td>
								<small><i>('.$m_quiz_name.')</i></small><br>';
				}else{
					$m_q .= '	<strong>'.$m_display_ID.'.</strong>
							</td>
							<td>';
				}
						$m_q .=	'<pre class="question_style"><strong><div style="width: 730px; word-wrap: break-word;">'.$m_thisQuestion.'</div></strong></pre>
							</td>
						</tr>';


				if($m_code != "" && $m_code_type != ""){
					$m_q .='<tr>
							<td></td>
							<td>
								<pre class="brush: '.$m_code_type.';">'.$m_code.'</pre>
							</td>
						</tr>
						';
				}

			 //gathering answers of question here
				$m_answers .=  '<tr>
									<td></td>
									<td>
										<ol type="a">
								';

					if (isset($answers_map[$m_question_id])) {
						foreach($answers_map[$m_question_id] as $m_row2){
						//putting column values in variables
							$m_answer = $m_row2['answer'];
							$m_correct = $m_row2['correct'];

							if($m_correct == 1)
								$m_answers .= '<u><i>';
							$m_answers .= '<div style="width: 730px; word-wrap: break-word;"><li>'.$m_answer.'</li></div>';
							if($m_correct == 1)
								$m_answers .= '</i></u>';
						}
					}

				$m_answers .=  '		</ol>
									</td>
								</tr>
								<tr height="20px"></tr>
								';

			 // the complete div that is sent back to quiz.php
				$m_output .= ''.$m_q.$m_answers;

				$m_display_ID++;
			}
			echo $m_output;
			exit();
	}
?>




































<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">

		<title>Admin</title>

		<!-- ****** faviconit.com favicons ****** -->
            <!-- Basic favicons -->
                <link rel="shortcut icon" sizes="16x16 32x32 48x48 64x64" href="img/faviconit/favicon.ico">
                <link rel="shortcut icon" type="image/x-icon" href="img/faviconit/favicon.ico">

            <!--[if IE]><link rel="shortcut icon" href="/favicon.ico"><![endif]-->

            <!-- For Opera Speed Dial -->
                <link rel="icon" type="image/png" sizes="195x195" href="img/faviconit/favicon-195.png">
            <!-- For iPad with high-resolution Retina Display running iOS ≥ 7 -->
                <link rel="apple-touch-icon" sizes="152x152" href="img/faviconit/favicon-152.png">
            <!-- For iPad with high-resolution Retina Display running iOS ≤ 6 -->
                <link rel="apple-touch-icon" sizes="144x144" href="img/faviconit/favicon-144.png">
            <!-- For iPhone with high-resolution Retina Display running iOS ≥ 7 -->
                <link rel="apple-touch-icon" sizes="120x120" href="img/faviconit/favicon-120.png">
            <!-- For iPhone with high-resolution Retina Display running iOS ≤ 6 -->
                <link rel="apple-touch-icon" sizes="114x114" href="img/faviconit/favicon-114.png">
            <!-- For Google TV devices -->
                <link rel="icon" type="image/png" sizes="96x96" href="img/faviconit/favicon-96.png">
            <!-- For iPad Mini -->
                <link rel="apple-touch-icon" sizes="76x76" href="img/faviconit/favicon-76.png">
            <!-- For first- and second-generation iPad -->
                <link rel="apple-touch-icon" sizes="72x72" href="img/faviconit/favicon-72.png">
            <!-- For non-Retina iPhone, iPod Touch and Android 2.1+ devices -->
                <link rel="apple-touch-icon" href="img/faviconit/favicon-57.png">
            <!-- Windows 8 Tiles -->
                <meta name="msapplication-TileColor" content="#FFFFFF">
                <meta name="msapplication-TileImage" content="img/faviconit/favicon-144.png">
        <!-- ****** faviconit.com favicons ****** -->

	    <!-- SYNTAX HIGHLIGHTER -->
			<link rel="stylesheet" type="text/css" href="sh/styles/shCore.css">
			<link rel="stylesheet" type="text/css" href="sh/styles/shThemeDefault.css">
			<script type="text/javascript" src="sh/scripts/shCore.js"></script>
		   <!-- INCLUDING ALL BRUSHES SCRIPTS -->
			<script type="text/javascript" src="sh/scripts/shBrushAppleScript.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushAS3.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushBash.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushColdFusion.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushCpp.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushCSharp.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushCss.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushDelphi.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushDiff.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushErlang.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushGroovy.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushJava.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushJavaFX.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushJScript.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushPerl.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushPhp.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushPlain.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushPowerShell.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushPython.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushRuby.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushSass.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushScala.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushSql.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushVb.js"></script>
			<script type="text/javascript" src="sh/scripts/shBrushXml.js"></script>
			<script type="text/javascript">
			    SyntaxHighlighter.all()
			</script>
		<!-- SYNTAX HIGHLIGHTER -->

	<!-- BUGGY CODE INPUT FIELD -->
		<link rel="stylesheet" href="codemirror/lib/codemirror.css">
		<script type="text/javascript" src="codemirror/codemirror-compressed.js"></script>
		<script type="text/javascript" src="codemirror/addon/mode/loadmode.js"></script>
	<!--
		<script src="codemirror/lib/codemirror.js"></script>

		<script src="codemirror/mode/clike/clike.js"></script>
		<script src="codemirror/mode/css/css.js"></script>
		<script src="codemirror/mode/diff/diff.js"></script>
		<script src="codemirror/mode/erlang/erlang.js"></script>
		<script src="codemirror/mode/groovy/groovy.js"></script>
		<script src="codemirror/mode/javascript/javascript.js"></script>
		<script src="codemirror/mode/perl/perl.js"></script>
		<script src="codemirror/mode/php/php.js"></script>
		<script src="codemirror/mode/python/python.js"></script>
		<script src="codemirror/mode/ruby/ruby.js"></script>
		<script src="codemirror/mode/sass/sass.js"></script>
		<script src="codemirror/mode/scala/scala.js"></script>
		<script src="codemirror/mode/shell/shell.js"></script>
		<script src="codemirror/mode/sql/sql.js"></script>
		<script src="codemirror/mode/vb/vb.js"></script>
		<script src="codemirror/mode/xml/xml.js"></script>
	-->

		<link rel="stylesheet" type="text/css" href="css/admin.css">



		<script type="text/javascript">
		//CSRF token appended to every XHR body (mirrors the hidden input in each form)
			function csrfQS(){
				return '&csrf_token=' + encodeURIComponent(document.querySelector('input[name=csrf_token]').value);
			}

		//displaying different code-blocks on button click
			function showDiv(el1,el2,el3){
				document.getElementById(el1).style.display = 'block';
				document.getElementById(el2).style.display = 'none';
				document.getElementById(el3).style.display = 'none';
			}

		 //hide all divs
			function hideDivs(){
				document.getElementById('tf').style.display = 'none';
				document.getElementById('mc').style.display = 'none';
				document.getElementById('quesans').style.display = 'none';
			}



/*SETTING TIMELOCKS
			function set_timelock(quizz){

			}

			function remove_timelock(quizzz){

			}
*/


			function clear_result(qquizID){
				if(confirm("Really wanna clear the result of all users of this quiz?!")) {
					var x = new XMLHttpRequest();
					var url = "admin.php";
					var vars = 'clearResult='+qquizID;
					x.open("POST", url, true);
					x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
					x.onreadystatechange = function() {
						if(x.readyState == 4 && x.status == 200) {
							document.getElementById("msg").innerHTML = x.responseText;
						}
					}
					x.send(vars + csrfQS());
					document.getElementById("msg").innerHTML = "processing...";
				}
			}




			function reset_tables(){
				if(confirm("Really wanna reset all the tables?!")) {
					if(confirm("Your admin ID will be \'admin\' and password \'12345\'")){
						var x = new XMLHttpRequest();
						var url = "admin.php";
						var vars = 'resetTables='+'yes';
						x.open("POST", url, true);
						x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
						x.onreadystatechange = function() {
							if(x.readyState == 4 && x.status == 200) {
								window.open("login.php?user_msg="+x.responseText, "_self");
							}
						}
						x.send(vars + csrfQS());
						document.getElementById("msg").innerHTML = "processing...";
					}
				}
			}




			function delete_account(){
				if(confirm("Really wanna delete this admin Account?!")) {
					var x = new XMLHttpRequest();
					var url = "admin.php";
					var vars = 'deleteAdmin='+'<?php echo $login_session; ?>';
					x.open("POST", url, true);
					x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
					x.onreadystatechange = function() {
						if(x.readyState == 4 && x.status == 200) {
							document.getElementById("msg").innerHTML = x.responseText;
						}
					}
					x.send(vars + csrfQS());
					document.getElementById("msg").innerHTML = "processing...";
				}
			}


			function top_users(qiiizName){
				var x = new XMLHttpRequest();
				var url = "admin.php";
				var vars = 'usersQuiz='+qiiizName;
				x.open("POST", url, true);
				x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				x.onreadystatechange = function() {
					if(x.readyState == 4 && x.status == 200) {
						showDiv('quesans', 'mc', 'tf');
						document.getElementById("quesans_table").innerHTML = x.responseText;
						document.getElementById("msg").innerHTML = "";

					}
				}
				x.send(vars + csrfQS());
				document.getElementById("msg").innerHTML = "processing...";
			}



			function all_users(qqizName){
				var x = new XMLHttpRequest();
				var url = "admin.php";
				var vars = 'usersAll='+qqizName;
				x.open("POST", url, true);
				x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				x.onreadystatechange = function() {
					if(x.readyState == 4 && x.status == 200) {
						showDiv('quesans', 'mc', 'tf');
						document.getElementById("quesans_table").innerHTML = x.responseText;
						document.getElementById("msg").innerHTML = "";
					}
				}
				x.send(vars + csrfQS());
				document.getElementById("msg").innerHTML = "processing...";
			}


			function delete_some_questions(quizzName){
				var x = new XMLHttpRequest();
				var url = "admin.php";
				var vars = 'deleteSomeQuestions='+quizzName;
				x.open("POST", url, true);
				x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				x.onreadystatechange = function() {
					if(x.readyState == 4 && x.status == 200) {
						showDiv('quesans', 'mc', 'tf');
						document.getElementById("quesans_table").innerHTML = x.responseText;
						SyntaxHighlighter.highlight();
						document.getElementById("msg").innerHTML = "";
					}
				}
				x.send(vars + csrfQS());
				document.getElementById("msg").innerHTML = "processing...";
			}



			function edit_question(qzname){
				var x = new XMLHttpRequest();
				var url = "admin.php";
				var vars = 'editaquestion='+qzname;
				x.open("POST", url, true);
				x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				x.onreadystatechange = function() {
					if(x.readyState == 4 && x.status == 200) {
						showDiv('quesans', 'mc', 'tf');
						document.getElementById("quesans_table").innerHTML = x.responseText;
						SyntaxHighlighter.highlight();
						document.getElementById("msg").innerHTML = "";
					}
				}
				x.send(vars + csrfQS());
				document.getElementById("msg").innerHTML = "processing...";
			}


			function editQ_submit(){
				document.deleteedit.action = "admin.php";
	            document.getElementById('deleteedit').submit();
	        }


			function view_questions(qiizName){
				var x = new XMLHttpRequest();
				var url = "admin.php";
				var vars = 'questionsQuiz='+qiizName;
				x.open("POST", url, true);
				x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				x.onreadystatechange = function() {
					if(x.readyState == 4 && x.status == 200) {
						showDiv('quesans', 'mc', 'tf');
						document.getElementById("quesans_table").innerHTML = x.responseText;
						SyntaxHighlighter.highlight();
						document.getElementById("msg").innerHTML = "";
					}
				}
				x.send(vars + csrfQS());
				document.getElementById("msg").innerHTML = "processing...";
			}

			function default_quiz(qizName){
				var x = new XMLHttpRequest();
				var url = "admin.php";
				var vars = 'defaultQuiz='+qizName;
				x.open("POST", url, true);
				x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				x.onreadystatechange = function() {
					if(x.readyState == 4 && x.status == 200) {
						document.getElementById("resetBtnMsg").innerHTML = x.responseText;
					}
				}
				x.send(vars + csrfQS());
				document.getElementById("resetBtnMsg").innerHTML = "processing...";
			}


		//truncating the tables and resetting the quiz
			function delete_quiz(qzName){
				if(confirm("Really wanna delete this Quiz and all its Questions?!")) {
					var x = new XMLHttpRequest();
					var url = "admin.php";
					var vars = 'deleteQuiz='+qzName;
					x.open("POST", url, true);
					x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
					x.onreadystatechange = function() {
						if(x.readyState == 4 && x.status == 200) {
							document.getElementById("resetBtnMsg").innerHTML = x.responseText;
						}
					}
					x.send(vars + csrfQS());
					document.getElementById("resetBtnMsg").innerHTML = "processing...";
				}
			}


		 //truncating the tables and resetting the quiz
			function resetQuiz(){
				if(confirm("Really wanna delete all the Questions from all the quizes?!")) {
					var x = new XMLHttpRequest();
					var url = "admin.php";
					var vars = 'reset=yes';
					x.open("POST", url, true);
					x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
					x.onreadystatechange = function() {
						if(x.readyState == 4 && x.status == 200) {
							document.getElementById("resetBtnMsg").innerHTML = x.responseText;
						}
					}
					x.send(vars + csrfQS());
					document.getElementById("resetBtnMsg").innerHTML = "processing...";
				}
			}
		</script>

		<script type="text/javascript">
	 //updating metadata for a quiz
		function update_quiz(qname){
			open_overlay('regNewQuiz', 'regNewAdmin');
			document.newQuiz_name.action = "updateExistingQuiz.php";
			document.getElementById('quizName').value = qname;
			document.getElementById('quizName').readOnly = true;
			document.getElementById('quizTime').focus();
		}


		function change_pass(){
			open_overlay('regNewAdmin', 'regNewQuiz');
			document.reg_name.action = "changePassword.php";
			document.getElementById('login').value = '<?php echo $login_session; ?>';
			document.getElementById('login').readOnly = true;
			document.getElementById("password").focus();
		}

		</script>

		<script type="text/javascript">
		//overlays
			//hiding the overlay
			    function close_overlay(){
			        document.getElementById('register').style.display='none';
			        document.getElementById('fade').style.display='none';
			    }

			//showing the overlay
			    function open_overlay(ele1, ele2){

				document.getElementById(ele1).style.display = 'block';
					document.getElementById(ele2).style.display = 'none';

			        document.getElementById('register').style.display='block';
			        document.getElementById('fade').style.display='block';

				if(ele1=='regNewAdmin'){
					document.getElementById("register").style.height = '200px';
					document.getElementById("login").focus();
				}
				else{
					document.getElementById("register").style.height = '300px';
					document.getElementById("quizName").focus();
				}
			    }
		</script>

		<link rel="stylesheet" type="text/css" href="css/register.css">
		<link rel="stylesheet" type="text/css" href="css/addNewQuiz.css">

		<script type="text/javascript">

		function submit_admin(){
			var x=document.forms["reg_name"]["login"].value;
			var y=document.forms["reg_name"]["password"].value;
            if (x==null || x=="" || y==null || y==""){
                document.getElementById("required").innerHTML = "Enter Both Values";
                exit();
            }
			close_overlay();
            document.getElementById('reg_name').submit();
            return false;
		}

		</script>

		<script type="text/javascript">
			function quiz_submit(){
				document.deleteedit.action = "deleteSomeQues.php";
	            document.getElementById('deleteedit').submit();
	        }
		</script>

	</head>

	<body style="font-family: Arial;">

		<div id="head" align="center">
            <img src="img/header.jpg" alt="Chandigarh Engineering College" />
        </div>


		<div id="admin_menu">
			<p style="color:#06F;" id="msg">
				<?php echo $msg; ?>
			</p>
			<br><br>
			<ul>
				<span id="Hello">Hello, <a href="admin.php"><span id="usr"><?php echo $login_session; ?>!</span></a></span>

				<a href="index.php" target="_blank">
					<li>Quiz Homepage</li>
				</a>

				<li>Manage Questions
					<ul>
						<li>Create a Question
							<ul>
							<a href="javascript:showDiv('tf', 'mc', 'quesans');">
								<li>True/False</li>
							</a>
							<a href="javascript:showDiv('mc', 'tf', 'quesans');">
								<li>Multiple Choice</li>
							</a>
						</ul>
						</li>
					<a href="javascript:view_questions('allthequestions');">
							<li>View All Questions</li>
						</a>
						<a href="javascript:edit_question('allthequestions');">
							<li>Edit a Question</li>
						</a>
						<a href="javascript:delete_some_questions('allthequestions');">
							<li>Delete Some Questions</li>
						</a>
					<a href="javascript:resetQuiz();">
							<li>Delete all Questions</li>
						</a>
					</ul>
			    </li>
				<li>Quiz Management
					<ul>
						<a href="javascript:open_overlay('regNewQuiz','regNewAdmin');">
							<li><span class="plus">+&nbsp;</span> Add New Quiz</li>
						</a>
						<?php echo $quizesMenu; ?>
					</ul>
				</li>

				<li>Settings
				<ul>
					<a href="javascript:open_overlay('regNewAdmin','regNewQuiz');">
						<li>Register an Admin</li>
					</a>
					<a href="javascript:change_pass();">
						<li>Change Password</li>
					</a>
					<a href="javascript:delete_account();">
						<li>Delete Your Account</li>
					</a>
					<a href="javascript:reset_tables();">
						<li>Reset all Tables</li>
					</a>
					<a href="logout.php">
						<li>LogOut</li>
					</a>
				</ul>
				</li>
			</ul>

		    <br /><br />

		    <span id="resetBtnMsg"></span>
		</div>


		<div class="content" id="tf" style="margin-bottom: 100px;">
			<h2>True or false</h2>

		<form action="admin.php" name="addQuestion" method="POST">
			<?php echo csrf_field(); ?>

			<strong>Select the quiz in which to enter the Question</strong>
			<select class="quizIDselect" name="quizID" id="quizIDtf">
				<?php echo $quizSelect; ?>
			</select>
			<br />
			<br />

			<strong>Please type your new question here:</strong>
			<br />

			<textarea class="txt_area" id="tfDesc" name="desc"></textarea>
			<br />
			<br />


			<strong>If there's a programming code, enter here</strong>
			<br />

			<strong style="font-family: Times;">Select Language of the code: </strong>
			<span class='css-select-moz'>
				<select class="lang_selector" name="prog-lang" onchange="lang_chosen(this);" id="prog-lang-tf">
					<option value=""> ------ </option>
						<option value="applescript">AppleScript</option>
						<option value="actionscript3">ActionScript3</option>
						<option value="shell">Bash/Shell</option>
						<option value="coldfusion">ColdFusion</option>
						<option value="csharp">C#</option>
						<option value="cpp">C/C++</option>
						<option value="css">CSS</option>
						<option value="delphi">Delphi</option>
						<option value="diff">Diff</option>
						<option value="erlang">Erlang</option>
						<option value="groovy">Groovy</option>
						<option value="js">JavaScript</option>
						<option value="java">Java</option>
						<option value="javafx">JavaFX</option>
						<option value="perl">Perl</option>
						<option value="php">PHP</option>
						<option value="plain">Plain Text</option>
						<option value="powershell">PowerShell</option>
						<option value="python">Python</option>
						<option value="ruby">Ruby on Rails</option>
						<option value="sass">Sass</option>
						<option value="scala">Scala</option>
						<option value="sql">SQL</option>
						<option value="vbnet">VB.net</option>
						<option value="html">HTML/XML/xHTML/XSLT</option>
					</select>
				</span>
			<br />
			<textarea class="txt_area" id="tfcodeDesc" name="code_desc" style="width:400px;height:95px;"></textarea>
			<br />

			<br />


			<strong>Select whether true or false is the Correct Answer</strong>
			<br />

		<input type="text" class="tf_txt_box" id="answer1" name="answer1" value="True" readonly>&nbsp;
		<label style="cursor:pointer; color:#555;">
			<input type="radio" id="tfans1" name="iscorrect" value="answer1">Correct Answer?
		</label>
			<br />
				<br />
		<input type="text" class="tf_txt_box" id="answer2" name="answer2" value="False" readonly>&nbsp;
		<label style="cursor:pointer; color:#555;">
			<input type="radio" id="tfans2" name="iscorrect" value="answer2">Correct Answer?
		</label>


			<br />
			<br />


			<input type="hidden" value="tf" name="type">
			<input type="hidden" value="<?php echo $gaq_question_id; ?>" name="questionID">
				<input type="submit" class="add_to_quiz" id="addToQuizTF" value="Add To Quiz">
		</form>
		</div>


		<div class="content" id="mc" style="margin-bottom: 100px;">
			<h2>Multiple Choice</h2>

		<form action="admin.php" name="addMcQuestion" method="POST">
			<?php echo csrf_field(); ?>

			<strong>Select the quiz in which to enter the Question</strong>
			<select class="quizIDselect" name="quizID" id="quizIDmc">
				<?php echo $quizSelect; ?>
			</select>
			<br />
			<br />

			<strong>Please type your new question here:</strong>
			<br />

			<textarea class="txt_area" id="mcdesc" name="desc"></textarea>
			<br />
			<br />


			<strong>If there's a programming code, enter here</strong>
			<br />

			<strong style="font-family: Times;">Select Language of the code: </strong>
			<span class='css-select-moz'>
				<select class="lang_selector" name="prog-lang" onchange="lang_chosen(this);" id="prog-lang-mc">
					<option value=""> ------ </option>
						<option value="applescript">AppleScript</option>
						<option value="actionscript3">ActionScript3</option>
						<option value="shell">Bash/Shell</option>
						<option value="coldfusion">ColdFusion</option>
						<option value="csharp">C#</option>
						<option value="cpp">C/C++</option>
						<option value="css">CSS</option>
						<option value="delphi">Delphi</option>
						<option value="diff">Diff</option>
						<option value="erlang">Erlang</option>
						<option value="groovy">Groovy</option>
						<option value="js">JavaScript</option>
						<option value="java">Java</option>
						<option value="javafx">JavaFX</option>
						<option value="perl">Perl</option>
						<option value="php">PHP</option>
						<option value="plain">Plain Text</option>
						<option value="powershell">PowerShell</option>
						<option value="python">Python</option>
						<option value="ruby">Ruby on Rails</option>
						<option value="sass">Sass</option>
						<option value="scala">Scala</option>
						<option value="sql">SQL</option>
						<option value="vbnet">VB.net</option>
						<option value="html">HTML/XML/xHTML/XSLT</option>
					</select>
				</span>
				<br />
			<pre><textarea class="txt_area" id="mccodeDesc" name="code_desc" style="width:400px;height:95px;"></textarea>
			</pre>

			<br />
			<br />


			<strong>First Option</strong>
			<br />
			<input type="text" class="mc_txt_box" id="mcanswer1" name="answer1">&nbsp;
			<label style="cursor:pointer; color:#555;">
				<input type="radio" id="mcans1" name="iscorrect" value="answer1">Correct Answer?
			</label>
			<br />
			<br />
			<strong>Second Option</strong>
			<br />
			<input type="text" class="mc_txt_box" id="mcanswer2" name="answer2">&nbsp;
			<label style="cursor:pointer; color:#555;">
				<input type="radio" id="mcans2" name="iscorrect" value="answer2">Correct Answer?
			</label>
			<br />
			<br />
			<strong>Third Option</strong>
			<br />
			<input type="text" class="mc_txt_box" id="mcanswer3" name="answer3">&nbsp;
			<label style="cursor:pointer; color:#555;">
				<input type="radio"  id="mcans3" name="iscorrect" value="answer3">Correct Answer?
			</label>
			<br />
			<br />
			<strong>Fourth Option</strong>
			<br />
			<input type="text" class="mc_txt_box" id="mcanswer4" name="answer4">&nbsp;
			<label style="cursor:pointer; color:#555;">
				<input type="radio"  id="mcans4" name="iscorrect" value="answer4">Correct Answer?
			</label>
			<br />
			<br />
			<input type="hidden" value="mc" name="type">
			<input type="hidden" value="<?php echo $gaq_question_id; ?>" name="questionID">
			<input type="submit" class="add_to_quiz" id="addToQuizMC" value="Add To Quiz">
		</form>
		</div>


		<div class="content" id="quesans"  style="margin-bottom: 100px;">
			<form id="deleteedit" name="deleteedit" action="deleteSomeQues.php" method="POST">
				<?php echo csrf_field(); ?>
				<table width="780px" align="center" id="quesans_table">
				</table>
			</form>
		</div>


		<div id="register" class="white_content">

            <form action="register.php" class="login" method="POST" name="reg_name" id="regNewAdmin">
			<?php echo csrf_field(); ?>
			<p>
			      <label class="reg_label" for="login">Choose a Username:</label>
			      <input type="text" name="login" id="login" required="required">
			    </p>
			    <p>
			      <label class="reg_label" for="password">Choose a Password:</label>
			      <input type="password" name="password" id="password" required="required">
			    </p>
			    <p class="login-submit">
			      <button  onClick="submit_admin()" id="reg_button" class="login-button">Register</button>
			    </p>
			    <p id="required"></p>
			</form>

			<form action="addNewQuiz.php" class="newQuiz" method="POST" name="newQuiz_name" id="regNewQuiz">
				<?php echo csrf_field(); ?>
				<p>
			      <label class="reg_label" for="quizName">Quiz Name:</label><br>
			      <input type="text" name="quizName" id="quizName" required="required">
			    </p>
			    <p>
			      <label class="reg_label" for="quizTime">Time Allotted:</label><br>
			      <input type="text" name="quizTime" id="quizTime" required="required">
			    </p>
			    <p>
			      <label class="reg_label" for="numQues">No. of Questions to Display:</label><br>
			      <input type="text" name="numQues" id="numQues" required="required">
			    </p>
			    <p class="addQuiz-submit">
			      <button  onClick="submit()" id="addQuiz_button" class="addQuiz-button">Add</button>
			    </p>
			    <p id="required"></p>
			</form>

        </div>



        <div id="fade_overlay">
            <a href="javascript:close_overlay();" style="cursor: default;">
                <div id="fade" class="black_overlay">
                </div>
            </a>
        </div>

        <br><BR><BR><BR><br><BR><BR><BR>

		<div id="footer" align="bottom">
            <table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
                <tbody>
                    <tr>
                        <td align="left" id="copyright">
                            © Copyright 2014, under
                            <a href="gnu_gpl.txt" style="color: WHITE; text-decoration: none;" target="_blank">
				GNU General Public License
                            </a>
                        </td>

                        <td align="right" id="developer" >
                            Quiz Designed &amp; Developed by :
                            <a href="mailto: rahul_jain@live.in" class="flink" style="color: #c4dcf5">
                                Rahul Jain<div id="dev_info">1139234/CSE/6thSEM</div>
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>












    <!-- Script for the codeMirror api -->
        <script type="text/javascript">
            CodeMirror.modeURL = "codemirror/mode/%N/%N.js";

	        var tfeditor = CodeMirror.fromTextArea(document.getElementById("tfcodeDesc"), {
										lineNumbers: true,
								        matchBrackets: true,
								        indentUnit: 4,
								        indentWithTabs: true,
								        smartIndent: true,
								        styleActiveLine: true,
								        autoCloseBrackets: true,
								        autoCloseTags: true,
								        viewportMargin: Infinity,
								        fixedGutter: true
							});
			 var mceditor = CodeMirror.fromTextArea(document.getElementById("mccodeDesc"), {
										lineNumbers: true,
								        matchBrackets: true,
								        indentUnit: 4,
								        indentWithTabs: true,
								        smartIndent: true,
								        styleActiveLine: true,
								        autoCloseBrackets: true,
								        autoCloseTags: true,
								        viewportMargin: Infinity,
								        fixedGutter: true
							});



			 //JS for changing the textarea
				function lang_chosen(selectObj){
				 // get the index of the selected option
					var idx = selectObj.selectedIndex;
				 // get the value of the selected option
					var which = selectObj.options[idx].value;

					change_editor(which);
				}

				function change_editor(which){
                    var changedMode = "text/plain";
                    var modeName = "";

					if(which=="cpp") { changedMode = "text/x-c++src"; modeName = "clike"; }
					else if(which=="css") { changedMode = "text/css"; modeName = "css"; }
					else if(which=="diff") { changedMode = "text/x-diff"; modeName = "diff"; }
					else if(which=="erlang") { changedMode = "text/x-erlang"; modeName = "erlang"; }
					else if(which=="groovy") { changedMode = "text/x-groovy"; modeName = "groovy"; }
					else if(which=="java" || which=="javafx") { changedMode = "text/x-java"; modeName = "clike"; }
					else if(which=="js") { changedMode = "text/javascript"; modeName = "javascript"; }
					else if(which=="perl") { changedMode = "text/x-perl"; modeName = "perl"; }
					else if(which=="php") { changedMode = "text/x-httpd-php"; modeName = "php"; }
					else if(which=="python") { changedMode = "text/x-python"; modeName = "python"; }
					else if(which=="ruby") { changedMode = "text/x-ruby"; modeName = "ruby"; }
					else if(which=="sass") { changedMode = "text/x-sass"; modeName = "sass"; }
					else if(which=="scala") { changedMode = "text/x-scala"; modeName = "clike"; }
					else if(which=="shell") { changedMode = "text/x-sh"; modeName = "shell"; }
					else if(which=="sql") { changedMode = "text/x-sql"; modeName = "sql"; }
					else if(which=="vbnet") { changedMode = "text/x-vb"; modeName = "vb"; }
					else if(which=="html") { changedMode = "text/x-html"; modeName = "xml"; }
					else if(which=="csharp") { changedMode = "text/x-csharp"; modeName = "clike"; }
                    else if(which=="applescript") { modeName = "applescript"; }
                    else if(which=="actionscript3") { modeName = "clike"; }
                    else if(which=="coldfusion") { modeName = "coldfusion"; }
                    else if(which=="delphi") { changedMode = "text/x-pascal"; modeName = "pascal"; }
                    else if(which=="powershell") { modeName = "powershell"; }

                    if (modeName) {
					CodeMirror.autoLoadMode(tfeditor, modeName);
					CodeMirror.autoLoadMode(mceditor, modeName);
                    }

					tfeditor.setOption("mode", changedMode);
					mceditor.setOption("mode", changedMode);
				}


        </script>

        <?php echo $editQoutput; ?>

	</body>
</html>
