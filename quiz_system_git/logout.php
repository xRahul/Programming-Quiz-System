<?php

	session_start();

	if(session_destroy()) {
		$user_msg = 'You have successfully Logged out!';
		header('location: login.php?user_msg='.$user_msg.'');
	}

?>