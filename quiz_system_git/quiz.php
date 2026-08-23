<?php

	/*
    Short Programming Quiz Framework
        Copyright (C) 2014  Rahul Jain
    */


	require_once("scripts/connect_db.php");
	require_once __DIR__ . '/lib/headers.php';
	require_once __DIR__ . '/lib/render.php';
	send_security_headers();

	use function App\fetch_answers_by_question_ids;

    $stmt = $pdo->query("SELECT id as quiz_id, display_questions, time_allotted, quiz_name FROM quizes WHERE set_default=1");
    $selecting_quiz_row = $stmt->fetch();

 //checking if all 3 values are there
	if(isset($_POST['rollno']) && $_POST['rollno'] != "")
	{
		
	 //getting values in variables
		$roll_no = $_POST['rollno'];

		$total_questions = preg_replace('/[^0-9]/', "", $selecting_quiz_row['display_questions']);

	 //total time converted to seconds
		$total_time = (preg_replace('/[^0-9]/', "", $selecting_quiz_row['time_allotted']))*60;

		$final_quiz_ID = preg_replace('/[^0-9]/', "", $selecting_quiz_row['quiz_id']);

		$quzz_name = $selecting_quiz_row['quiz_name'];

	 //checking if user has already taken this quiz
        $stmtCheck = $pdo->prepare("SELECT id FROM quiz_takers WHERE username = :username AND quiz_id=:quizID");
        $stmtCheck->execute(['username' => $roll_no, 'quizID' => $final_quiz_ID]);

	 //one friendly message for BOTH duplicate paths: the rowCount loser
	 //below and the unique-key race loser whose SELECT ran before another
	 //request's INSERT landed (uq_takers_user_quiz). Legacy wording, byte-
	 //identical for either path.
		$already_msg = 'Sorry, but '.$roll_no.', has already attempted the quiz, '.$quzz_name.'!';

	 //if user already did, redirect to index.php with error
		if($stmtCheck->rowCount() > 0){
			header('location: index.php?user_msg='.urlencode($already_msg));
			exit();
		}else{
	 //else inserting few columns into the table; a lost race between the
	 //SELECT above and this INSERT surfaces as SQLSTATE 23000 -- map it to
	 //the same friendly already-attempted redirect instead of a 500
			try{
				$stmtInsert = $pdo->prepare("INSERT INTO quiz_takers (username, percentage, date_time, quiz_id, duration) VALUES (:username, '0', now(), :quizID, '0')");
				$stmtInsert->execute(['username' => $roll_no, 'quizID' => $final_quiz_ID]);
			}catch(PDOException $e){
				if($e->getCode() !== '23000'){
					throw $e;
				}
				header('location: index.php?user_msg='.urlencode($already_msg));
				exit();
			}
		}
	}else{
		$user_msg = 'Hey, This is the start Page, So enter your username here first';
		header('location: index.php?user_msg='.urlencode($user_msg));
			exit();
	}







 //getting body i.e. questions, options and submit button for the page

 //initialize the optput variable
	$m_output='';
 
 //Getting the questions from DB here
    // Note: ORDER BY RAND() is not efficient for large tables, but fine for this scale.
    // PDO doesn't have a direct equivalent to LIMIT taking a variable in query string if emulation is off,
    // but default emulation is usually on or we can bind param.
    // However, LIMIT parameter binding in PDO can be tricky with string types.
    // Since $total_questions is strictly regexed to numbers, direct interpolation is safe-ish,
    // but better to use binding or ensure it is int.
    $total_questions = (int)$total_questions;

    // Optimized: Fetch IDs first, shuffle in PHP, then fetch rows.
    // This avoids ORDER BY RAND() which is slow on large datasets.
    $stmtIDs = $pdo->prepare("SELECT id FROM questions WHERE quiz_id=:quizID");
    $stmtIDs->bindValue(':quizID', $final_quiz_ID);
    $stmtIDs->execute();
    $all_ids = $stmtIDs->fetchAll(PDO::FETCH_COLUMN);

    shuffle($all_ids);
    $selected_ids = array_slice($all_ids, 0, $total_questions);

    $sorted_questions = [];
    if (!empty($selected_ids)) {
        // Fetch the questions for the selected IDs
        $inQuery = implode(',', array_map('intval', $selected_ids));
        $stmtQ = $pdo->query("SELECT * FROM questions WHERE id IN ($inQuery)");
        $fetched_rows = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

        // Map them by ID for easy lookup
        $rows_map = [];
        foreach ($fetched_rows as $row) {
            $rows_map[$row['id']] = $row;
        }

        // Reconstruct the array in the shuffled order
        foreach ($selected_ids as $id) {
            if (isset($rows_map[$id])) {
                $sorted_questions[] = $rows_map[$id];
            }
        }
    }

		if (empty($sorted_questions)) {
			$user_msg = 'Hey, weird, but it seems there are no questions in this quiz!';
			header('location: index.php?user_msg='.urlencode($user_msg));
			exit();
		}

	 //setting Question No. to 1 on quiz page(necessary due to rand() above)
		$m_display_ID = 1;

	 //one batched answers fetch for every displayed question
	 //(replaces one "ORDER BY rand()" SELECT per question inside the loop)
		$answers_by_qid = fetch_answers_by_question_ids($pdo, array_map('strval', array_column($sorted_questions, 'id')));

	 //looping through the questions and adding them on the page
		foreach($sorted_questions as $m_row){
		 //initializing the options
			$m_answers='';
				
		 //getting row attributes in variables
			$m_id = $m_row['id'];
			$m_thisQuestion = $m_row['question'];
			$m_type = $m_row['type'];
			$m_question_id = $m_row['id'];
			$m_code = $m_row['code'];
			$m_code_type = $m_row['code_type'];

		 //html for question
			$m_q = '<tr>
						<td width="40px" rowspan="1" align="center">
							<strong>'.$m_display_ID.'.</strong>
						</td>
						<td>
							<pre class="question_style"><strong><div style="width: 730px; word-wrap: break-word;">'.htmlspecialchars($m_thisQuestion, ENT_QUOTES).'</div></strong></pre>
						</td>
					</tr>';
		 //if programming code is inserted, its html for the code
			if($m_code != "" && $m_code_type != ""){
				$m_q .='<tr>
						<td></td>
						<td>
							<pre class="brush: '.htmlspecialchars($m_code_type, ENT_QUOTES).';">'.htmlspecialchars($m_code, ENT_QUOTES).'</pre>
						</td>
					</tr>
					';
			}

		 //gathering options of the question here
		 //random-per-question answer order preserved: shuffle() replaces ORDER BY rand()
			$m_answerRows = $answers_by_qid[(int) $m_question_id] ?? [];
			shuffle($m_answerRows);

				$m_answers .=  '<tr>
									<td></td>
									<td>
								';
				 //adding html to individual options here
					foreach($m_answerRows as $m_row2){
					 //getting row attributes in variables
						$m_answer = $m_row2['answer'];
						$m_answer_ID = $m_row2['id'];

						
						$m_answers .= ' <label style="cursor:pointer;">
									   		<input type="radio" name="rads'.$m_display_ID.'" value="'.$m_answer_ID.'">'.htmlspecialchars($m_answer, ENT_QUOTES).'</label>
										<br /><br />
									  ';
					}

					$m_answers .=  '</td>
								</tr>
								<tr height="20px">
								</tr>
								   ';



			 // the complete div that is sent back to quiz.php
				$m_output .= ''.$m_q.$m_answers;

				$m_display_ID++;

		}

		$m_display_ID--;

	 //adding html for submit button
		$m_output .= '  <tr>
							<td colspan="2" align="center">
								<span id="m_btnSpan">
									<a href="javascript:{}" onclick="quiz_submit()" class="myButton">Submit</a>
								</span>
							</td>
						</tr>';

	 //adding html for hidden values to be sent to result.php
		$m_output .= '<input type="hidden" name="rollno" value="'.htmlspecialchars($roll_no, ENT_QUOTES).'">
					  <input type="hidden" name="total_ques" value="'.$m_display_ID.'">
					  <input type="hidden" name="total_time" value="'.$total_time.'">
					  <input type="hidden" name="quizID" value="'.$final_quiz_ID.'">
					  ';
