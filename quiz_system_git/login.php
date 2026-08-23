<?php

	/*
    Short Programming Quiz Framework
        Copyright (C) 2014  Rahul Jain

        This program is free software: you can redistribute it and/or modify
        it under the terms of the GNU General Public License as published by
        the Free Software Foundation, either version 3 of the License, or
        (at your option) any later version.

        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.

        You should have received a copy of the GNU General Public License
        along with this program.  If not, see <http://www.gnu.org/licenses/>.
    	
    	Short Programming Quiz Framework -- Copyright (C) 2014  Rahul Jain
        This program comes with ABSOLUTELY NO WARRANTY.
        This is free software, and you are welcome to redistribute it
        under certain conditions found in the GNU GPL license
    */

    require_once __DIR__ . '/lib/session.php';
    secure_session_start();

    require_once __DIR__ . '/lib/headers.php';
    send_security_headers();

    $wrong = "";
    //stored raw; escaped once at the echo site
    if(isset($_POST['user_msg']) && $_POST['user_msg']!=""){
        $wrong = $_POST['user_msg'];
    }

    if(isset($_GET['user_msg']) && $_GET['user_msg']!=""){
        $wrong = $_GET['user_msg'];
    }

?>

<?php $page_title = 'Admin-Login'; require __DIR__ . '/lib/views/head.php'; ?>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<?php require __DIR__ . '/lib/views/favicon.php'; ?>

          <link rel="stylesheet" href="css/login.css">

        <script src="assets/js/login.js"></script>

		<style type="text/css">

		body{
			position: absolute;
			top: 50%;
			left: 50%;
			margin-left: -250px;
			margin-top: -130px;
		}

		</style>

	</head>
	<body>
		<form action="login_check.php" class="login" method="POST">
          		<p>
			      <label for="login">Username:</label>
			      <input type="text" name="login" id="login" autofocus>
			    </p>

			    <p>
			      <label for="password">Password:</label>
			      <input type="password" name="password" id="password">
			    </p>

			    <p class="login-submit">
			      <button type="submit" class="login-button">Login</button>
			    </p>
			    <p class="message">
			    	<?php echo htmlspecialchars($wrong, ENT_QUOTES); ?>
			    </p>
		</form>
	</body>
</html>