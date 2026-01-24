<?php
	
    require_once('scripts/connect_db.php');

	session_start();

    if (!isset($_SESSION['login_username'])) {
        $user_msg = 'Please Login First!';
        header('location: login.php?user_msg='.urlencode($user_msg));
        exit();
    }

	$check = $_SESSION['login_username'];

    $stmt = $pdo->prepare("SELECT username FROM admins WHERE username=:username");
    $stmt->execute(['username' => $check]);
    $row = $stmt->fetch();

	$login_session = $row['username'];

	if(!$login_session) {
		$user_msg = 'Please Login First!';
		header('location: login.php?user_msg='.urlencode($user_msg));
        exit();
	}

?>