<?php

	/*
    Short Programming Quiz Framework
        Copyright (C) 2014  Rahul Jain
    */

    require_once("session.php");
    require_once __DIR__ . '/lib/csrf.php';
    csrf_verify();

    require_once __DIR__ . '/lib/headers.php';
    send_security_headers();

    require_once __DIR__ . '/lib/render.php';
    require_once __DIR__ . '/lib/audit.php';
	use function App\fetch_answers_by_question_ids;
	use function App\render_questions_table;
	use function App\render_results_table;

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
     // Values are stored RAW and escaped at output time (T4.4).

	 //if its a true/false question, do this-
		if($type == 'tf'){
		 //if any field is null or empty, say sorry ('0' is legitimate content)
			if($question=='' || $answer1=='' || $answer2=='' || $isCorrect==''){
				echo "Sorry, All fields must be filled in to add a new question to the quiz. Please press back in your browser and try again.";
				exit();
			}
		}

	 //if its a multiple choice question, do this-
		if($type == 'mc'){
		 //if any field is null or empty, say sorry ('0' is legitimate content)
			if($question=='' || $answer1=='' || $answer2=='' || $answer3=='' || $answer4=='' || $isCorrect==''){
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

	 //transactional write: question -> denormalized question_id -> quiz counter -> answers
		try{
			$pdo->beginTransaction();

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

		 /// Insert answers based on which is correct //////////////
			if(!empty($answerRows)){
				$stmtInsert = $pdo->prepare("INSERT INTO answers (quiz_id, question_id, answer, correct) VALUES (:quizID, :questionID, :answer, :correct)");
				foreach($answerRows as $answerRow){
					$stmtInsert->execute([
						'quizID' => $quizID,
						'questionID' => $lastId,
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
			$msg = 'Thanks, question no.'.$lastId.' has been added';
			header('location: admin.php?msg='.urlencode($msg));
			exit();
		}
	}
?>


<?php
//showing the message back to the user after a transaction is completed
//stored raw; escaped once at the echo site
	$msg = "";
	if(isset($_GET['msg'])){
		$msg = $_GET['msg'];
	}

	if(isset($_POST['msg'])){
		$msg = $_POST['msg'];
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

            audit_log('reset_tables', 'all tables truncated; default admin restored');
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

        if($editQ != 'allthequestions') {
		$stmt = $pdo->prepare("SELECT id as quiz_id FROM quizes WHERE quiz_name = :quizName");
            $stmt->execute(['quizName' => $editQ]);
            $row = $stmt->fetch();
		$get_quiz_id = $row['quiz_id'];
        }

		$isAll = ($editQ == 'allthequestions');

		if($isAll) {
			$stmt = $pdo->query("SELECT questions.*, quizes.quiz_name FROM questions LEFT JOIN quizes ON questions.quiz_id = quizes.quiz_id");
        } else {
            $stmt = $pdo->prepare("SELECT questions.*, quizes.quiz_name FROM questions LEFT JOIN quizes ON questions.quiz_id = quizes.quiz_id WHERE questions.quiz_id = :quizID");
            $stmt->execute(['quizID' => $get_quiz_id]);
        }

			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if($isAll){
				foreach($rows as $idx => $r){
					$rows[$idx]['_all'] = true;
				}
			}

			echo render_questions_table($rows, fetch_answers_by_question_ids($pdo, array_column($rows, 'question_id')), 'radio');
			exit();
	}
?>



<?php
//When editAQ is clicked
	$editQops = array();
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
	 //storage is raw since migration 005b + T4.4: no decode needed

        $stmtAns = $pdo->prepare("SELECT * FROM answers WHERE question_id=:questionID");
        $stmtAns->execute(['questionID' => $editAQ]);


	 //if question is true/false type
		if($gaq_type=='tf'){
			$editQops[] = array('showDiv', 'tf', 'mc', 'quesans');
			$editQops[] = array('setValue', 'quizIDtf', $gaq_quiz_id);
			$editQops[] = array('setValue', 'tfDesc', $gaq_question);
		 //if there's programming code attached to the question, add this
			if($gaq_code_type!=""){
				$editQops[] = array('setValue', 'prog-lang-tf', $gaq_code_type);
				$editQops[] = array('changeEditor', $gaq_code_type);
				$editQops[] = array('editorValue', 'tfeditor', $gaq_code_editor);
			}
		 //getting answers of T/F questions
			$ga_index=1;
			while($getanswers_row = $stmtAns->fetch()){
				$ga_answer = $getanswers_row['answer'];
				$ga_correct = $getanswers_row['correct'];

				if($ga_correct==1 && $ga_answer=="True"){
					$editQops[] = array('setChecked', 'tfans1');
				}
				else if($ga_correct==1 && $ga_answer=="False"){
					$editQops[] = array('setChecked', 'tfans2');
				}
				$ga_index++;
			}

		 //changing the submit button and action
			$editQops[] = array('setAction', 'addQuestion', 'editaquest.php');
			$editQops[] = array('setLabel', 'addToQuizTF', 'Save');

		}

	 //if the question is multiple choice!
		else if($gaq_type=='mc'){
			$editQops[] = array('showDiv', 'mc', 'tf', 'quesans');
			$editQops[] = array('setValue', 'quizIDmc', $gaq_quiz_id);
			$editQops[] = array('setValue', 'mcdesc', $gaq_question);
			if($gaq_code_type!=""){
				$editQops[] = array('setValue', 'prog-lang-mc', $gaq_code_type);
				$editQops[] = array('changeEditor', $gaq_code_type);
				$editQops[] = array('editorValue', 'mceditor', $gaq_code_editor);
			}

			$ga_index=1;
			while($getanswers_row = $stmtAns->fetch()){
				$ga_answer = $getanswers_row['answer'];
				$ga_correct = $getanswers_row['correct'];

				if($ga_correct==1){
					$editQops[] = array('setChecked', 'mcans'.$ga_index);
				}

				$editQops[] = array('setValue', 'mcanswer'.$ga_index, $ga_answer);

				$ga_index++;
			}

			$editQops[] = array('setAction', 'addMcQuestion', 'editaquest.php');
			$editQops[] = array('setLabel', 'addToQuizMC', 'Save');

		}
	}

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

		$isAll = ($deleteSQ == 'allthequestions');

		if($isAll) {
			$stmt = $pdo->query("SELECT questions.*, quizes.quiz_name FROM questions LEFT JOIN quizes ON questions.quiz_id = quizes.quiz_id");
        } else {
            $stmt = $pdo->prepare("SELECT questions.*, quizes.quiz_name FROM questions LEFT JOIN quizes ON questions.quiz_id = quizes.quiz_id WHERE questions.quiz_id = :quizID");
            $stmt->execute(['quizID' => $get_quiz_id]);
        }

			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if($isAll){
				foreach($rows as $idx => $r){
					$rows[$idx]['_all'] = true;
				}
			}

			echo render_questions_table($rows, fetch_answers_by_question_ids($pdo, array_column($rows, 'question_id')), 'checkbox');
			exit();
	}
?>



<?php
//if deleteAdmin is clicked, check--
	if(isset($_POST['deleteAdmin']) && $_POST['deleteAdmin'] != ""){
		$deleteA = $_POST['deleteAdmin'];

        $stmt = $pdo->prepare("DELETE FROM admins WHERE username = :username");
        $stmt->execute(['username' => $deleteA]);

	//verifying the delete via the DML row count itself
		if($stmt->rowCount() < 1){
			echo "Sorry, there was a problem deleting the /".htmlspecialchars($deleteA)."/ admin. Please try again later.";
			exit();
		}else{
			audit_log('delete_admin', $deleteA);
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


 //verifying the update via the DML row count itself
		if($stmt->rowCount() < 1){
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
		$clearR = preg_replace('/[^0-9]/', "", $_POST['clearResult']);

	//deleting; the DELETE row count is the verification (zero rows remain either way)
        $stmt = $pdo->prepare("DELETE FROM quiz_takers WHERE quiz_id=:quizID");
        $stmt->execute(['quizID' => $clearR]);

        audit_log('clear_result', 'quiz_id=' . $clearR);

		echo "Result has been cleared!";
		exit();
	}
?>






<?php
//if reset is clicked, check--
	if(isset($_POST['reset']) && $_POST['reset'] != ""){
		$reset = preg_replace('/[^0-9]/', "", $_POST['reset']);

	//resetting the tables (any failure surfaces as a PDOException under ERRMODE_EXCEPTION)
		$pdo->exec("TRUNCATE TABLE questions");
		$pdo->exec("TRUNCATE TABLE answers");

	 ///////Updating value of total questions in quizes
		$pdo->exec("UPDATE quizes SET total_questions=0");

        audit_log('reset_questions', 'questions and answers truncated; quiz counters zeroed');

		echo "Thanks! The all quizes have now been reset back to 0 questions.";
		exit();
	}
?>




<?php
//if deleteQuiz is clicked, check--
	if(isset($_POST['deleteQuiz']) && $_POST['deleteQuiz'] != ""){
		$deleteQ = $_POST['deleteQuiz'];

	//resolving the quiz id
        $stmt = $pdo->prepare("SELECT id as quiz_id FROM quizes WHERE quiz_name = :quizName");
        $stmt->execute(['quizName' => $deleteQ]);
        $row = $stmt->fetch();
        $qz_id = $row['quiz_id'];

	 //transactional cascade: quiz -> questions -> answers
		try{
			$pdo->beginTransaction();

			$stmtDel = $pdo->prepare("DELETE FROM quizes WHERE id = :quizID");
			$stmtDel->execute(['quizID' => $qz_id]);

			$stmtDelQ = $pdo->prepare("DELETE FROM questions WHERE quiz_id = :quizID");
			$stmtDelQ->execute(['quizID' => $qz_id]);

			$stmtDelA = $pdo->prepare("DELETE FROM answers WHERE quiz_id = :quizID");
			$stmtDelA->execute(['quizID' => $qz_id]);

			$pdo->commit();
		}catch(Throwable $e){
			if($pdo->inTransaction()){
				$pdo->rollBack();
			}
			echo "Sorry, there was a problem deleting the /".htmlspecialchars($deleteQ)."/ quiz. Please try again later.";
			exit();
		}

	 //verifying the delete via the DML row count itself
		if((int) $stmtDel->rowCount() < 1)
			echo "Sorry, there was a problem deleting the /".htmlspecialchars($deleteQ)."/ quiz. Please try again later.";
		else{
			audit_log('delete_quiz', $deleteQ);
			echo "Thanks! The quiz /".htmlspecialchars($deleteQ)."/ has now been deleted.";
		}

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
		 //quiz names travel into single/double-quoted JS onclick args inside
		 //a double-quoted HTML attribute: JSON hex-encodes quotes/tags for the
		 //JS layer, then ENT_QUOTES encodes the result for the attribute layer
			$m_quiz_name_js = htmlspecialchars(json_encode($m_quiz_name, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES);
			$m_quiz_name_html = htmlspecialchars($m_quiz_name, ENT_QUOTES);
		 //getting options for the selecting quiz part of create/edit question
			$quizSelect .= ' <option value="'.$m_quizID.'">'.$m_quiz_name_html.'</option>';
		 //getting the quiz menu!
			$quizesMenu .= '<li>'.$m_quiz_name_html.' (Q='.$m_disp_ques.', T='.$m_time_alot.')
								<ul>
									<li>Quiz Settings
										<ul>
											<a href="javascript:default_quiz('.$m_quiz_name_js.')">
												<li>Set Default</li>
											</a>
											<a href="javascript:update_quiz('.$m_quiz_name_js.')">
												<li>Update Metadata</li>
											</a>
											<a href="javascript:delete_quiz('.$m_quiz_name_js.')">
												<li>Delete this Quiz</li>
											</a>
										</ul>
									</li>

									<li>Manage Questions
										<ul>
											<a href="javascript:view_questions('.$m_quiz_name_js.')">
												<li>View all Questions</li>
											</a>
											<a href="javascript:edit_question('.$m_quiz_name_js.')">
												<li>Edit a Question</li>
											</a>
											<a href="javascript:delete_some_questions('.$m_quiz_name_js.')">
												<li>Delete Some Questions</li>
											</a>
										</ul>
									</li>



									<li>Results
										<ul>
											<a href="javascript:top_users('.$m_quiz_name_js.')">
												<li>Result(Top 20)</li>
											</a>
											<a href="javascript:all_users('.$m_quiz_name_js.')">
												<li>Result(All)</li>
											</a>
											<a href="javascript:clear_result(\''.$m_quizID.'\')">
												<li>Clear the Result</li>
											</a>
											<a href="export_results.php?quiz='.(int) $m_quizID.'&amp;scope=top">
												<li>Export Top 20 (CSV)</li>
											</a>
											<a href="export_results.php?quiz='.(int) $m_quizID.'&amp;scope=all">
												<li>Export All (CSV)</li>
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

        $stmt = $pdo->prepare("SELECT * FROM quiz_takers WHERE quiz_id = :quizID ORDER BY marks desc, duration asc");
        $stmt->execute(['quizID' => $get_quiz_id]);

		echo render_results_table($stmt->fetchAll(), 20);
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

        $stmt = $pdo->prepare("SELECT * FROM quiz_takers WHERE quiz_id = :quizID ORDER BY marks desc, duration asc");
        $stmt->execute(['quizID' => $get_quiz_id]);

		echo render_results_table($stmt->fetchAll(), null);
		exit();
	}
?>




<?php
//T5.3 audit viewer: recent 50 rows for the collapsible admin panel

	if(isset($_POST['auditRecent']) && $_POST['auditRecent'] != ""){
		$stmt = $pdo->query("SELECT created_at, actor, action, detail FROM audit_log ORDER BY id DESC LIMIT 50");

		echo ' <tr align="center">
					<th>Time</th>
					<th>Actor</th>
					<th>Action</th>
					<th>Detail</th>
				</tr>
			 ';

		$any = false;
		while($row = $stmt->fetch()){
			$any = true;
			echo '<tr align="center">
					  <td>' . htmlspecialchars((string) $row['created_at'], ENT_QUOTES) . '</td>
					  <td>' . htmlspecialchars((string) $row['actor'], ENT_QUOTES) . '</td>
					  <td>' . htmlspecialchars((string) $row['action'], ENT_QUOTES) . '</td>
					  <td>' . htmlspecialchars((string) $row['detail'], ENT_QUOTES) . '</td>
				  </tr>
				 ';
		}

		if(!$any){
			echo '<tr><td colspan="4">No activity recorded yet.</td></tr>';
		}
		exit();
	}
?>





<?php
//PHP for showing all the questions

	if(isset($_POST['questionsQuiz']) && $_POST['questionsQuiz'] != ""){
		$questionsQ = $_POST['questionsQuiz'];

        $get_quiz_id = '';
		$isAll = ($questionsQ == 'allthequestions');

        if(!$isAll) {
            $stmt = $pdo->prepare("SELECT id as quiz_id FROM quizes WHERE quiz_name = :quizName");
            $stmt->execute(['quizName' => $questionsQ]);
            $row = $stmt->fetch();
            $get_quiz_id = $row['quiz_id'];
        }

		if($isAll) {
			$stmt = $pdo->query("SELECT questions.*, quizes.quiz_name FROM questions JOIN quizes ON questions.quiz_id = quizes.quiz_id");
        } else {
            $stmt = $pdo->prepare("SELECT questions.*, quizes.quiz_name FROM questions JOIN quizes ON questions.quiz_id = quizes.quiz_id WHERE questions.quiz_id = :quizID");
            $stmt->execute(['quizID' => $get_quiz_id]);
        }

			$rows = $stmt->fetchAll();
			if($isAll){
				foreach($rows as $idx => $r){
					$rows[$idx]['_all'] = true;
				}
			}

			echo render_questions_table($rows, fetch_answers_by_question_ids($pdo, array_column($rows, 'question_id')), 'none');
			exit();
	}
?>




































<?php $page_title = 'Admin'; require __DIR__ . '/lib/views/head.php'; ?>
<?php require __DIR__ . '/lib/views/favicon.php'; ?>

	    <!-- SYNTAX HIGHLIGHTING (Prism 1.29.0, pinned) -->
			<link rel="stylesheet" type="text/css" href="assets/vendor/prism-1.29.0/themes/prism.css">
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-core.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-markup.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-markup-templating.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-css.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-clike.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-javascript.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-actionscript.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-applescript.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-bash.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-basic.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-c.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-cpp.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-csharp.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-pascal.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-diff.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-erlang.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-groovy.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-java.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-perl.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-php.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-powershell.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-python.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-ruby.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-sass.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-scala.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-sql.min.js"></script>
			<script type="text/javascript" src="assets/vendor/prism-1.29.0/components/prism-vbnet.min.js"></script>
		<!-- SYNTAX HIGHLIGHTING -->

	<!-- CODE EDITOR (CodeMirror 5.65.16, pinned) -->
		<link rel="stylesheet" href="assets/vendor/codemirror-5.65.16/lib/codemirror.css">
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/lib/codemirror.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/addon/edit/matchbrackets.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/addon/edit/closebrackets.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/addon/edit/closetag.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/addon/selection/active-line.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/addon/mode/loadmode.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/mode/xml/xml.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/mode/javascript/javascript.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/mode/css/css.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/mode/htmlmixed/htmlmixed.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/mode/clike/clike.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/mode/diff/diff.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/mode/erlang/erlang.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/mode/groovy/groovy.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/mode/perl/perl.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/mode/php/php.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/mode/pascal/pascal.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/mode/python/python.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/mode/ruby/ruby.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/mode/sass/sass.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/mode/shell/shell.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/mode/sql/sql.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/mode/vb/vb.js"></script>
		<script type="text/javascript" src="assets/vendor/codemirror-5.65.16/mode/powershell/powershell.js"></script>

		<link rel="stylesheet" type="text/css" href="css/admin.css">






		<link rel="stylesheet" type="text/css" href="css/register.css">
		<link rel="stylesheet" type="text/css" href="css/addNewQuiz.css">



	</head>

	<body style="font-family: Arial;">

		<?php require __DIR__ . '/lib/views/header.php'; ?>


		<div id="admin_menu">
		<p style="color:#06F;" id="msg">
			<?php echo htmlspecialchars($msg, ENT_QUOTES); ?>
		</p>
			<br><br>
			<ul>
				<span id="Hello">Hello, <a href="admin.php"><span id="usr"><?php echo htmlspecialchars($login_session, ENT_QUOTES); ?>!</span></a></span>

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
					<a href="javascript:open_overlay('regResetPass', 'regNewAdmin');">
						<li>Reset an Admin Password</li>
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

		    <div id="audit_panel">
				<a href="javascript:{}" onclick="toggle_audit_panel();" class="myButton">Recent Activity</a>
				<div id="audit_wrap" style="display:none;">
					<div class="table-wrap">
						<table align="center" id="audit_table" style="width:100%;max-width:780px;">
						</table>
					</div>
				</div>
			</div>
		</div>


		<?php $type = 'tf'; require __DIR__ . '/lib/views/question-form.php'; ?>


		<?php $type = 'mc'; require __DIR__ . '/lib/views/question-form.php'; ?>


		<div class="content" id="quesans"  style="margin-bottom: 100px;">
			<form id="deleteedit" name="deleteedit" action="deleteSomeQues.php" method="POST">
				<?php echo csrf_field(); ?>
				<div class="table-wrap">
					<table align="center" id="quesans_table" style="width:100%;max-width:780px;">
					</table>
				</div>
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

			<form action="reset_password.php" class="login" method="POST" name="reset_pass_name" id="regResetPass" style="display:none;">
				<?php echo csrf_field(); ?>
				<p>
			      <label class="reg_label" for="reset_username">Admin Username:</label><br>
			      <input type="text" name="username" id="reset_username" required="required">
			    </p>
			    <p>
			      <label class="reg_label" for="new_password">New Password (min 6 chars):</label><br>
			      <input type="password" name="new_password" id="new_password" required="required">
			    </p>
			    <p class="login-submit">
			      <button type="submit" class="login-button">Reset Password</button>
			    </p>
			</form>

        </div>



        <div id="fade_overlay">
            <a href="javascript:close_overlay();" style="cursor: default;">
                <div id="fade" class="black_overlay">
                </div>
            </a>
        </div>

        <br><BR><BR><BR><br><BR><BR><BR>

		<?php $footer_video_link = false; ?><?php require __DIR__ . '/lib/views/footer.php'; ?>












    <!-- Script for the codeMirror api -->
	<!-- Page config + per-request state for assets/js/admin.js (CSP-safe JSON islands) -->
	<script id="admin-boot" type="application/json"><?php echo json_encode(array('login_session' => $login_session), JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
<?php if (!empty($editQops)): ?>	<script id="edit-state" type="application/json"><?php echo json_encode($editQops, JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
<?php endif; ?>
	<script src="assets/js/code-highlight.js"></script>
	<script src="assets/js/admin.js"></script>


	</body>
</html>
