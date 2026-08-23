<?php

	/*
    Short Programming Quiz Framework
        Copyright (C) 2014  Rahul Jain
    */


	require_once("scripts/connect_db.php");
	require_once __DIR__ . '/lib/headers.php';
	send_security_headers();

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

	 //if user already did, redirect to index.php with error
		if($stmtCheck->rowCount() > 0){
			$user_msg = 'Sorry, but '.$roll_no.', has already attempted the quiz, '.$quzz_name.'!';
			header('location: index.php?user_msg='.urlencode($user_msg));
			exit();
		}else{
	 //else inserting few columns into the table
        $stmtInsert = $pdo->prepare("INSERT INTO quiz_takers (username, percentage, date_time, quiz_id, duration) VALUES (:username, '0', now(), :quizID, '0')");
        $stmtInsert->execute(['username' => $roll_no, 'quizID' => $final_quiz_ID]);
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

	 //looping through the questions and adding them on the page
		foreach($sorted_questions as $m_row){
		 //initializing the options
			$m_answers='';
				
		 //getting row attributes in variables
			$m_id = $m_row['id'];
			$m_thisQuestion = $m_row['question'];
			$m_type = $m_row['type'];
			$m_question_id = $m_row['question_id'];
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
            $stmtAns = $pdo->prepare("SELECT * FROM answers WHERE question_id=:questionID ORDER BY rand()");
            $stmtAns->execute(['questionID' => $m_question_id]);

				$m_answers .=  '<tr>
									<td></td>
									<td>
								';
				 //adding html to individual options here
					while($m_row2 = $stmtAns->fetch()){
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

     <!-- SYNTAX HIGHLIGHTER LINKS & SCRIPTS -->
        <link rel="stylesheet" type="text/css" href="sh/styles/shCore.css">
		<link rel="stylesheet" type="text/css" href="sh/styles/shThemeDefault.css">
		<script type="text/javascript" src="sh/scripts/shCore.js"></script>
	 <!-- INCLUDING ALL SCRIPTS FOR BRUSHES -->
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


		
	</head>

	<body style="font-family: Arial;">

		<?php require __DIR__ . '/lib/views/header.php'; ?>

        <br><strong><?php echo htmlspecialchars($quzz_name, ENT_QUOTES); ?></strong>

        <div id="countdown">
        <script id="quiz-boot" type="application/json"><?php echo json_encode(array('total_time' => (int) $total_time, 'total_ques' => (int) $m_display_ID), JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
        <script src="assets/js/quiz.js"></script>
        </div>
        <span id="progress"></span>


		<div id="main_body" align="center" style="margin-bottom: 100px;">
			<form id="quiz_form" name="quiz_form_name" action="result.php" method="POST">
			<br /><BR /><BR />
				<table width="780px" align="center">
					<?php echo $m_output ?>
				</table>
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