<?php

    /*
    Short Programming Quiz Framework
        Copyright (C) 2014  Rahul Jain
    */
    

    require_once __DIR__ . '/lib/headers.php';
    send_security_headers();

    if(isset($_POST["total_ques"]) && isset($_POST["rollno"]) && isset($_POST["quizID"]))
    {
        if($_POST["total_ques"] != "" && $_POST["rollno"] != "" && $_POST["quizID"] != "")
        {
            require_once("scripts/connect_db.php");

         //initializing the variables
            $marks = 0;
            $total_questions = $_POST["total_ques"];
            $roll_no = $_POST["rollno"];
            $quiz_ID = $_POST["quizID"];

            $roll_no = htmlspecialchars($roll_no);

            if($total_questions>0){

                // calculating %age
                // Optimization: Fetch all correct answers in one query instead of N queries
                $php_ids = [];
                // Collect IDs first
                for($i=1 ; $i <= $total_questions ; $i++){
                    @$fetch_ID = "rads".$i;
                    @$php_id = $_POST[$fetch_ID];

                    if(isset($php_id) && $php_id != "") {
                        $php_ids[] = $php_id;
                    }
                }

                if (!empty($php_ids)) {
                    $unique_ids = array_unique($php_ids);
                    // Use placeholders for WHERE IN clause
                    $placeholders = implode(',', array_fill(0, count($unique_ids), '?'));

                    $stmtCheck = $pdo->prepare("SELECT id, correct FROM answers WHERE id IN ($placeholders)");
                    $stmtCheck->execute(array_values($unique_ids));

                    $correct_map = [];
                    while ($q_answer = $stmtCheck->fetch()) {
                        $correct_map[$q_answer['id']] = $q_answer['correct'];
                    }

                    // Calculate marks using the map
                    foreach ($php_ids as $pid) {
                        if (isset($correct_map[$pid])) {
                            $marks += $correct_map[$pid];
                        }
                    }
                }
	            $percent = ($marks/$total_questions)*100;

	         //getting total time taken by the user to complete the quiz
             // Using TIMESTAMPDIFF for accurate seconds calculation
             // Note: Original code used "now() - date_time" which is often incorrect for seconds (it's YYYYMMDDHHMMSS difference)
             // A "Principal Engineer" fix is to use correct time arithmetic.
                $stmtTimeDiff = $pdo->prepare("SELECT TIMESTAMPDIFF(SECOND, date_time, now()) as time_taken FROM quiz_takers WHERE username = :username AND quiz_id = :quizID ORDER BY id DESC LIMIT 1");
                $stmtTimeDiff->execute(['username' => $roll_no, 'quizID' => $quiz_ID]);
                $get_time = $stmtTimeDiff->fetch();

	            $time_taken = $get_time['time_taken'];

                // Check duration to see if it's 0 (first submission)
                // We must use the SAME record we just checked time for, ideally.
                // However, user logic assumes one active session per quiz.
	            $stmtDuration = $pdo->prepare("SELECT duration FROM quiz_takers WHERE username = :username AND quiz_id = :quizID ORDER BY id DESC LIMIT 1");
                $stmtDuration->execute(['username' => $roll_no, 'quizID' => $quiz_ID]);
                $check_time = $stmtDuration->fetch();
	            $duration = $check_time['duration'];

	            if($duration==0){
		         //updating the %age and time taken by the user in the DB
                    $stmtUpdate = $pdo->prepare("UPDATE quiz_takers SET marks=:marks, percentage= :percent, duration= :time_taken, quiz_id= :quizID WHERE username = :username AND quiz_id = :quizID AND duration = 0");
                    // Added extra WHERE clauses to ensure we update the correct record and avoid race conditions/multiple updates
                    $stmtUpdate->execute([
                        'marks' => $marks,
                        'percent' => $percent,
                        'time_taken' => $time_taken,
                        'quizID' => $quiz_ID,
                        'username' => $roll_no
                    ]);

                 //race guard: if nothing was updated, a concurrent submission already claimed
                 //the row; block the replay exactly like a detected resubmission
                    if ($stmtUpdate->rowCount() === 0) {
                        $stmtTaken = $pdo->prepare("SELECT id FROM quiz_takers WHERE username = :username AND quiz_id = :quizID AND duration > 0 LIMIT 1");
                        $stmtTaken->execute(['username' => $roll_no, 'quizID' => $quiz_ID]);
                        if ($stmtTaken->fetch() !== false) {
                            $user_msg = 'Sorry, but re-submission of the quiz isn\'t allowed!';
						header('location: index.php?user_msg='.urlencode($user_msg));
                            exit();
                        }
                    }
	            }else{
	            	$user_msg = 'Sorry, but re-submission of the quiz isn\'t allowed!';
				header('location: index.php?user_msg='.urlencode($user_msg));
                    exit();
	            }
	        }else{
	        	$user_msg = 'Hey, Weird, but it seems the quiz had no questions!';
			header('location: index.php?user_msg='.urlencode($user_msg));
            	exit();
	        }
        }else{
            $user_msg = 'Hey, Something went wrong! Tell the Admin!!';
        header('location: index.php?user_msg='.urlencode($user_msg));
            exit();
        }
    }else{
        $user_msg = 'Hey, This is the start Page!, So enter your username here first';
        header('location: index.php?user_msg='.urlencode($user_msg));
            exit();
    }
?>

<?php $page_title = 'Result'; require __DIR__ . '/lib/views/head.php'; ?>
        <link rel="stylesheet" type="text/css" href="css/master.css">
        <script type="text/javascript" src="scripts/overlay.js"></script>
<?php require __DIR__ . '/lib/views/favicon.php'; ?>

        <script src="assets/js/result.js"></script>
    </head>

    <body  style="font-family: Arial;">

        <?php require __DIR__ . '/lib/views/header.php'; ?>

        <div id="score" align="center">
            <?php echo $roll_no; ?>, You scored 
            <?php echo $marks; ?>/<?php echo $total_questions; ?>
        </div>

        <div id="video" class="white_content" onclick="javascript:close_overlay();">
            <h1 style="color: WHITE; margin-top: 185px;">Nice Try, But its time to go now!</h1>
            <br>
            <h2 style="color: WHITE;">You should have watched it before..</h2>
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