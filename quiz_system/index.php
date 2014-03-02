<?php 
    /*
    Short Programming Quiz Framework
        Copyright (C) 2014,  Rahul Jain

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
    	
    	Short Quiz Framework -- Copyright (C) 2014  Rahul Jain
        This program comes with ABSOLUTELY NO WARRANTY.
        This is free software, and you are welcome to redistribute it
        under certain conditions found in the GNU GPL license
    */

    $user_taken = "";
    if(isset($_GET['user_msg']) && $_GET['user_msg']!=""){
        $user_taken = $_GET['user_msg'];
    }

    $total_time = 15;
    $total_questions = 5;
?>

<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">

        <title>Instructions</title>

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

        <link rel="stylesheet" type="text/css" href="css/master.css">
        <script type="text/javascript" src="scripts/overlay.js"></script>

        <script type="text/javascript">
            function submit(){
                var x=document.forms["onlyForm"]["rollno"].value;
                if (x==null || x==""){
                    document.getElementById("enter_rollno").innerHTML = "Please Enter Your Roll No.";
                    exit();
                }
                document.getElementById('myForm').submit(); 
                return false;
            }
        </script>
    </head>

    <body>
        <div id="head" align="center">
            <img src="img/header.jpg" alt="Chandigarh Engineering College" />
        </div>

        <div id="main_body" align="center">
            <h2>So, you want to try your luck at the <big>QUIZ</big></h2>

            <h3 align="left">Here are the rules then:</h3>
            <div align="left">
                <ul>
                    <li>You've got <?php echo $total_time; ?> mins for attempting <?php echo $total_questions; ?> questions</li>
                    <li>If time runs out, your quiz will automatically be submitted</li>
                    <li>You'll only be getting confirmation pop-up once if you try to leave during the quiz</li>
                    <li>You can only attempt the quiz once</li>
                </ul>
            </div>

            <h3>GOOD LUCK!</h3>

            <form id="myForm" name="onlyForm" action="quiz.php" method="GET">
                <table align="center">
                    <tr>
                        <td align="center">
                            <input type="text" name="rollno" placeholder="Enter Your Roll No." autofocus required/>
                        </td>
                    </tr>
                    <tr>
                        <td align="center">
                            <h3>Click below when you are ready to start the quiz</h3>
                        </td>
                    </tr>
                    <tr>
                        <td align="center">
                            <a href="javascript:submit();" class="myButton">Click Here to Begin</a>
                            <input type="hidden" name="total_time" value="<?php echo $total_time; ?>" />
                            <input type="hidden" name="total_questions" value="<?php echo $total_questions; ?>" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div id = "enter_rollno" align="center"><?php echo $user_taken ?></div>
                        </td>
                    </tr>
                </table>
            </form>
        </div>

        <div id="video" class="white_content">
            <a name="Planet_Earth">
                <video id="video_player" controls preload="meta" height="480">
                    <source src="videos/video.mp4" type='video/mp4' />
                    <source src="videos/video.webmhd.webm" type='video/webm' />
                    Your browser doesn't seem to support the video tag.
                </video>
                
            </a>
        </div>

        <div id="fade_overlay">
            <a href="javascript:close_overlay();" style="cursor: default;">
                <div id="fade" class="black_overlay">
                </div>
            </a>
        </div>

        <div id="footer" align="bottom">
            <table border="0" cellpadding="0" cellspacing="0" style="width:100%;">
                <tbody>
                    <tr>
                        <td align="left" id="copyright">
                            © Copyright 2014, under 
                            <a href="gnu_gpl.txt" style="color: WHITE; text-decoration: none;" target="_blank">
                                GNU General Public License
                            </a>
                        </td>
                        <td align="center" id="video_link">
                            Getting Bored? Watch a  
                            <a href="javascript:open_overlay();" style="color: #c4dcf5">
                                Video</a>
                            to pass time!
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


