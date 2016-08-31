-- phpMyAdmin SQL Dump
-- version 4.0.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 09, 2014 at 05:33 PM
-- Server version: 5.6.14
-- PHP Version: 5.5.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `debug`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `last_login` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `last_login`) VALUES
(1, 'admin', '12345', '2014-03-09 21:58:05');

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE IF NOT EXISTS `answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer` varchar(255) NOT NULL,
  `correct` enum('0','1') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=133 ;

--
-- Dumping data for table `answers`
--

INSERT INTO `answers` (`id`, `quiz_id`, `question_id`, `answer`, `correct`) VALUES
(1, 1, 1, 'wrong escape sequence \\r instead of \\a', '1'),
(2, 1, 1, 'line 4 should be printed after line 5', '0'),
(3, 1, 1, 'no error', '0'),
(4, 1, 1, 'wrong escape sequence \\v instead of \\a', '0'),
(5, 1, 2, '-1,-1', '1'),
(6, 1, 2, '0,-1', '0'),
(7, 1, 2, '-1,-3', '0'),
(8, 1, 2, '0,1', '0'),
(9, 1, 3, 'The code converts upper case character to lower case', '1'),
(10, 1, 3, 'The code converts a string in to an integer', '0'),
(11, 1, 3, 'The code converts lower case character to upper case', '0'),
(12, 1, 3, 'Error in code', '0'),
(13, 1, 4, 'Error: Constant expression required at line case P: ', '1'),
(14, 1, 4, 'Error: No default value is specified', '0'),
(15, 1, 4, 'Error: There is no break statement in each case.', '0'),
(16, 1, 4, 'No error will be reported', '0'),
(17, 1, 5, 'funct();', '1'),
(18, 1, 5, 'funct;', '0'),
(19, 1, 5, 'funct x,y;', '0'),
(20, 1, 5, 'int funct();', '0'),
(21, 1, 6, '0 times', '1'),
(22, 1, 6, 'infinite times', '0'),
(23, 1, 6, '10 times', '0'),
(24, 1, 6, '11 times', '0'),
(25, 1, 7, 'no error', '1'),
(26, 1, 7, 'Error: RValue required', '0'),
(27, 1, 7, 'Error: Lvalue required', '0'),
(28, 1, 7, 'Error: cannot convert from ''const int *'' to ''int *const''', '0'),
(29, 1, 8, 'compile time error', '1'),
(30, 1, 8, 'Preprocessing error', '0'),
(31, 1, 8, 'Runtime error.', '0'),
(32, 1, 8, 'Runtime exception.', '0'),
(33, 1, 9, '50', '1'),
(34, 1, 9, '10', '0'),
(35, 1, 9, 'error', '0'),
(36, 1, 9, 'no output', '0'),
(37, 1, 10, '10', '1'),
(38, 1, 10, '9', '0'),
(39, 1, 10, '5', '0'),
(40, 1, 10, '1', '0'),
(41, 1, 11, 'Error: LValue required', '1'),
(42, 1, 11, 'Error: Declaration syntax', '0'),
(43, 1, 11, 'Error: Expression syntax', '0'),
(44, 1, 11, 'Error: Rvalue required', '0'),
(45, 1, 12, 'hidden', '1'),
(46, 1, 12, 'protected', '0'),
(47, 1, 12, 'private', '0'),
(48, 1, 12, 'public', '0'),
(49, 1, 13, 'False', '1'),
(50, 1, 13, 'True', '0'),
(51, 1, 14, 'a', '1'),
(52, 1, 14, 'ayqm', '0'),
(53, 1, 14, 'syntax error', '0'),
(54, 1, 14, 'compilation error', '0'),
(55, 1, 15, 'binary search', '1'),
(56, 1, 15, 'linear search', '0'),
(57, 1, 15, 'hash search', '0'),
(58, 1, 15, 'all of the above', '0'),
(59, 1, 16, 'prints ascii value for 100', '1'),
(60, 1, 16, '100', '0'),
(61, 1, 16, 'prints garbage value', '0'),
(62, 1, 16, 'none of these', '0'),
(63, 1, 17, 'True', '1'),
(64, 1, 17, 'False', '0'),
(65, 1, 18, 'True', '1'),
(66, 1, 18, 'False', '0'),
(67, 1, 19, 'linker error', '1'),
(68, 1, 19, 'compiler error', '0'),
(69, 1, 19, 'syntax error', '0'),
(70, 1, 19, 'no output,no error', '0'),
(71, 1, 20, 'type mismatch in redeclaration', '1'),
(72, 1, 20, 'Error: Expression syntax', '0'),
(73, 1, 20, 'Error: LValue required', '0'),
(74, 1, 20, 'Error: Rvalue required', '0'),
(75, 2, 21, '64', '1'),
(76, 2, 21, 'compilation error', '0'),
(77, 2, 21, 'syntax error', '0'),
(78, 2, 21, 'a cannot be converted from char to char*', '0'),
(79, 1, 22, 'no output,no error', '1'),
(80, 1, 22, 'EXAM', '0'),
(81, 1, 22, 'EXAM4', '0'),
(82, 1, 22, 'syntax error', '0'),
(83, 1, 23, 'Error: we may not get input for second scanf() statement', '1'),
(84, 1, 23, 'Error: suspicious char to in conversion in scanf()  ', '0'),
(85, 1, 23, 'No error ', '0'),
(86, 1, 23, 'None of above', '0'),
(91, 2, 25, 'char *str = &quot;char *str = %c%s%c; main(){ printf(str, 34, str, 34);}&quot;;', '1'),
(92, 2, 25, 'char *str = %c%s%c; main(){ printf(str, 34, str, 34);} ', '0'),
(93, 2, 25, 'No output ', '0'),
(94, 2, 25, 'Error in program', '0'),
(95, 1, 26, '0..1..2', '1'),
(96, 1, 26, 'compiler error', '0'),
(97, 1, 26, '0..0..0', '0'),
(98, 1, 26, '2..1..0', '0'),
(99, 2, 27, ' Error: typedef cannot be used until it is defined ', '1'),
(100, 2, 27, 'Error: in *NODEPTR', '0'),
(101, 2, 27, 'No error ', '0'),
(102, 2, 27, 'None of above', '0'),
(103, 2, 28, '-14-1-14412 ', '1'),
(104, 2, 28, '12211212 ', '0'),
(105, 2, 28, '12222324 ', '0'),
(106, 2, 28, 'nooutput,no error', '0'),
(107, 2, 29, 'Error: goto cannot takeover control to other function ', '1'),
(108, 2, 29, 'No Error: prints &quot;It works&quot;', '0'),
(109, 2, 29, ' Error: fun() cannot be accessed ', '0'),
(110, 2, 29, 'No error', '0'),
(111, 1, 30, 'Both 1 and 2 are incorrect.', '1'),
(112, 1, 30, 'Only 1 is correct.', '0'),
(113, 1, 30, ' Only 2 is correct.', '0'),
(114, 1, 30, 'Both 1 and 2 are correct.', '0'),
(115, 1, 31, 'you should not initialize variable in declaration', '1'),
(116, 1, 31, 'syntax error', '0'),
(117, 1, 31, 'no error', '0'),
(118, 1, 31, 'lvalue required here', '0'),
(119, 2, 32, 'compiler error', '1'),
(120, 2, 32, 'access violation', '0'),
(121, 2, 32, 'syntax error', '0'),
(122, 2, 32, 'none of the above', '0'),
(131, 1, 33, 'True', '1'),
(132, 1, 33, 'False', '0');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE IF NOT EXISTS `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `code` varchar(9999) NOT NULL,
  `code_type` varchar(30) NOT NULL,
  `type` varchar(2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=34 ;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `quiz_id`, `question_id`, `question`, `code`, `code_type`, `type`) VALUES
(1, 1, 1, 'If the output of the question is  hai , find the error in the program?', 'main()\r\n { \r\nprintf(&quot;\\nab&quot;);\r\nprintf(&quot;\\bsi&quot;);\r\nprintf(&quot;\\aha&quot;);\r\n\r\n}\r\n', 'cpp', 'mc'),
(2, 1, 2, 'find the output?', 'void main()\n{\nint i=1,y;\ny=i---i---i;\ncout&lt;&lt;y&lt;&lt;â€,â€&lt;&lt;i;\ngetch();\n}\n', 'cpp', 'mc'),
(3, 1, 3, 'find the output?', '#include&lt;stdio.h&gt;\r\n\r\nint main()\r\n{\r\ncharstr[20], *s;\r\nprintf(&quot;Enter a string\\n&quot;);\r\nscanf(&quot;%s&quot;, str);\r\n    s=str;\r\nwhile(*s != ''\\0'')\r\n    {\r\nif(*s &gt;= 97&amp;&amp; *s &lt;= 122)\r\n            *s = *s-32;\r\n        s++;\r\n    }\r\nprintf(&quot;%s&quot;,str);\r\nreturn0;\r\n}\r\n', 'cpp', 'mc'),
(4, 1, 4, 'find the error', '#include&lt;stdio.h&gt;\r\nint main()\r\n{\r\nint P = 10;\r\nswitch(P)\r\n    {\r\ncase10:\r\nprintf(&quot;Case 1&quot;);\r\n\r\ncase20:\r\nprintf(&quot;Case 2&quot;);\r\nbreak;\r\n\r\ncase P:\r\nprintf(&quot;Case 2&quot;);\r\nbreak;\r\n    }\r\nreturn0;\r\n}\r\n\r\n', 'cpp', 'mc'),
(5, 1, 5, 'find the correct valid function call...assuming the function exists', '', '', 'mc'),
(6, 1, 6, 'find the output...', 'int main()\n{\nint x;\nfor(x=-1; x&lt;=10; x++)\n    {\nif(x &lt; 5)\ncontinue;\nelse\nbreak;\nprintf(&quot;techfest&quot;);\n    }\n', 'cpp', 'mc'),
(7, 1, 7, 'find the error', '#include&lt;stdio.h&gt;\nint main(){\nconst int k=7;\nint *const q=&amp;k;\nprintf(&quot;%d&quot;, *q);\nreturn0;\n}', 'cpp', 'mc'),
(8, 1, 8, 'What happens when a class with parameterized constructors and having no default constructor is used in a program and we create an object that needs a zero-argument constructor?', '', '', 'mc'),
(9, 1, 9, 'find the output...', '	#include &lt;stdio.h&gt;\r\n#define a 10\r\nmain()\r\n{\r\n#define a 50\r\nprintf(&quot;%d&quot;,a);\r\n}\r\n', 'cpp', 'mc'),
(10, 1, 10, 'find the last value of x', 'int x;\r\nfor(x=0;x&lt;10;x++)\r\n	{}', 'cpp', 'mc'),
(11, 1, 11, 'find the error', '#include&lt;stdio.h&gt;\r\n\r\n int main()\r\n{\r\nint a[] = {10, 20, 30, 40, 50};\r\nint j;\r\nfor(j=0; j&lt;5; j++)\r\n    {\r\nprintf(&quot;%d\\n&quot;, a);\r\n        a++;\r\n    }\r\nreturn 0;\r\n}\r\n', 'cpp', 'mc'),
(12, 1, 12, 'which is not a protection level provided by classes in c++', '', '', 'mc'),
(13, 1, 13, 'In a call to printf() function the format specifier %b can be used to print binary equivalent of an integer.', '', '', 'tf'),
(14, 1, 14, 'tick the correct', 'void main ( )\r\n\r\n{\r\n\r\n  char *P = &quot;ayqm&quot; ;\r\n\r\n  char c;\r\n\r\n  c = ++*p ;\r\n\r\n  printf (&quot;%c&quot;, c);\r\n\r\n}\r\n\r\n', 'cpp', 'mc'),
(15, 1, 15, 'which of the following algo requires sorted array?', '', '', 'mc'),
(16, 1, 16, 'the statement prints', 'printf(&quot;%c&quot;,100);', 'cpp', 'mc'),
(17, 1, 17, 'all srings end up with a null zero....', '', '', 'tf'),
(18, 1, 18, 'character variable may contain up to seven literals...', '', '', 'tf'),
(19, 1, 19, 'find the error', 'main(){\nextern int i;\ni=20;\nprintf(&quot;%d&quot;,i);\n}', 'cpp', 'mc'),
(20, 2, 20, 'find the error', '	main()\r\n{\r\nchar string[]=&quot;Hello World&quot;;\r\n	display(string);\r\n}\r\nvoid display(char *string)\r\n{\r\n	printf(&quot;%s&quot;,string);\r\n}\r\n', 'cpp', 'mc'),
(21, 2, 21, 'find the error and the  output...', '#include&lt;stdio.h&gt;\r\nvoid main() \r\n{ \r\nint a=320; \r\nchar *ptr; \r\nptr=(char *)&amp;a; \r\nprintf(&quot;%d&quot;,*ptr); \r\ngetch();\r\n}\r\n', 'cpp', 'mc'),
(22, 1, 22, 'find the output', 'void main()\r\n\r\n{\r\n\r\n  int a = 1, b=2, c=3;\r\n\r\n  char d = 0;\r\n\r\n  if(a,b,c,d)\r\n\r\n  {\r\n\r\n    printf(&quot;EXAM&quot;);\r\n\r\n  }\r\n', 'cpp', 'mc'),
(23, 2, 23, 'find the  error', '#include&lt;stdio.h&gt;\nint main(){\nchar ch;\nint i;\nscanf(&quot;%c&quot;, &amp;i);\nscanf(&quot;%d&quot;, &amp;ch);\nprintf(&quot;%c %d&quot;, ch, i);\nreturn0;\n}', 'cpp', 'mc'),
(25, 2, 25, 'find the error', '#include&lt;stdio.h&gt;\r\nchar *str = &quot;char *str = %c%s%c; main(){ \r\nprintf(str, 34, str, 34);}&quot;;\r\n\r\nint main()\r\n{\r\nprintf(str, 34, str, 34);\r\nreturn 0;\r\n}\r\n', 'cpp', 'mc'),
(26, 2, 26, 'find the output', 'enum colors {BLACK,BLUE,GREEN}\r\nmain()\r\n{\r\n\r\nprintf(&quot;%d..%d..%d&quot;,BLACK,BLUE,GREEN);\r\n\r\nreturn(1);\r\n}\r\n', 'cpp', 'mc'),
(27, 2, 27, 'find the error...', 'typedefstruct\r\n{\r\nint data;\r\n    NODEPTR link;\r\n}*NODEPTR;\r\n', 'cpp', 'mc'),
(29, 2, 29, 'find the error', '#include&lt;stdio.h&gt;\r\nint main()\r\n{\r\nvoid fun();\r\ninti = 1;\r\nwhile(i&lt;= 5)\r\n    {\r\nprintf(&quot;%d\\n&quot;, i);\r\nif(i&gt;2)\r\ngoto here;\r\n    }\r\nreturn0;\r\n}\r\nvoid fun()\r\n{\r\n    here:\r\nprintf(&quot;It works&quot;);\r\n}\r\n', 'cpp', 'mc'),
(30, 1, 30, 'Which of the following statements is correct? \r\n1.	Once a reference variable has been defined to refer to a particular variable it can refer to any other variable. \r\n2.	A reference is not a constant pointer. \r\n\r\n\r\n', '', '', 'mc'),
(31, 2, 31, 'find the error..', '4.	#include&lt;stdio.h&gt;\r\nmain()\r\n{\r\nstruct xx\r\n{\r\nint x=3;\r\nchar name[]=&quot;hello&quot;;\r\n };\r\nstruct xx *s;\r\nprintf(&quot;%d&quot;,s-&gt;x);\r\nprintf(&quot;%s&quot;,s-&gt;name);\r\n}\r\n', 'cpp', 'mc'),
(32, 2, 32, 'find the error..', 'main() \r\n{ \r\ninti; \r\nclrscr(); \r\nprintf(&quot;%d&quot;, &amp;i)+1; \r\nscanf(&quot;%d&quot;, i)-1; \r\n} \r\n', 'cpp', 'mc'),
(33, 1, 33, 'hahaha', '', '', 'tf');

-- --------------------------------------------------------

--
-- Table structure for table `quizes`
--

CREATE TABLE IF NOT EXISTS `quizes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `quiz_name` varchar(50) NOT NULL,
  `total_questions` int(11) NOT NULL,
  `display_questions` int(11) NOT NULL,
  `time_allotted` int(11) NOT NULL,
  `set_default` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `quizes`
