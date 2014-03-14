<?php

	include('scripts/connect_db.php');

    if(isset($_POST['login']) && $_POST['login'] != "" &&
       isset($_POST['password']) && $_POST['password'] != ""){

    
        $user=mysql_real_escape_string($_POST['login']);
        $pass=mysql_real_escape_string($_POST['password']);

        $fetch=mysql_query("SELECT id FROM admins 
                            WHERE username='$user'")or die(mysql_error());
        $count=mysql_num_rows($fetch);
        if($count!="")
        {
        	$user_msg = 'Sorry, but \ '.$user.' \ is already taken!';
            header('location: admin.php?msg='.$user_msg.'');
        }
        else
        {
            mysql_query("INSERT INTO admins (username, password) 
            	VALUES ('$user','$pass')")or die(mysql_error());

        	$user_msg = 'Admin account, \ '.$user.' \ has been created!';
            header('location: admin.php?msg='.$user_msg.'');
        }
    }else{
        $user_msg = 'Sorry, but Something went wrong';
        header('location: admin.php?msg='.$user_msg.'');
    }

?>