#Programming Multiple Quiz System

    A simple Programming Quiz System containing-
  
* __Instruction page__
  * Student enters Roll No. here 
* __Quiz page__
  *  Timed-Quiz appears here
* __Result page__
  * Result of the user is shown here 
* __Login Page__
  * Admin has to login here first._(admin, 12345)_
* __Admin page__
  * All admin related functions are here!
* __Videos__
  * Users can also watch a video during the quiz. Or anything you want to display to them.
  
---

####APIs used to enter and Display Programming codes
* [CodeMirror](http://codemirror.net/) In-browser code editor.
* [SyntaxHighlighter](http://alexgorbatchev.com/SyntaxHighlighter/) JavaScript code syntax highlighter.

---

###Coded & Tested using-
* __[XAMPP](http://www.apachefriends.org/index.html)__ for __Apache__ server and __MySQL__.
* __[Sublime Text](http://www.sublimetext.com/)__ for code editing.
* __Firefox__ and __Chrome__ browsers.
* __Mac OSX__ & __Windows__.

---

###How to install on your system-
1. Install _XAMPP_ and make sure __Apache Web Server__ and __MySQL Database__ are running
2. copy-paste the __quiz_system_git__ folder in __htdocs__ folder associated with _XAMPP_
3. goto __phpMyAdmin__ and _create_ a database.
4. _import_ the quiz database from __database__ folder into it and make sure it has all _5_ tables.
4. _Truncate_ the tables to have clean database.
5. `INSERT` first admin in the __admins__ database.
6. make required changes in __scripts/connect_db.php__
7. Get _favicons_-
    * visit __[faviconit](http://faviconit.com/en)__ _(© 2013-2014 Eduardo Russo)_.
    * Upload atleast 200x200px img with maximum 1MB size limit.
    * click on __favicon it!__ and download the __zip__ file.
    * extract the __faviconit.zip__ in the __img__ folder.
8. Add videos in __webm__(_video.webmhd.webm_) and __mp4__(_video.mp4_) formats in __videos__ folder.
9. login to the admin page using _http://localhost/quiz_system_git/login.php_
10. start the quiz @ _http://localhost/quiz_system_git/_

---
---
---

##Features provided for the _Admin_ in _admin.php_ page-

* Clicking on your __username__ in the navbar _refreshes_ the page.
* __Quiz Homepage__ opens the _Instructions page_ in a new tab.

#### *Manage Questions*-
* __Create a Question__-
  * _True/False_ - Create a Question with 2 options- True and False.
  * _Multiple Choice_ - Create a Question with 4 options.
* __View All Questions__-
  * Shows all the questions of all the quizes on a single page along with the correct answers.
* __Edit a Question__-
  * Select one question from all the questions in all quizes to edit.
* __Delete Some Questions__-
  * Select as many questions as you want to delete from the database.
* __Delete All Questions__-
  * Deletes all the questions from all the quizes, but leaves the quizes intact.

#### *Quiz Management*-
    You will see all your quizes lined up here.
* __Add New Quiz__-
  * Add a new quiz to the database and allot the Duration for the quiz, and Max no. of Questions to be displayed to the users.
* Further list contains __QuizName__ along with the time and no. of questions allotted to it.

####### Hovering over a QuizName-
* __Quiz Settings__
  * _Set Default_ - Sets the quiz as default i.e. it will be the one USER will be attempting.
  * _Update Metadata_ - edit the Duration for the quiz, and Max no. of Questions to be displayed.
  * _Delete this Quiz_ - Deletes the quiz along with all its questions.
* __Manage Questions__
  * _View All Questions_ - Shows all Questions of the quiz
  * _Edit a Question_ - Edit a single question from the quiz
  * _Delete Some Questions_ - Delete some questions from the quiz
* __Results__
  * _Result(Top 20)_ - Shows the top 20 Rank Holders.
  * _Result(All)_ - Shows all the users, sorted according to rank.
  * _Clear the Result_ - Delete all records of the users who took this quiz.
  
#### *Settings*

* _Register an Admin_
  * Register a new admin
* _Change Password_
  * Change password to your account
* _Delete Your Account_
  * Delete Your admin account
* _Reset All Tables_
  * Truncates all tables and sets one admin ID as default _(admin, 12345)_
* _LogOut_
  * Log out of your admin account.
    
---

#### Interface for Creating/Editing a Question

1. Select/re-select the quiz to which you want to add/transfer the question.
    * If there are no quizes in the dropdown, create a Quiz first.
2. Type/Change your Question.
3. If there's a program/code in the question,
    * Select/Change Language of the code
    * Add/Edit the code below
4. If adding a True/False Question-
    * Select the correct answer
5. If adding a Multiple Choice Question-
    * Write/Edit the 4 options(single lined) and choose the correct one.
6. Click Add to quiz/Save.

#### Interface for Viewing/SelectingForEdit/DeletingSome Questions

1. Quiz_Name is displayed above its question.
2. If there's code, it'll be displayed after the text part of the Question.
3. Options are displayed below.
4. Correct answer is Underlined and Emphasised!
5. You can select only 1 question for editing.
6. You can select any number of questions to delete.

#### Result Interface

1. List is already sorted according to their **ranks**.
2. Ranks are decided on the basis of *highest marks first*, followed by the *time taken by the user* to finish the quiz.
3. TimeStamp contains the time user started the quiz.


---
---
---
## Features provided in other pages

#### # index.php(*Instructions page*)

1. Quiz name along with no. of questions to display and durations is displayed here.
2. An overlay is provided, currently used to display a video to the user while they wait to start the quiz.
3. User can't proceed without entering the username/RollNo.
4. User can't attempt the same quiz twice.
5. HTML can be edited to add your own rules and the like.
6. Right-click is disabled.

#### # quiz.php(*Quiz page*)

1. Quiz name is displayed on the top
2. Countdown is fixed & displayed on the top-right of the screen.
3. At zero the quiz will automatically be submitted.4. 
4. There's no negative marking.
5. User can answer none, some or all the questions.
6. Code/Program will be displayed in color-coded format.
7. A confirmation dialog will pop-up if the user tries to close, refresh or navigate away from this page.
8. Right-click is disabled.
9. An overlay is provided, currently used to display a video to the user during the quiz.
10. User can't directly access this page.

#### # result.php(*Result page*)

1. Marks of the user are dosplayed here(1 mark per correct answer).
2. An overlay is provided, currently used to display a message to the user on click.
3. User can't directly access this page.
4. if user clicks on back and goes back to the quiz, he can't resubmit the quiz.

#### # login.php(Admin-Login page)

1. A user can login from multiple systems at the same time.
2. User can't access admin page before logging in.
3. Using sessions to store the session of an admin. Re-login isn't necessary as long as the admin doesn't click logOut.


---
---

###Miscellaneous features

1. Using *codemirror* to have a better textarea input for programs/codes.
2. Using *syntax highlighter* to display the code better in a question.
3. A lot of *MYSQL error checks* are in place in case of some unknown error.
4. Basic **failsafes** are in place for things like *SQL injection*.
5. Layout is designed keeping *different window sizes* in mind.
6. It isn't designed to work on **Internet Explorer** also. So, it might be displayed differently on that(Styling difference).
7. None of the other pages can be accessed directly except the **logout.php** as it can only be accessed by the admin to logout.

