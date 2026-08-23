<?php 
    require_once __DIR__ . '/lib/auth.php';
    require_admin();

    require_once __DIR__ . '/lib/csrf.php';
    csrf_verify();

    require_once('scripts/connect_db.php');
    
    if(isset($_POST['login']) && $_POST['login'] != "" &&
       isset($_POST['password']) && $_POST['password'] != "" ){
        session_start();

        $user = $_POST['login'];
        $pass = $_POST['password'];

        // Security check: ensure the logged-in user is the one changing the password
        if (!isset($_SESSION['login_username']) || $_SESSION['login_username'] !== $user) {
             $user_msg = 'You can only change your own password!';
             header('location: admin.php?msg='.urlencode($user_msg));
             exit();
        }

        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username=:username");
        $stmt->execute(['username' => $user]);

        if($stmt->rowCount() > 0) {
            $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

            $updateStmt = $pdo->prepare("UPDATE admins SET password = :password WHERE username = :username");
            $updateStmt->execute(['password' => $hashed_password, 'username' => $user]);

            $user_msg = 'Password Changed Successfully for \\'.htmlspecialchars($user).'\\';
            header('location: admin.php?msg='.urlencode($user_msg));
            exit();
        }
        else
        {
            $user_msg = 'Wrong Username!';
            header('location: admin.php?msg='.urlencode($user_msg));
            exit();
        }
    }else{
        $user_msg = 'Sorry, but Something went wrong';
        header('location: admin.php?msg='.urlencode($user_msg));
        exit();
    }
?>
