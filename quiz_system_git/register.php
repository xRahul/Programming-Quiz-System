<?php

	require_once __DIR__ . '/lib/auth.php';
	require_admin();

	require_once('scripts/connect_db.php');

    if(isset($_POST['login']) && $_POST['login'] != "" &&
       isset($_POST['password']) && $_POST['password'] != ""){

        $user = $_POST['login'];
        $pass = $_POST['password'];

        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username=:username");
        $stmt->execute(['username' => $user]);

        if($stmt->rowCount() > 0)
        {
		$user_msg = 'Sorry, but \ '.htmlspecialchars($user).' \ is already taken!';
            header('location: admin.php?msg='.urlencode($user_msg));
            exit();
        }
        else
        {
            $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (:username, :password)");
            $stmt->execute(['username' => $user, 'password' => $hashed_password]);

		$user_msg = 'Admin account, \ '.htmlspecialchars($user).' \ has been created!';
            header('location: admin.php?msg='.urlencode($user_msg));
            exit();
        }
    }else{
        $user_msg = 'Sorry, but Something went wrong';
        header('location: admin.php?msg='.urlencode($user_msg));
        exit();
    }

?>