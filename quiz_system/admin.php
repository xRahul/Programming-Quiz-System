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


/*
 //set username and password for admin here!
    $username = 'admin';
    $passwrd = 'cool';

    if(!isset($_POST['type']) || $_POST['type'] == ""){
*/
/*	    if( !isset($_POST['login']) || !isset($_POST['password']) ){
	    	$user_msg = 'Please Login First!';
			header('location: login.php?user_msg='.$user_msg.'');
			exit();
	    }

	    if($_POST['login'] != $username || $_POST['password'] != $passwrd){
	    	$user_msg = 'Wrong Username or Password!';
			header('location: login.php?user_msg='.$user_msg.'');
			exit();
	    }
	}
*/


//checking if the required data has been filled
	if(isset($_POST['desc'])){
		if(!isset($_POST['iscorrect']) || $_POST['iscorrect'] == ""){
			echo "Sorry, important data to submit your question is missing. Please press back in your browser and try again and make sure you select a correct answer for the question.";
			exit();
		}

		if(!isset($_POST['type']) || $_POST['type'] == ""){
			echo "Sorry, there was an error parsing the form. Please press back in your browser and try again";
			exit();
		}

	//connecting to the database
		require_once("scripts/connect_db.php");

	//initializing the variables
		$question = $_POST['desc'];
		$program = $_POST['code_desc'];
		$programType = $_POST['prog-lang'];
		$answer1 = $_POST['answer1'];
		$answer2 = $_POST['answer2'];
		$answer3 = $_POST['answer3'];
		$answer4 = $_POST['answer4'];
		$type = $_POST['type'];

	//replacing everything except a-z with nothing as its values are - mc/tf
		$type = preg_replace('/[^a-z]/', "", $type);

	//replacing everything except 0-9 & a-z with nothhing as value is - answer1/2/3/4
		$isCorrect = preg_replace('/[^0-9a-z]/', "", $_POST['iscorrect']);

	//getting and converting strings as they are
		$question = htmlspecialchars($question);
		$question = mysql_real_escape_string($question);

		$program = htmlspecialchars($program);
		$program = mysql_real_escape_string($program);

		$answer1 = htmlspecialchars($answer1);
		$answer1 = mysql_real_escape_string($answer1);

		$answer2 = htmlspecialchars($answer2);
		$answer2 = mysql_real_escape_string($answer2);

		$answer3 = htmlspecialchars($answer3);
		$answer3 = mysql_real_escape_string($answer3);

		$answer4 = htmlspecialchars($answer4);
		$answer4 = mysql_real_escape_string($answer4);



	//if its a true/false question, do this-
		if($type == 'tf'){
		//if any field is null or empty, say sorry
			if((!$question) || (!$answer1) || (!$answer2) || (!$isCorrect)){
				echo "Sorry, All fields must be filled in to add a new question to the quiz. Please press back in your browser and try again.";
				exit();
			}
		}

	//if its a multiple choice question, do this-
		if($type == 'mc'){
		//if any field is null or empty, say sorry
			if((!$question) || (!$answer1) || (!$answer2) || (!$answer3) || (!$answer4) || (!$isCorrect)){
				echo "Sorry, All fields must be filled in to add a new question to the quiz. Please press back in your browser and try again.";
				exit();
			}
		}
		
	//inserting the question and type into table question
		$sql = mysql_query("INSERT INTO questions (question, code, code_type, type) VALUES ('$question','$program', '$programType', '$type')")or die(mysql_error());
		//lastId is there, so we can insert the id, question_id in our table
			$lastId = mysql_insert_id();
			mysql_query("UPDATE questions SET question_id='$lastId' WHERE id='$lastId' LIMIT 1")or die(mysql_error());

////// Update answers based on which is correct //////////////

	//if inserting a true/false question, insert answers by this-
		if($type == 'tf'){
		//if answer1 is marked correct, do this--
			if($isCorrect == "answer1"){
				$sql2 = mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer1', '1')")or die(mysql_error());
				mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer2', '0')")or die(mysql_error());
				$msg = 'Thanks, question no.'.$lastId.' has been added';
		  		header('location: admin.php?msg='.$msg.'');
				exit();
			}
		//if answer2 is marked correct, do this--
			if($isCorrect == "answer2"){
				$sql2 = mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer2', '1')")or die(mysql_error());
				mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer1', '0')")or die(mysql_error());
				$msg = 'Thanks, question no.'.$lastId.' has been added';
				header('location: admin.php?msg='.$msg.'');
				exit();
			}	
		}

	//if inserting a multiple choice question, insert answers by this-
		if($type == 'mc'){
		//if answer1 is marked correct, do this--
			if($isCorrect == "answer1"){
				$sql2 = mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer1', '1')")or die(mysql_error());
				mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer2', '0')")or die(mysql_error());
				mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer3', '0')")or die(mysql_error());
				mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer4', '0')")or die(mysql_error());
				$msg = 'Thanks, question no.'.$lastId.' has been added';
			  	header('location: admin.php?msg='.$msg.'');
				exit();
			}
		//if answer1 is marked correct, do this--
			if($isCorrect == "answer2"){
				$sql2 = mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer2', '1')")or die(mysql_error());
				mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer1', '0')")or die(mysql_error());
				mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer3', '0')")or die(mysql_error());
				mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer4', '0')")or die(mysql_error());
				$msg = 'Thanks, question no.'.$lastId.' has been added';
		  		header('location: admin.php?msg='.$msg.'');
				exit();
			}
		//if answer1 is marked correct, do this--
			if($isCorrect == "answer3"){
				$sql2 = mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer3', '1')")or die(mysql_error());
				mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer1', '0')")or die(mysql_error());
				mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer2', '0')")or die(mysql_error());
				mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer4', '0')")or die(mysql_error());
				$msg = 'Thanks, question no.'.$lastId.' has been added';
		  		header('location: admin.php?msg='.$msg.'');
				exit();
			}
		//if answer1 is marked correct, do this--
			if($isCorrect == "answer4"){
				$sql2 = mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer4', '1')")or die(mysql_error());
				mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer1', '0')")or die(mysql_error());
				mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer2', '0')")or die(mysql_error());
				mysql_query("INSERT INTO answers (question_id, answer, correct) VALUES ('$lastId', '$answer3', '0')")or die(mysql_error());
				$msg = 'Thanks, question no.'.$lastId.' has been added';
			  	header('location: admin.php?msg='.$msg.'');
				exit();
			}
		}
	}
