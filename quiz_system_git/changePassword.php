<?php 

    include('scripts/connect_db.php');
    
    if(isset($_POST['login']) && $_POST['login'] != "" &&
       isset($_POST['password']) && $_POST['password'] != "" ){
        session_start();
        {
            $user=mysql_real_escape_string($_POST['login']);
            $pass=mysql_real_escape_string($_POST['password']);
            $fetch=mysql_query("SELECT id FROM admins 
                                WHERE username='$user'")or die(mysql_error());
            $count=mysql_num_rows($fetch);
            if($count!="") {
                mysql_query("UPDATE admins 
                             SET password = '$pass'
                             WHERE username = '$user' ")or die(mysql_error());

                $user_msg = 'Password Changed Successfully for \\'.$user.'\\';
                header('location: admin.php?msg='.$user_msg.'');
            }
            else
            {
                $user_msg = 'Wrong Username or Password!';
                header('location: admin.php?msg='.$user_msg.'');
            }
        }
    }else{
        $user_msg = 'Sorry, but Something went wrong';
        header('location: admin.php?msg='.$user_msg.'');
    }
?>

