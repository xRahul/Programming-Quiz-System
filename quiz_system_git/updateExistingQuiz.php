<?php

	include('scripts/connect_db.php');

        if(isset($_POST['quizName']) && $_POST['quizName'] != "" &&
           isset($_POST['quizTime']) && $_POST['quizTime'] != "" &&
           isset($_POST['numQues']) && $_POST['numQues'] != ""){

            $qName=mysql_real_escape_string($_POST['quizName']);
            $qTime=mysql_real_escape_string($_POST['quizTime']);
            $nQues=mysql_real_escape_string($_POST['numQues']);

            $qTime = preg_replace('/[^0-9]/', "", $qTime);
            $nQues = preg_replace('/[^0-9]/', "", $nQues);

            $fetch=mysql_query("SELECT total_questions FROM quizes 
                                WHERE quiz_name='$qName'")or die(mysql_error());
            $count=mysql_num_rows($fetch);
            if($count=="")
            {
            	$user_msg = 'Sorry, but \ '.$qName.' \ doesn\'t exist!';
                header('location: admin.php?msg='.$user_msg.'');
            }else{

                $fetch_row = mysql_fetch_array($fetch);
                $t_ques = $fetch_row['total_questions'];

                mysql_query("UPDATE quizes 
                             SET display_questions='$nQues', time_allotted= '$qTime'
                             WHERE quiz_name = '$qName' ")or die(mysql_error());

            	$user_msg = 'Quiz, \ '.$user.' \ has been updated!';
                if($t_ques < $nQues)
                    $user_msg .= ' But, display Questions are more than the total no. of questions in the quiz('.$nQues.'/'.$t_ques.').';

                header('location: admin.php?msg='.$user_msg.'');
            }
        }else{
            $user_msg = 'Sorry, but Something went wrong';
            header('location: admin.php?msg='.$user_msg.'');
        }
?>