?>


<?php 
//showing the message back to the user after a transaction is completed
	$msg = "";
	if(isset($_GET['msg'])){
		$msg = $_GET['msg'];
		$msg = strip_tags($msg);
		$msg = mysql_real_escape_string($msg);
	}
?>


<?php 
//if reset is clicked, check--
	if(isset($_POST['reset']) && $_POST['reset'] != ""){
		$reset = preg_replace('/^[a-z]/', "", $_POST['reset']);
		require_once("scripts/connect_db.php");

	//resetting the tables
		mysql_query("TRUNCATE TABLE questions")or die(mysql_error());
		mysql_query("TRUNCATE TABLE answers")or die(mysql_error());

//checking if truncate is successful
	//getting rows from tables
		$sql1 = mysql_query("SELECT id FROM questions LIMIT 1")or die(mysql_error());
		$sql2 = mysql_query("SELECT id FROM answers LIMIT 1")or die(mysql_error());
	//getting number of rows that were returned
		$numQuestions = mysql_num_rows($sql1);
		$numAnswers = mysql_num_rows($sql2);
	//checking if the number of rows==0
		if($numQuestions > 0 || $numAnswers > 0){
			echo "Sorry, there was a problem reseting the quiz. Please try again later.";
			exit();
		}else{
			echo "Thanks! The quiz has now been reset back to 0 questions.";
			exit();
		}
	}
?>