?>




























<?php $page_title = 'Quiz'; require __DIR__ . '/lib/views/head.php'; ?>
		<link rel="stylesheet" type="text/css" href="css/master.css">
        <script type="text/javascript" src="scripts/overlay.js"></script>
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


		
	</head>

	<body style="font-family: Arial;">

		<?php require __DIR__ . '/lib/views/header.php'; ?>

        <br><strong><?php echo htmlspecialchars($quzz_name, ENT_QUOTES); ?></strong>

        <div id="countdown">
        <script id="quiz-boot" type="application/json"><?php echo json_encode(array('total_time' => (int) $total_time, 'total_ques' => (int) $m_display_ID), JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
        <script src="assets/js/code-highlight.js"></script>
        <script src="assets/js/quiz.js"></script>
        </div>
        <span id="progress"></span>


		<div id="main_body" align="center" style="margin-bottom: 100px;">
			<form id="quiz_form" name="quiz_form_name" action="result.php" method="POST">
			<br /><BR /><BR />
				<div class="table-wrap">
					<table align="center" style="width:100%;max-width:780px;">
						<?php echo $m_output ?>
					</table>
				</div>
			</form>
		</div>


		<div id="video" class="white_content">
            <a name="Planet_Earth">
                <video id="video_player" controls preload="meta" height="480">
                    <source src="videos/video.mp4" type='video/mp4' />
                    <source src="videos/video.webmhd.webm" type='video/webm' />
                    Your browser doesn't seem to support the video tag.
                </video>
                
            </a>
        </div>


        <div id="fade_overlay">
            <a href="javascript:close_overlay();" style="cursor: default;">
                <div id="fade" class="black_overlay">
                </div>
            </a>
        </div>


		<?php require __DIR__ . '/lib/views/footer.php'; ?>
	</body>
</html>