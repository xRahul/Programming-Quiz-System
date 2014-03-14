<?php

	include('scripts/connect_db.php');

        if(isset($_POST['quizName']) && $_POST['quizName'] != ""
        && isset($_POST['quizTime']) && $_POST['quizTime'] != ""
        && isset($_POST['numQues']) && $_POST['numQues'] != ""){

            $qName=mysql_real_escape_string($_POST['quizName']);
            $qTime=mysql_real_escape_string($_POST['quizTime']);
            $nQues=mysql_real_escape_string($_POST['numQues']);

            $qTime = preg_replace('/[^0-9]/', "", $qTime);
            $nQues = preg_replace('/[^0-9]/', "", $nQues);

            $fetch=mysql_query("SELECT id FROM quizes 
                                WHERE quiz_name='$qName'")or die(mysql_error());
            $count=mysql_num_rows($fetch);
            if($count!="")
            {
            	$user_msg = 'Sorry, but \ '.$qName.' \ already exists!';
                header('location: admin.php?msg='.$user_msg.'');
            }else{
                mysql_query("INSERT INTO quizes (quiz_name, display_questions, time_allotted) 
                	VALUES ('$qName','$nQues','$qTime')")or die(mysql_error());
                
                $lastId = mysql_insert_id();
                mysql_query("UPDATE quizes SET quiz_id='$lastId' 
                                WHERE id='$lastId' LIMIT 1")or die(mysql_error());

            	$user_msg = 'Quiz, \ '.$qName.' \ has been created!';
                header('location: admin.php?msg='.$user_msg.'');
            }
        }else{
            $user_msg = 'Sorry, but Something went wrong';
            header('location: admin.php?msg='.$user_msg.'');
        }
?>