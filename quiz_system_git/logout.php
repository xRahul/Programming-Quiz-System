<?php

	require_once __DIR__ . '/lib/session.php';

	secure_session_start();

	$user_msg = 'You have successfully Logged out!';

	unset($_SESSION);
	session_destroy();
	setcookie(session_name(), '', time() - 42000, '/');

	header('location: login.php?user_msg='.$user_msg.'');

?>