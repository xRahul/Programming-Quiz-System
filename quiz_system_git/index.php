<?php
    /*
    Short Programming Quiz Framework
        Copyright (C) 2014,  Rahul Jain
    */

    require_once("scripts/connect_db.php");
    require_once __DIR__ . '/lib/headers.php';
    send_security_headers();


    $stmt = $pdo->query("SELECT id as quiz_id, display_questions, time_allotted, quiz_name FROM quizes WHERE set_default=1");
    $index_selecting_quiz_row = $stmt->fetch();
    $index_selecting_quiz_num = $stmt->rowCount(); // rowCount might not work for SELECT in all PDO drivers but usually does in MySQL.
    // Safer: check if $index_selecting_quiz_row is false.

    $user_taken = "";
    //stored raw; escaped once at the echo site
    if(isset($_POST['user_msg']) && $_POST['user_msg']!=""){
        $user_taken = $_POST['user_msg'];
    }
    if(isset($_GET['user_msg']) && $_GET['user_msg']!=""){
        $user_taken = $_GET['user_msg'];
    }

    if ($index_selecting_quiz_row) {
        $total_questions = preg_replace('/[^0-9]/', "", $index_selecting_quiz_row['display_questions']);
        $total_time = preg_replace('/[^0-9]/', "", $index_selecting_quiz_row['time_allotted']);
        $quizName = $index_selecting_quiz_row['quiz_name'];

        $first_item = 'You\'ve got '.$total_time.' mins for attempting '.$total_questions.' questions.';
    } else {
        $quizName = "";
        $first_item = '<strong>Sorry, but it seems there are no quizzes Available right now!</strong>';
    }
?>

<?php $page_title = 'Instructions'; require __DIR__ . '/lib/views/head.php'; ?>
<?php require __DIR__ . '/lib/views/favicon.php'; ?>

        <link rel="stylesheet" type="text/css" href="css/master.css">
        <script type="text/javascript" src="scripts/overlay.js"></script>

        <script src="assets/js/index.js"></script>


    </head>

    <body style="font-family: Arial;">
        <?php require __DIR__ . '/lib/views/header.php'; ?>

        <div id="main_body" align="center">
            <h2>So, you want to try your luck at the <big>QUIZ</big></h2>
            <strong><?php echo htmlspecialchars($quizName, ENT_QUOTES); ?> </strong>
            <h3 align="left">Here are the rules then:</h3>
            <div align="left">
                <ul>
                    <li><?php echo $first_item; ?></li>
                    <li>If time runs out, your quiz will automatically be submitted</li>
                    <li>You'll only be getting confirmation pop-up once if you try to leave during the quiz</li>
                    <li>You can only attempt the quiz once</li>
                </ul>
            </div>

            <h3>GOOD LUCK!</h3>

            <form id="myForm" name="onlyForm" action="quiz.php" method="POST">
                <table align="center">
                    <tr>
                        <td align="center">
                            <input type="text" name="rollno" placeholder="Enter Your Roll No." autofocus/>
                        </td>
                    </tr>
                    <tr>
                        <td align="center">
                            <h3>Click below when you are ready to start the quiz</h3>
                        </td>
                    </tr>
                    <tr>
                        <td align="center">
                            <a href="javascript:submit();" class="myButton">Click Here to Begin</a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div id = "enter_rollno" align="center"><?php echo htmlspecialchars($user_taken, ENT_QUOTES); ?></div>
                        </td>
                    </tr>
                </table>
            </form>
        </div><br><br><br><br><br><br>

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