<?php
//PHP for showing all the questions
	require_once("scripts/connect_db.php");

	$m_output='';

	$multipleSQL = mysql_query("SELECT * FROM questions") or die(mysql_error());

			$m_display_ID = 1;

			while($m_row = mysql_fetch_array($multipleSQL)){
				$m_answers='';
			 //id var = id column and so on
				$m_id = $m_row['id'];
				$m_thisQuestion = $m_row['question'];
				$m_type = $m_row['type'];
				$m_question_id = $m_row['question_id'];
				$m_code = $m_row['code'];
				$m_code_type = $m_row['code_type'];

			 //putting the question in h2 tag
				$m_q = '<tr>
							<td width="40px" rowspan="1" align="center">
								<strong>'.$m_display_ID.'.</strong>
							</td>
							<td>
								<strong><div style="width: 730px; word-wrap: break-word;">'.$m_thisQuestion.'</div></strong>
							</td>
						</tr>';

				if($m_code != "" && $m_code_type != ""){
					$m_q .='<tr>
							<td></td>
							<td>
								<pre class="brush: '.$m_code_type.';">'.$m_code.'</pre>
							</td>
						</tr>
						';
				}

			 //gathering answers of question here
				$m_sql2 = mysql_query("SELECT * FROM answers WHERE question_id='$m_question_id'") or die(mysql_error());
				//running loop on all the answers

				$m_answers .=  '<tr>
									<td></td>
									<td>
										<ol type="a">
								';

					while($m_row2 = mysql_fetch_array($m_sql2)){
					//putting column values in variables
						$m_answer = $m_row2['answer'];
						$m_correct = $m_row2['correct'];

						if($m_correct == 1)
							$m_answers .= '<u><i>';
						$m_answers .= '<div style="width: 730px; word-wrap: break-word;"><li>'.$m_answer.'</li></div>';
						if($m_correct == 1)
							$m_answers .= '</i></u>';
					}

				$m_answers .=  '		</ol>
									</td>
								</tr>
								<tr height="20px"></tr>
								';

			 // the complete div that is sent back to quiz.php
				$m_output .= ''.$m_q.$m_answers;

				$m_display_ID++;
			}

?>




































