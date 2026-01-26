<?php

    /*
    Short Programming Quiz Framework
        Copyright (C) 2014  Rahul Jain
    */
    

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

<!DOCTYPE html>
<html>
    <head>
        <title>Result</title>

        <meta charset="utf-8">

        <link rel="stylesheet" type="text/css" href="css/master.css">
        <script type="text/javascript" src="scripts/overlay.js"></script>

        <!-- ****** favicons ****** -->
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
        <!-- ****** favicons ****** -->

        <script language="javascript">
            document.addEventListener("contextmenu", function(e){
                e.preventDefault();
            }, false);
        </script>
    </head>

    <body  style="font-family: Arial;">

        <div id="head" align="center">
            <img src="img/header.jpg" alt="Chandigarh Engineering College" />
        </div>

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
                        <td align="center" id="video_link">
                            Getting Bored? Watch a
                            <a href="javascript:open_overlay();" style="color: #c4dcf5">
                                Video</a>
                            to pass time!
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
    </body>
</html>