--

INSERT INTO `quizes` (`id`, `quiz_id`, `quiz_name`, `total_questions`, `display_questions`, `time_allotted`, `set_default`) VALUES
(1, 1, 'LEVEL1(EASY)', 22, 20, 30, 0),
(2, 2, 'LEVEL2(HARD)', 9, 10, 20, 1);

-- --------------------------------------------------------

--
-- Table structure for table `quiz_takers`
--

CREATE TABLE IF NOT EXISTS `quiz_takers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `marks` int(11) NOT NULL,
  `percentage` varchar(24) NOT NULL,
  `date_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `duration` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=20 ;

--
-- Dumping data for table `quiz_takers`
--

INSERT INTO `quiz_takers` (`id`, `username`, `quiz_id`, `marks`, `percentage`, `date_time`, `duration`) VALUES
(3, '1245', 2, 0, '0', '2014-03-06 09:53:41', 5),
(4, '456789', 2, 1, '11.111111111111', '2014-03-06 09:54:35', 8),
(5, '1139095', 2, 1, '11.111111111111', '2014-03-06 10:20:01', 638),
(6, '45698777', 2, 0, '0', '2014-03-06 10:26:18', 298),
(7, '1139113', 1, 0, '0', '2014-03-06 10:27:14', 3079612),
(8, 'yoyo', 1, 4, '20', '2014-03-09 18:12:55', 61),
(11, 'yoyo', 2, 0, '0', '2014-03-09 18:17:18', 0),
(13, '1139113', 1, 0, '0', '2014-03-09 18:23:22', 3079612),
(14, '877656443', 2, 0, '0', '2014-03-09 18:35:07', 49),
(15, '0987', 2, 0, '0', '2014-03-09 18:36:23', 0),
(16, '1qaz', 2, 0, '0', '2014-03-09 18:39:33', 0),
(17, '1qaz22', 2, 0, '0', '2014-03-09 18:40:25', 0),
(18, '11qaz22', 2, 0, '0', '2014-03-09 18:40:49', 190),
(19, '123321', 2, 0, '0', '2014-03-09 20:33:35', 1472);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
