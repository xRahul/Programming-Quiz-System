<?php 
    // CSRF-exempt (task 1.3): pre-auth bootstrap token flow deferred; rate-limiting out of scope.
    require_once('scripts/connect_db.php');
    require_once __DIR__ . '/lib/session.php';

    if(isset($_POST['login']) && $_POST['login'] != "" &&
       isset($_POST['password']) && $_POST['password'] != "" ){

        secure_session_start();

        $user = $_POST['login'];
        $pass = $_POST['password'];

        $stmt = $pdo->prepare("SELECT id, password FROM admins WHERE username = :username");
        $stmt->execute(['username' => $user]);
        $row = $stmt->fetch();

        if ($row && password_verify($pass, $row['password'])) {
            session_regenerate_id(true);
            unset($_SESSION['csrf_token']);
            $_SESSION['login_username'] = $user;

            $updateStmt = $pdo->prepare("UPDATE admins SET last_login=now() WHERE username = :username");
            $updateStmt->execute(['username' => $user]);

            header("Location:admin.php");
            exit();
        } else {
            $user_msg = 'Wrong Username or Password!';
            header('location: login.php?user_msg='.urlencode($user_msg));
            exit();
        }
    } else{
        $user_msg = 'Sorry, but Something went wrong';
        header('location: login.php?user_msg='.urlencode($user_msg));
        exit();
    }
?>
