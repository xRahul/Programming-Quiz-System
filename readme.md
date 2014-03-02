#Programming Quiz System

A simple Programming Quiz System containing-
  
* __Instruction page__
  * Student enters Roll No. here 
* __Quiz page__
  *  Timed-Quiz appears here
* __Result page__
  * Result of the user is shown here 
* __Admin page__
  * Admin can add True/False or Multiple Choice questions here.
  * Admin can also view all the questions along with the correct Answers.
* __Videos__
  * Users can also watch a video during the quiz.
  


####APIs used to enter and Display Programming codes
* [CodeMirror](http://codemirror.net/) In-browser code editor.
* [SyntaxHighlighter](http://alexgorbatchev.com/SyntaxHighlighter/) JavaScript code syntax highlighter.

---

###Settings to change
* Set the __timer___(in mins)_ for the Quiz and __No. of questions__ by changin values of variables in lines `30,31` in _index.php_.
* Add videos in the __videos__ folder and link them in lines `138,139` in _index.php_.
* Everything is in `$_GET` to better understand things. It needs to be changed to `$_POST` for better security.
* How to get favicons
  * visit __[faviconit](http://faviconit.com/en)__ _(© 2013-2014 Eduardo Russo)_.
  * Upload atleast 200x200px img with maximum 1MB size limit.
  * click on __favicon it!__ and download the __zip__ file.
  * extract the __faviconit.zip__ in the __img__ folder.

---

###Coded & Tested using-
* __[XAMPP](http://www.apachefriends.org/index.html)__ for __Apache__ server and __MySQL__.
* __[Sublime Text](http://www.sublimetext.com/)__ for code editing.
* __Firefox__ and __Chrome__ browsers.
* __Mac OSX__ & __Windows__.

---

###How to install on your system-
1. Install _XAMPP_ and make sure __Apache Web Server__ and __MySQL Database__ are running
2. copy-paste the __quiz_system__ folder in __htdocs__ folder associated with _XAMPP_
3. goto __phpMyAdmin__ and _import_ the quiz database from  and make sure it has all 3 tables.
4. Truncate the tables to have clean database.
5. make required changes in __scripts/connect_db.php__
6. start adding questions by going to _http://localhost/quiz_system/admin.php_
7. start the quiz @ _http://localhost/quiz_system/_

---

####Bugs I know of-
* __CodeMirror__ isn't working properly, i.e, everytime we select a new language in `admin.php`, a new _textarea_ is appended above the previous one instead of replacing the one we have to refresh the page, or ignore the textareas except the _top-most_ one.
* User is able to edit the values of `No. of Questions` and `Total Time` by editing the HTML of the page.
  * and as the `%age` is being stored in the __database__ and not the `marks`, user can set `No. of Questions` to 1 and if he's correct, can get 100% marks.
* Login system for admin isn't working properly.