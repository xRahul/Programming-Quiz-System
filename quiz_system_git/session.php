<?php
	
    require_once('scripts/connect_db.php');
    require_once __DIR__ . '/lib/session.php';

	secure_session_start();

    if (!isset($_SESSION['login_username'])) {
        $user_msg = 'Please Login First!';
        header('location: login.php?user_msg='.urlencode($user_msg));
        exit();
    }

	$check = $_SESSION['login_username'];

    $stmt = $pdo->prepare("SELECT username FROM admins WHERE username=:username");
    $stmt->execute(['username' => $check]);
    $row = $stmt->fetch();

	//read only after the falsy check shape: a vanished row must not raise
	//a warning when we are about to bounce the session anyway
	$login_session = is_array($row) ? (string) $row['username'] : '';

	if(!$login_session) {
		$user_msg = 'Please Login First!';
		header('location: login.php?user_msg='.urlencode($user_msg));
        exit();
	}

?>