-- phpMyAdmin SQL Dump
-- version 4.0.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 02, 2014 at 11:08 PM
-- Server version: 5.6.14
-- PHP Version: 5.5.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `prog_quiz`
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
(1, 'admin', '12345', '2014-03-03 03:37:01'),
(2, 'asdf', '123', '0000-00-00 00:00:00'),
(3, 'qwerty', 'yuiop', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE IF NOT EXISTS `answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `answer` varchar(255) NOT NULL,
  `correct` enum('0','1') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=81 ;

--
-- Dumping data for table `answers`
--

INSERT INTO `answers` (`id`, `question_id`, `answer`, `correct`) VALUES
(1, 1, 'True', '1'),
(2, 1, 'False', '0'),
(3, 2, 'aqdcx ', '1'),
(4, 2, 'nbcnb', '0'),
(5, 2, 'nmvgd', '0'),
(6, 2, 'opopipoipoi', '0'),
(7, 3, 'sup', '1'),
(8, 3, 'naa', '0'),
(9, 3, 'wha?', '0'),
(10, 3, 'qwerty:::', '0'),
(11, 4, 'True', '1'),
(12, 4, 'False', '0'),
(13, 5, 'False', '1'),
(14, 5, 'True', '0'),
(15, 6, 'dsfsdf', '1'),
(16, 6, 'dfsdf', '0'),
(17, 6, 'dsfdsf', '0'),
(18, 6, 'dsfdsf', '0'),
(19, 7, '25', '1'),
(20, 7, '5', '0'),
(21, 7, '125', '0'),
(22, 7, 'Garbage Value', '0'),
(23, 8, 'True', '1'),
(24, 8, 'False', '0'),
(25, 9, 'True', '1'),
(26, 9, 'False', '0'),
(27, 10, 'rahul jain', '1'),
(28, 10, 'rahul jain', '0'),
(29, 10, 'rahul jain', '0'),
(30, 10, 'rahul jain', '0'),
(31, 11, 'alladino', '1'),
(32, 11, 'hiv alladin', '0'),
(33, 11, 'pointy rocket', '0'),
(34, 11, 'you look me in the eye,its so disrespectful', '0'),
(35, 12, 'True', '1'),
(36, 12, 'False', '0'),
(37, 13, 'False', '1'),
(38, 13, 'True', '0'),
(39, 14, 'True', '1'),
(40, 14, 'False', '0'),
(41, 15, 'True', '1'),
(42, 15, 'False', '0'),
(43, 16, 'False', '1'),
(44, 16, 'True', '0'),
(45, 17, 'False', '1'),
(46, 17, 'True', '0'),
(47, 18, 'True', '1'),
(48, 18, 'False', '0'),
(49, 19, 'False', '1'),
(50, 19, 'True', '0'),
(51, 20, 'True', '1'),
(52, 20, 'False', '0'),
(53, 21, 'False', '1'),
(54, 21, 'True', '0'),
(55, 22, '1qwert', '1'),
(56, 22, '2asdfg', '0'),
(57, 22, '3zxcvb', '0'),
(58, 22, '4poiuy', '0'),
(59, 23, 'True', '1'),
(60, 23, 'False', '0'),
(61, 24, '12', '1'),
(62, 24, 'qw', '0'),
(63, 24, 'cv', '0'),
(64, 24, 'trtr', '0'),
(65, 25, 'True', '1'),
(66, 25, 'False', '0'),
(67, 26, 'False', '1'),
(68, 26, 'True', '0'),
(69, 27, 'True', '1'),
(70, 27, 'False', '0'),
(71, 28, 'True', '1'),
(72, 28, 'False', '0'),
(73, 29, 'True', '1'),
(74, 29, 'False', '0'),
(75, 30, 'True', '1'),
(76, 30, 'False', '0'),
(77, 31, 'True', '1'),
(78, 31, 'False', '0'),
(79, 32, 'True', '1'),
(80, 32, 'False', '0');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE IF NOT EXISTS `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `code` varchar(9999) NOT NULL,
  `code_type` varchar(30) NOT NULL,
  `type` varchar(2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=33 ;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `question_id`, `question`, `code`, `code_type`, `type`) VALUES
(7, 7, 'What will be the output of the program ?', '#include&lt;stdio.h&gt;\r\npower(int**);\r\nint main()\r\n{\r\n    int a=5, *aa; /* Address of ''a'' is 1000 */\r\n    aa = &amp;a;\r\n    a = power(&amp;aa);\r\n    printf(&quot;%d\\n&quot;, a);\r\n    return 0;\r\n}\r\npower(int **ptr)\r\n{\r\n    int b;\r\n    b = **ptr***ptr;\r\n    return (b);\r\n}', 'cpp', 'mc'),
(8, 8, 'HOHOHO', '&lt;!DOCTYPE html&gt;\r\n&lt;html&gt;\r\n&lt;head&gt;\r\n	&lt;title&gt;&lt;/title&gt;\r\n&lt;/head&gt;\r\n&lt;body&gt;\r\n\r\n&lt;/body&gt;\r\n&lt;/html&gt;', 'html', 'tf'),
(12, 12, 'Hey, Jude!', '', '', 'tf'),
(13, 13, 'Yerterdayyyyy!', '', 'java', 'tf'),
(14, 14, 'a c program', 'int i=1;', 'cpp', 'tf'),
(15, 15, '7656544', 'hfhgc\r\n	hgjhfj\r\n	', 'cpp', 'tf'),
(16, 16, 'PHPPP', '$qw = mysql_query(&quot;&quot;);', 'php', 'tf'),
(17, 17, 'another php', '&lt;?php\r\n	$How = mysql_query(&quot;&quot;);\r\n?&gt;', 'php', 'tf'),
(18, 18, 'another c or php?', 'void main(){\r\n	printf(&quot;%d&quot;, i);\r\n}', 'cpp', 'tf'),
(19, 19, 'which one?', 'C/C++', 'cpp', 'tf'),
(20, 20, 'checking source', 'int a=10;\r\nint b=20;', '', 'tf'),
(21, 21, 'sdlkfhsfd', 'var c=10;', 'js', 'tf'),
(22, 22, 'WHat??', 'yep = 20\r\n	cout&lt;&lt;10', 'python', 'mc'),
(23, 23, '1234', 'asjhkjdfhdmsg', 'groovy', 'tf'),
(24, 24, '78326478', 'nbcxbnxbncxnbxc', 'cpp', 'mc'),
(25, 25, '123', 'fgh', 'cpp', 'tf'),
(26, 26, '12345', 'int i=45;', 'cpp', 'tf'),
(27, 27, '12345', '121111', 'cpp', 'tf'),
(28, 28, 'qq', '', '', 'tf'),
(29, 29, 'asdf', 'vbvvb', 'javafx', 'tf'),
(30, 30, '123', 'void main(){\r\n	\r\n}', 'cpp', 'tf'),
(31, 31, '12345', '', '', 'tf'),
(32, 32, '1234567890', '', '', 'tf');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_takers`
--

CREATE TABLE IF NOT EXISTS `quiz_takers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `marks` int(11) NOT NULL,
  `percentage` varchar(24) NOT NULL,
  `date_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `duration` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `quiz_takers`
--

INSERT INTO `quiz_takers` (`id`, `username`, `marks`, `percentage`, `date_time`, `duration`) VALUES
(1, '1139234', 3, '60', '2014-03-03 03:35:52', 52);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