<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		
		<title>Admin</title>

		<link rel="stylesheet" type="text/css" href="css/admin.css">

		<!-- ****** faviconit.com favicons ****** -->
            <!-- Basic favicons -->
                <link rel="shortcut icon" sizes="16x16 32x32 48x48 64x64" href="img/faviconit/favicon.ico">
                <link rel="shortcut icon" type="image/x-icon" href="img/faviconit/favicon.ico">

            <!--[if IE]><link rel="shortcut icon" href="/favicon.ico"><![endif]-->

            <!-- For Opera Speed Dial -->
                <link rel="icon" type="image/png" sizes="195x195" href="img/faviconit/favicon-195.png">
            <!-- For iPad with high-resolution Retina Display running iOS ≥ 7 -->
                <link rel="apple-touch-icon" sizes="152x152" href="img/faviconit/favicon-152.png">
            <!-- For iPad with high-resolution Retina Display running iOS ≤ 6 -->
                <link rel="apple-touch-icon" sizes="144x144" href="img/faviconit/favicon-144.png">
            <!-- For iPhone with high-resolution Retina Display running iOS ≥ 7 -->
                <link rel="apple-touch-icon" sizes="120x120" href="img/faviconit/favicon-120.png">
            <!-- For iPhone with high-resolution Retina Display running iOS ≤ 6 -->
                <link rel="apple-touch-icon" sizes="114x114" href="img/faviconit/favicon-114.png">
            <!-- For Google TV devices -->
                <link rel="icon" type="image/png" sizes="96x96" href="img/faviconit/favicon-96.png">
            <!-- For iPad Mini -->
                <link rel="apple-touch-icon" sizes="76x76" href="img/faviconit/favicon-76.png">
            <!-- For first- and second-generation iPad -->
                <link rel="apple-touch-icon" sizes="72x72" href="img/faviconit/favicon-72.png">
            <!-- For non-Retina iPhone, iPod Touch and Android 2.1+ devices -->
                <link rel="apple-touch-icon" href="img/faviconit/favicon-57.png">
            <!-- Windows 8 Tiles -->
                <meta name="msapplication-TileColor" content="#FFFFFF">
                <meta name="msapplication-TileImage" content="img/faviconit/favicon-144.png">
        <!-- ****** faviconit.com favicons ****** -->

     <!-- SYNTAX HIGHLIGHTER -->
		<link rel="stylesheet" type="text/css" href="sh/styles/shCore.css">
		<link rel="stylesheet" type="text/css" href="sh/styles/shThemeDefault.css">
		<script type="text/javascript" src="sh/scripts/shCore.js"></script>
	   <!-- INCLUDING ALL BRUSHES SCRIPTS -->
		<script type="text/javascript" src="sh/scripts/shBrushAppleScript.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushAS3.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushBash.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushColdFusion.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushCpp.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushCSharp.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushCss.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushDelphi.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushDiff.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushErlang.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushGroovy.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushJava.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushJavaFX.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushJScript.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushPerl.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushPhp.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushPlain.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushPowerShell.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushPython.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushRuby.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushSass.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushScala.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushSql.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushVb.js"></script>
		<script type="text/javascript" src="sh/scripts/shBrushXml.js"></script>
		<script type="text/javascript">
		    SyntaxHighlighter.all()
		</script>

	<!-- BUGGY CODE INPUT FIELD -->
		<link rel="stylesheet" href="codemirror/lib/codemirror.css">
		<script type="text/javascript" src="codemirror/codemirror-compressed.js"></script>
		<script type="text/javascript" src="scripts/codeEditor.js"></script>
	<!--	
		<script src="codemirror/lib/codemirror.js"></script>

		<script src="codemirror/mode/clike/clike.js"></script>
		<script src="codemirror/mode/css/css.js"></script>
		<script src="codemirror/mode/diff/diff.js"></script>
		<script src="codemirror/mode/erlang/erlang.js"></script>
		<script src="codemirror/mode/groovy/groovy.js"></script>
		<script src="codemirror/mode/javascript/javascript.js"></script>
		<script src="codemirror/mode/perl/perl.js"></script>
		<script src="codemirror/mode/php/php.js"></script>
		<script src="codemirror/mode/python/python.js"></script>
		<script src="codemirror/mode/ruby/ruby.js"></script>
		<script src="codemirror/mode/sass/sass.js"></script>
		<script src="codemirror/mode/scala/scala.js"></script>
		<script src="codemirror/mode/shell/shell.js"></script>
		<script src="codemirror/mode/sql/sql.js"></script>
		<script src="codemirror/mode/vb/vb.js"></script>
		<script src="codemirror/mode/xml/xml.js"></script>
	-->
		
		<script type="text/javascript">
		 //displaying different code-blocks on button click
			function showDiv(el1,el2,el3){
				document.getElementById(el1).style.display = 'block';
				document.getElementById(el2).style.display = 'none';
				document.getElementById(el3).style.display = 'none';
			}

		 //truncating the tables and resetting the quiz
			function resetQuiz(){	
				if(confirm("Really wanna delete all the Questions?!")) {
					var x = new XMLHttpRequest();
					var url = "admin.php";
					var vars = 'reset=yes';
					x.open("POST", url, true);
					x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
					x.onreadystatechange = function() {
						if(x.readyState == 4 && x.status == 200) {
							document.getElementById("resetBtnMsg").innerHTML = x.responseText;
						}
					}
					x.send(vars);
					document.getElementById("resetBtnMsg").innerHTML = "processing...";
				}
			}
		</script>
	</head>

	<body onLoad="getAllQuestions()">

		<div id="head" align="center">
            <img src="img/header.jpg" alt="Chandigarh Engineering College" />
        </div>


		<div id="admin_menu">
			<p style="color:#06F;">
		   		<?php echo $msg; ?>
		   	</p>

			<h2>What type of question would you like to create?</h2>

			<table align="center">
				<tr>
					<td align="right">
						<button onClick="showDiv('tf', 'mc', 'quesans')">True / False</button>
					</td>
					<td></td>
					<td align="left">
						<button onClick="showDiv('mc', 'tf', 'quesans')">Multiple Choice</button>
					</td>
				</tr>
				<tr>
				</tr>
				<tr>
					<td align="center" colspan="3">
						<button onClick="showDiv('quesans', 'tf', 'mc')">View Questions</button>
					</td>
					<td></td>
				<!--
					<td align="left">
						<span id="resetBtn"><button onclick="resetQuiz()">Reset quiz to zero</button></span>
					</td>
				-->
				</tr>

			</table>

		    <br />
		    <span id="resetBtnMsg"></span>
		</div>


		<div class="content" id="tf" style="margin-bottom: 100px;">
			<h3>True or false</h3>
    	
    		<form action="admin.php" name="addQuestion" method="POST">

    			<strong>Please type your new question here</strong>
    			<br />

    			<textarea class="txt_area" id="tfDesc" name="desc" style="width:400px;height:95px;"></textarea>
    			<br />
    			<br />


    			<strong>If there's a programming code, enter here</strong>
    			<br />

    			<strong style="font-family: Times;">Select Language of the code: </strong>
    			<span class='css-select-moz'>
	    			<select class="lang_selector" name="prog-lang" onchange="lang_chosen(this);">
	    				<option value=""> ------ </option>
					  	<option value="applescript">AppleScript</option>
					  	<option value="actionscript3">ActionScript3</option>
					  	<option value="shell">Bash/Shell</option>
					  	<option value="coldfusion">ColdFusion</option>
					  	<option value="csharp">C#</option>
					  	<option value="cpp">C/C++</option>
					  	<option value="css">CSS</option>
					  	<option value="delphi">Delphi</option>
					  	<option value="diff">Diff</option>
					  	<option value="erlang">Erlang</option>
					  	<option value="groovy">Groovy</option>
					  	<option value="js">JavaScript</option>
					  	<option value="java">Java</option>
					  	<option value="javafx">JavaFX</option>
					  	<option value="perl">Perl</option>
					  	<option value="php">PHP</option>
					  	<option value="plain">Plain Text</option>
					  	<option value="powershell">PowerShell</option>
					  	<option value="python">Python</option>
					  	<option value="ruby">Ruby on Rails</option>
					  	<option value="sass">Sass</option>
					  	<option value="scala">Scala</option>
					  	<option value="sql">SQL</option>
					  	<option value="vbnet">VB.net</option>
					  	<option value="html">HTML/XML/xHTML/XSLT</option>
					</select> 
				</span>
    			<br />
    			<textarea class="txt_area" id="tfcodeDesc" name="code_desc" style="width:400px;height:95px;"></textarea>
    			<br />
    			
    			<br />


    			<strong>Please select whether true or false is the correct answer</strong>
    			<br />

            	<input type="text" class="txt_box" id="answer1" name="answer1" value="True" readonly>&nbsp;
            	<label style="cursor:pointer; color:#06F;">
            		<input type="radio" name="iscorrect" value="answer1">Correct Answer?
            	</label>
    	  		<br />
   				<br />
            	<input type="text" class="txt_box" id="answer2" name="answer2" value="False" readonly>&nbsp;
              	<label style="cursor:pointer; color:#06F;">
            		<input type="radio" name="iscorrect" value="answer2">Correct Answer?
            	</label>


    	  		<br />
    			<br />


    			<input type="hidden" value="tf" name="type">
    			<input type="hidden" value="<?php echo $username; ?>" name='login'>
    			<input type="hidden" value="<?php echo $passwrd; ?>" name='password'>
    			<input type="submit" value="Add To Quiz">
    		</form>
 		</div>
 

 		<div class="content" id="mc" style="margin-bottom: 100px;">
  			<h3>Multiple Choice</h3>

    		<form action="admin.php" name="addMcQuestion" method="POST">
    			<strong>Please type your new question here</strong>
        		<br />

        		<textarea class="txt_area" id="mcdesc" name="desc" style="width:400px;height:95px;"></textarea>
        		<br />
      			<br />


      			<strong>If there's a programming code, enter here</strong>
    			<br />

    			<strong style="font-family: Times;">Select Language of the code: </strong>
    			<span class='css-select-moz'>
	    			<select class="lang_selector" name="prog-lang" onchange="lang_chosen(this);">
	    				<option value=""> ------ </option>
					  	<option value="applescript">AppleScript</option>
					  	<option value="actionscript3">ActionScript3</option>
					  	<option value="shell">Bash/Shell</option>
					  	<option value="coldfusion">ColdFusion</option>
					  	<option value="csharp">C#</option>
					  	<option value="cpp">C/C++</option>
					  	<option value="css">CSS</option>
					  	<option value="delphi">Delphi</option>
					  	<option value="diff">Diff</option>
					  	<option value="erlang">Erlang</option>
					  	<option value="groovy">Groovy</option>
					  	<option value="js">JavaScript</option>
					  	<option value="java">Java</option>
					  	<option value="javafx">JavaFX</option>
					  	<option value="perl">Perl</option>
					  	<option value="php">PHP</option>
					  	<option value="plain">Plain Text</option>
					  	<option value="powershell">PowerShell</option>
					  	<option value="python">Python</option>
					  	<option value="ruby">Ruby on Rails</option>
					  	<option value="sass">Sass</option>
					  	<option value="scala">Scala</option>
					  	<option value="sql">SQL</option>
					  	<option value="vbnet">VB.net</option>
					  	<option value="html">HTML/XML/xHTML/XSLT</option>
					</select> 
				</span>
				<br />
    			<textarea class="txt_area" id="mccodeDesc" name="code_desc" style="width:400px;height:95px;"></textarea>
    			

    			<br />
    			<br />


    			<strong>Please create the first answer for the question</strong>
    			<br />
        		<input type="text" class="txt_box" id="mcanswer1" name="answer1">&nbsp;
          		<label style="cursor:pointer; color:#06F;">
          			<input type="radio" name="iscorrect" value="answer1">Correct Answer?
        		</label>
      			<br />
    			<br />
    			<strong>Please create the second answer for the question</strong>
    			<br />
        		<input type="text" class="txt_box" id="mcanswer2" name="answer2">&nbsp;
          		<label style="cursor:pointer; color:#06F;">
          			<input type="radio" name="iscorrect" value="answer2">Correct Answer?
        		</label>
      			<br />
    			<br />
    			<strong>Please create the third answer for the question</strong>
    			<br />
        		<input type="text" class="txt_box" id="mcanswer3" name="answer3">&nbsp;
          		<label style="cursor:pointer; color:#06F;">
          			<input type="radio" name="iscorrect" value="answer3">Correct Answer?
        		</label>
      			<br />
    			<br />
    			<strong>Please create the fourth answer for the question</strong>
    			<br />
        		<input type="text" class="txt_box" id="mcanswer4" name="answer4">&nbsp;
          		<label style="cursor:pointer; color:#06F;">
          			<input type="radio" name="iscorrect" value="answer4">Correct Answer?
        		</label>
      			<br />
    			<br />
    			<input type="hidden" value="mc" name="type">
    			<input type="hidden" value="<?php echo $username; ?>" name='login'>
    			<input type="hidden" value="<?php echo $passwrd; ?>" name='password'>
    			<input type="submit" value="Add To Quiz">
    		</form>
 		</div>


 		<div class="content" id="quesans"  style="margin-bottom: 100px;">
 			<table width="780px" align="center">
 				<?php echo $m_output; ?>
 			</table>
 		</div>


 		<div id="footer" align="bottom">
            <table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
                <tbody>
                    <tr>
                        <td align="left" id="copyright">
                            © Copyright 2014, under GNU General Public License
                        </td>
                        
                        <td align="right" id="developer" >
                            Quiz Designed &amp; Developed by : 
                            <a href="mailto: rahulgr8888@gmail.com" class="flink" style="color: #c4dcf5">
                                Rahul Jain
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
	</body>
</html>


