/*M!999999\- enable the sandbox mode */ 
-- Base schema+seed snapshot for the revitalized app: legacy database/debug.sql
-- imported into a scratch DB and migrated via database/migrate.sh (001..007).
-- Seeds preserved except mandated transforms: charset utf8mb4, deduped taker
-- pair (kept id 7), 4 orphan answers purged, stored entities decoded (see
-- *_entitybak tables in any migrated DB for pre-decode values). Re-importing
-- this file leaves migrate.sh with nothing to apply.
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: debug
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `last_login` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--
-- ORDER BY:  `id`

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES
(1,'admin','$2y$10$Fp6Ozh0DYIp2hoDQ08wqx.xO9kSPLFB3.TwrLoRIBMkNUkDft8iEO','2014-03-09 21:58:05');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `answers`
--

DROP TABLE IF EXISTS `answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer` varchar(255) NOT NULL,
  `correct` enum('0','1') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_answers_question_id` (`question_id`),
  KEY `fk_answers_quiz` (`quiz_id`),
  CONSTRAINT `fk_answers_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_answers_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `answers`
--
-- ORDER BY:  `id`

LOCK TABLES `answers` WRITE;
/*!40000 ALTER TABLE `answers` DISABLE KEYS */;
INSERT INTO `answers` VALUES
(1,1,1,'wrong escape sequence \\r instead of \\a','1'),
(2,1,1,'line 4 should be printed after line 5','0'),
(3,1,1,'no error','0'),
(4,1,1,'wrong escape sequence \\v instead of \\a','0'),
(5,1,2,'-1,-1','1'),
(6,1,2,'0,-1','0'),
(7,1,2,'-1,-3','0'),
(8,1,2,'0,1','0'),
(9,1,3,'The code converts upper case character to lower case','1'),
(10,1,3,'The code converts a string in to an integer','0'),
(11,1,3,'The code converts lower case character to upper case','0'),
(12,1,3,'Error in code','0'),
(13,1,4,'Error: Constant expression required at line case P: ','1'),
(14,1,4,'Error: No default value is specified','0'),
(15,1,4,'Error: There is no break statement in each case.','0'),
(16,1,4,'No error will be reported','0'),
(17,1,5,'funct();','1'),
(18,1,5,'funct;','0'),
(19,1,5,'funct x,y;','0'),
(20,1,5,'int funct();','0'),
(21,1,6,'0 times','1'),
(22,1,6,'infinite times','0'),
(23,1,6,'10 times','0'),
(24,1,6,'11 times','0'),
(25,1,7,'no error','1'),
(26,1,7,'Error: RValue required','0'),
(27,1,7,'Error: Lvalue required','0'),
(28,1,7,'Error: cannot convert from \'const int *\' to \'int *const\'','0'),
(29,1,8,'compile time error','1'),
(30,1,8,'Preprocessing error','0'),
(31,1,8,'Runtime error.','0'),
(32,1,8,'Runtime exception.','0'),
(33,1,9,'50','1'),
(34,1,9,'10','0'),
(35,1,9,'error','0'),
(36,1,9,'no output','0'),
(37,1,10,'10','1'),
(38,1,10,'9','0'),
(39,1,10,'5','0'),
(40,1,10,'1','0'),
(41,1,11,'Error: LValue required','1'),
(42,1,11,'Error: Declaration syntax','0'),
(43,1,11,'Error: Expression syntax','0'),
(44,1,11,'Error: Rvalue required','0'),
(45,1,12,'hidden','1'),
(46,1,12,'protected','0'),
(47,1,12,'private','0'),
(48,1,12,'public','0'),
(49,1,13,'False','1'),
(50,1,13,'True','0'),
(51,1,14,'a','1'),
(52,1,14,'ayqm','0'),
(53,1,14,'syntax error','0'),
(54,1,14,'compilation error','0'),
(55,1,15,'binary search','1'),
(56,1,15,'linear search','0'),
(57,1,15,'hash search','0'),
(58,1,15,'all of the above','0'),
(59,1,16,'prints ascii value for 100','1'),
(60,1,16,'100','0'),
(61,1,16,'prints garbage value','0'),
(62,1,16,'none of these','0'),
(63,1,17,'True','1'),
(64,1,17,'False','0'),
(65,1,18,'True','1'),
(66,1,18,'False','0'),
(67,1,19,'linker error','1'),
(68,1,19,'compiler error','0'),
(69,1,19,'syntax error','0'),
(70,1,19,'no output,no error','0'),
(71,1,20,'type mismatch in redeclaration','1'),
(72,1,20,'Error: Expression syntax','0'),
(73,1,20,'Error: LValue required','0'),
(74,1,20,'Error: Rvalue required','0'),
(75,2,21,'64','1'),
(76,2,21,'compilation error','0'),
(77,2,21,'syntax error','0'),
(78,2,21,'a cannot be converted from char to char*','0'),
(79,1,22,'no output,no error','1'),
(80,1,22,'EXAM','0'),
(81,1,22,'EXAM4','0'),
(82,1,22,'syntax error','0'),
(83,1,23,'Error: we may not get input for second scanf() statement','1'),
(84,1,23,'Error: suspicious char to in conversion in scanf()  ','0'),
(85,1,23,'No error ','0'),
(86,1,23,'None of above','0'),
(91,2,25,'char *str = \"char *str = %c%s%c; main(){ printf(str, 34, str, 34);}\";','1'),
(92,2,25,'char *str = %c%s%c; main(){ printf(str, 34, str, 34);} ','0'),
(93,2,25,'No output ','0'),
(94,2,25,'Error in program','0'),
(95,1,26,'0..1..2','1'),
(96,1,26,'compiler error','0'),
(97,1,26,'0..0..0','0'),
(98,1,26,'2..1..0','0'),
(99,2,27,' Error: typedef cannot be used until it is defined ','1'),
(100,2,27,'Error: in *NODEPTR','0'),
(101,2,27,'No error ','0'),
(102,2,27,'None of above','0'),
(107,2,29,'Error: goto cannot takeover control to other function ','1'),
(108,2,29,'No Error: prints \"It works\"','0'),
(109,2,29,' Error: fun() cannot be accessed ','0'),
(110,2,29,'No error','0'),
(111,1,30,'Both 1 and 2 are incorrect.','1'),
(112,1,30,'Only 1 is correct.','0'),
(113,1,30,' Only 2 is correct.','0'),
(114,1,30,'Both 1 and 2 are correct.','0'),
(115,1,31,'you should not initialize variable in declaration','1'),
(116,1,31,'syntax error','0'),
(117,1,31,'no error','0'),
(118,1,31,'lvalue required here','0'),
(119,2,32,'compiler error','1'),
(120,2,32,'access violation','0'),
(121,2,32,'syntax error','0'),
(122,2,32,'none of the above','0'),
(131,1,33,'True','1'),
(132,1,33,'False','0');
/*!40000 ALTER TABLE `answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `answers_entitybak`
--

DROP TABLE IF EXISTS `answers_entitybak`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `answers_entitybak` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer` varchar(255) NOT NULL,
  `correct` enum('0','1') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_answers_question_id` (`question_id`),
  KEY `fk_answers_quiz` (`quiz_id`)
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `answers_entitybak`
--
-- ORDER BY:  `id`

LOCK TABLES `answers_entitybak` WRITE;
/*!40000 ALTER TABLE `answers_entitybak` DISABLE KEYS */;
INSERT INTO `answers_entitybak` VALUES
(1,1,1,'wrong escape sequence \\r instead of \\a','1'),
(2,1,1,'line 4 should be printed after line 5','0'),
(3,1,1,'no error','0'),
(4,1,1,'wrong escape sequence \\v instead of \\a','0'),
(5,1,2,'-1,-1','1'),
(6,1,2,'0,-1','0'),
(7,1,2,'-1,-3','0'),
(8,1,2,'0,1','0'),
(9,1,3,'The code converts upper case character to lower case','1'),
(10,1,3,'The code converts a string in to an integer','0'),
(11,1,3,'The code converts lower case character to upper case','0'),
(12,1,3,'Error in code','0'),
(13,1,4,'Error: Constant expression required at line case P: ','1'),
(14,1,4,'Error: No default value is specified','0'),
(15,1,4,'Error: There is no break statement in each case.','0'),
(16,1,4,'No error will be reported','0'),
(17,1,5,'funct();','1'),
(18,1,5,'funct;','0'),
(19,1,5,'funct x,y;','0'),
(20,1,5,'int funct();','0'),
(21,1,6,'0 times','1'),
(22,1,6,'infinite times','0'),
(23,1,6,'10 times','0'),
(24,1,6,'11 times','0'),
(25,1,7,'no error','1'),
(26,1,7,'Error: RValue required','0'),
(27,1,7,'Error: Lvalue required','0'),
(28,1,7,'Error: cannot convert from \'const int *\' to \'int *const\'','0'),
(29,1,8,'compile time error','1'),
(30,1,8,'Preprocessing error','0'),
(31,1,8,'Runtime error.','0'),
(32,1,8,'Runtime exception.','0'),
(33,1,9,'50','1'),
(34,1,9,'10','0'),
(35,1,9,'error','0'),
(36,1,9,'no output','0'),
(37,1,10,'10','1'),
(38,1,10,'9','0'),
(39,1,10,'5','0'),
(40,1,10,'1','0'),
(41,1,11,'Error: LValue required','1'),
(42,1,11,'Error: Declaration syntax','0'),
(43,1,11,'Error: Expression syntax','0'),
(44,1,11,'Error: Rvalue required','0'),
(45,1,12,'hidden','1'),
(46,1,12,'protected','0'),
(47,1,12,'private','0'),
(48,1,12,'public','0'),
(49,1,13,'False','1'),
(50,1,13,'True','0'),
(51,1,14,'a','1'),
(52,1,14,'ayqm','0'),
(53,1,14,'syntax error','0'),
(54,1,14,'compilation error','0'),
(55,1,15,'binary search','1'),
(56,1,15,'linear search','0'),
(57,1,15,'hash search','0'),
(58,1,15,'all of the above','0'),
(59,1,16,'prints ascii value for 100','1'),
(60,1,16,'100','0'),
(61,1,16,'prints garbage value','0'),
(62,1,16,'none of these','0'),
(63,1,17,'True','1'),
(64,1,17,'False','0'),
(65,1,18,'True','1'),
(66,1,18,'False','0'),
(67,1,19,'linker error','1'),
(68,1,19,'compiler error','0'),
(69,1,19,'syntax error','0'),
(70,1,19,'no output,no error','0'),
(71,1,20,'type mismatch in redeclaration','1'),
(72,1,20,'Error: Expression syntax','0'),
(73,1,20,'Error: LValue required','0'),
(74,1,20,'Error: Rvalue required','0'),
(75,2,21,'64','1'),
(76,2,21,'compilation error','0'),
(77,2,21,'syntax error','0'),
(78,2,21,'a cannot be converted from char to char*','0'),
(79,1,22,'no output,no error','1'),
(80,1,22,'EXAM','0'),
(81,1,22,'EXAM4','0'),
(82,1,22,'syntax error','0'),
(83,1,23,'Error: we may not get input for second scanf() statement','1'),
(84,1,23,'Error: suspicious char to in conversion in scanf()  ','0'),
(85,1,23,'No error ','0'),
(86,1,23,'None of above','0'),
(91,2,25,'char *str = &quot;char *str = %c%s%c; main(){ printf(str, 34, str, 34);}&quot;;','1'),
(92,2,25,'char *str = %c%s%c; main(){ printf(str, 34, str, 34);} ','0'),
(93,2,25,'No output ','0'),
(94,2,25,'Error in program','0'),
(95,1,26,'0..1..2','1'),
(96,1,26,'compiler error','0'),
(97,1,26,'0..0..0','0'),
(98,1,26,'2..1..0','0'),
(99,2,27,' Error: typedef cannot be used until it is defined ','1'),
(100,2,27,'Error: in *NODEPTR','0'),
(101,2,27,'No error ','0'),
(102,2,27,'None of above','0'),
(107,2,29,'Error: goto cannot takeover control to other function ','1'),
(108,2,29,'No Error: prints &quot;It works&quot;','0'),
(109,2,29,' Error: fun() cannot be accessed ','0'),
(110,2,29,'No error','0'),
(111,1,30,'Both 1 and 2 are incorrect.','1'),
(112,1,30,'Only 1 is correct.','0'),
(113,1,30,' Only 2 is correct.','0'),
(114,1,30,'Both 1 and 2 are correct.','0'),
(115,1,31,'you should not initialize variable in declaration','1'),
(116,1,31,'syntax error','0'),
(117,1,31,'no error','0'),
(118,1,31,'lvalue required here','0'),
(119,2,32,'compiler error','1'),
(120,2,32,'access violation','0'),
(121,2,32,'syntax error','0'),
(122,2,32,'none of the above','0'),
(131,1,33,'True','1'),
(132,1,33,'False','0');
/*!40000 ALTER TABLE `answers_entitybak` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `actor` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `detail` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--
-- ORDER BY:  `id`

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL DEFAULT 0,
  `question` varchar(255) NOT NULL,
  `code` varchar(9999) NOT NULL,
  `code_type` varchar(30) NOT NULL,
  `type` varchar(2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_questions_quiz_id` (`quiz_id`),
  CONSTRAINT `fk_questions_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `questions`
--
-- ORDER BY:  `id`

LOCK TABLES `questions` WRITE;
/*!40000 ALTER TABLE `questions` DISABLE KEYS */;
INSERT INTO `questions` VALUES
(1,1,1,'If the output of the question is  hai , find the error in the program?','main()\r\n { \r\nprintf(\"\\nab\");\r\nprintf(\"\\bsi\");\r\nprintf(\"\\aha\");\r\n\r\n}\r\n','cpp','mc'),
(2,1,2,'find the output?','void main()\n{\nint i=1,y;\ny=i---i---i;\ncout<<y<<â€,â€<<i;\ngetch();\n}\n','cpp','mc'),
(3,1,3,'find the output?','#include<stdio.h>\r\n\r\nint main()\r\n{\r\ncharstr[20], *s;\r\nprintf(\"Enter a string\\n\");\r\nscanf(\"%s\", str);\r\n    s=str;\r\nwhile(*s != \'\\0\')\r\n    {\r\nif(*s >= 97&& *s <= 122)\r\n            *s = *s-32;\r\n        s++;\r\n    }\r\nprintf(\"%s\",str);\r\nreturn0;\r\n}\r\n','cpp','mc'),
(4,1,4,'find the error','#include<stdio.h>\r\nint main()\r\n{\r\nint P = 10;\r\nswitch(P)\r\n    {\r\ncase10:\r\nprintf(\"Case 1\");\r\n\r\ncase20:\r\nprintf(\"Case 2\");\r\nbreak;\r\n\r\ncase P:\r\nprintf(\"Case 2\");\r\nbreak;\r\n    }\r\nreturn0;\r\n}\r\n\r\n','cpp','mc'),
(5,1,5,'find the correct valid function call...assuming the function exists','','','mc'),
(6,1,6,'find the output...','int main()\n{\nint x;\nfor(x=-1; x<=10; x++)\n    {\nif(x < 5)\ncontinue;\nelse\nbreak;\nprintf(\"techfest\");\n    }\n','cpp','mc'),
(7,1,7,'find the error','#include<stdio.h>\nint main(){\nconst int k=7;\nint *const q=&k;\nprintf(\"%d\", *q);\nreturn0;\n}','cpp','mc'),
(8,1,8,'What happens when a class with parameterized constructors and having no default constructor is used in a program and we create an object that needs a zero-argument constructor?','','','mc'),
(9,1,9,'find the output...','	#include <stdio.h>\r\n#define a 10\r\nmain()\r\n{\r\n#define a 50\r\nprintf(\"%d\",a);\r\n}\r\n','cpp','mc'),
(10,1,10,'find the last value of x','int x;\r\nfor(x=0;x<10;x++)\r\n	{}','cpp','mc'),
(11,1,11,'find the error','#include<stdio.h>\r\n\r\n int main()\r\n{\r\nint a[] = {10, 20, 30, 40, 50};\r\nint j;\r\nfor(j=0; j<5; j++)\r\n    {\r\nprintf(\"%d\\n\", a);\r\n        a++;\r\n    }\r\nreturn 0;\r\n}\r\n','cpp','mc'),
(12,1,12,'which is not a protection level provided by classes in c++','','','mc'),
(13,1,13,'In a call to printf() function the format specifier %b can be used to print binary equivalent of an integer.','','','tf'),
(14,1,14,'tick the correct','void main ( )\r\n\r\n{\r\n\r\n  char *P = \"ayqm\" ;\r\n\r\n  char c;\r\n\r\n  c = ++*p ;\r\n\r\n  printf (\"%c\", c);\r\n\r\n}\r\n\r\n','cpp','mc'),
(15,1,15,'which of the following algo requires sorted array?','','','mc'),
(16,1,16,'the statement prints','printf(\"%c\",100);','cpp','mc'),
(17,1,17,'all srings end up with a null zero....','','','tf'),
(18,1,18,'character variable may contain up to seven literals...','','','tf'),
(19,1,19,'find the error','main(){\nextern int i;\ni=20;\nprintf(\"%d\",i);\n}','cpp','mc'),
(20,2,20,'find the error','	main()\r\n{\r\nchar string[]=\"Hello World\";\r\n	display(string);\r\n}\r\nvoid display(char *string)\r\n{\r\n	printf(\"%s\",string);\r\n}\r\n','cpp','mc'),
(21,2,21,'find the error and the  output...','#include<stdio.h>\r\nvoid main() \r\n{ \r\nint a=320; \r\nchar *ptr; \r\nptr=(char *)&a; \r\nprintf(\"%d\",*ptr); \r\ngetch();\r\n}\r\n','cpp','mc'),
(22,1,22,'find the output','void main()\r\n\r\n{\r\n\r\n  int a = 1, b=2, c=3;\r\n\r\n  char d = 0;\r\n\r\n  if(a,b,c,d)\r\n\r\n  {\r\n\r\n    printf(\"EXAM\");\r\n\r\n  }\r\n','cpp','mc'),
(23,2,23,'find the  error','#include<stdio.h>\nint main(){\nchar ch;\nint i;\nscanf(\"%c\", &i);\nscanf(\"%d\", &ch);\nprintf(\"%c %d\", ch, i);\nreturn0;\n}','cpp','mc'),
(25,2,25,'find the error','#include<stdio.h>\r\nchar *str = \"char *str = %c%s%c; main(){ \r\nprintf(str, 34, str, 34);}\";\r\n\r\nint main()\r\n{\r\nprintf(str, 34, str, 34);\r\nreturn 0;\r\n}\r\n','cpp','mc'),
(26,2,26,'find the output','enum colors {BLACK,BLUE,GREEN}\r\nmain()\r\n{\r\n\r\nprintf(\"%d..%d..%d\",BLACK,BLUE,GREEN);\r\n\r\nreturn(1);\r\n}\r\n','cpp','mc'),
(27,2,27,'find the error...','typedefstruct\r\n{\r\nint data;\r\n    NODEPTR link;\r\n}*NODEPTR;\r\n','cpp','mc'),
(29,2,29,'find the error','#include<stdio.h>\r\nint main()\r\n{\r\nvoid fun();\r\ninti = 1;\r\nwhile(i<= 5)\r\n    {\r\nprintf(\"%d\\n\", i);\r\nif(i>2)\r\ngoto here;\r\n    }\r\nreturn0;\r\n}\r\nvoid fun()\r\n{\r\n    here:\r\nprintf(\"It works\");\r\n}\r\n','cpp','mc'),
(30,1,30,'Which of the following statements is correct? \r\n1.	Once a reference variable has been defined to refer to a particular variable it can refer to any other variable. \r\n2.	A reference is not a constant pointer. \r\n\r\n\r\n','','','mc'),
(31,2,31,'find the error..','4.	#include<stdio.h>\r\nmain()\r\n{\r\nstruct xx\r\n{\r\nint x=3;\r\nchar name[]=\"hello\";\r\n };\r\nstruct xx *s;\r\nprintf(\"%d\",s->x);\r\nprintf(\"%s\",s->name);\r\n}\r\n','cpp','mc'),
(32,2,32,'find the error..','main() \r\n{ \r\ninti; \r\nclrscr(); \r\nprintf(\"%d\", &i)+1; \r\nscanf(\"%d\", i)-1; \r\n} \r\n','cpp','mc'),
(33,1,33,'hahaha','','','tf');
/*!40000 ALTER TABLE `questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `questions_entitybak`
--

DROP TABLE IF EXISTS `questions_entitybak`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `questions_entitybak` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL DEFAULT 0,
  `question` varchar(255) NOT NULL,
  `code` varchar(9999) NOT NULL,
  `code_type` varchar(30) NOT NULL,
  `type` varchar(2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_questions_quiz_id` (`quiz_id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `questions_entitybak`
--
-- ORDER BY:  `id`

LOCK TABLES `questions_entitybak` WRITE;
/*!40000 ALTER TABLE `questions_entitybak` DISABLE KEYS */;
INSERT INTO `questions_entitybak` VALUES
(1,1,1,'If the output of the question is  hai , find the error in the program?','main()\r\n { \r\nprintf(&quot;\\nab&quot;);\r\nprintf(&quot;\\bsi&quot;);\r\nprintf(&quot;\\aha&quot;);\r\n\r\n}\r\n','cpp','mc'),
(2,1,2,'find the output?','void main()\n{\nint i=1,y;\ny=i---i---i;\ncout&lt;&lt;y&lt;&lt;â€,â€&lt;&lt;i;\ngetch();\n}\n','cpp','mc'),
(3,1,3,'find the output?','#include&lt;stdio.h&gt;\r\n\r\nint main()\r\n{\r\ncharstr[20], *s;\r\nprintf(&quot;Enter a string\\n&quot;);\r\nscanf(&quot;%s&quot;, str);\r\n    s=str;\r\nwhile(*s != \'\\0\')\r\n    {\r\nif(*s &gt;= 97&amp;&amp; *s &lt;= 122)\r\n            *s = *s-32;\r\n        s++;\r\n    }\r\nprintf(&quot;%s&quot;,str);\r\nreturn0;\r\n}\r\n','cpp','mc'),
(4,1,4,'find the error','#include&lt;stdio.h&gt;\r\nint main()\r\n{\r\nint P = 10;\r\nswitch(P)\r\n    {\r\ncase10:\r\nprintf(&quot;Case 1&quot;);\r\n\r\ncase20:\r\nprintf(&quot;Case 2&quot;);\r\nbreak;\r\n\r\ncase P:\r\nprintf(&quot;Case 2&quot;);\r\nbreak;\r\n    }\r\nreturn0;\r\n}\r\n\r\n','cpp','mc'),
(5,1,5,'find the correct valid function call...assuming the function exists','','','mc'),
(6,1,6,'find the output...','int main()\n{\nint x;\nfor(x=-1; x&lt;=10; x++)\n    {\nif(x &lt; 5)\ncontinue;\nelse\nbreak;\nprintf(&quot;techfest&quot;);\n    }\n','cpp','mc'),
(7,1,7,'find the error','#include&lt;stdio.h&gt;\nint main(){\nconst int k=7;\nint *const q=&amp;k;\nprintf(&quot;%d&quot;, *q);\nreturn0;\n}','cpp','mc'),
(8,1,8,'What happens when a class with parameterized constructors and having no default constructor is used in a program and we create an object that needs a zero-argument constructor?','','','mc'),
(9,1,9,'find the output...','	#include &lt;stdio.h&gt;\r\n#define a 10\r\nmain()\r\n{\r\n#define a 50\r\nprintf(&quot;%d&quot;,a);\r\n}\r\n','cpp','mc'),
(10,1,10,'find the last value of x','int x;\r\nfor(x=0;x&lt;10;x++)\r\n	{}','cpp','mc'),
(11,1,11,'find the error','#include&lt;stdio.h&gt;\r\n\r\n int main()\r\n{\r\nint a[] = {10, 20, 30, 40, 50};\r\nint j;\r\nfor(j=0; j&lt;5; j++)\r\n    {\r\nprintf(&quot;%d\\n&quot;, a);\r\n        a++;\r\n    }\r\nreturn 0;\r\n}\r\n','cpp','mc'),
(12,1,12,'which is not a protection level provided by classes in c++','','','mc'),
(13,1,13,'In a call to printf() function the format specifier %b can be used to print binary equivalent of an integer.','','','tf'),
(14,1,14,'tick the correct','void main ( )\r\n\r\n{\r\n\r\n  char *P = &quot;ayqm&quot; ;\r\n\r\n  char c;\r\n\r\n  c = ++*p ;\r\n\r\n  printf (&quot;%c&quot;, c);\r\n\r\n}\r\n\r\n','cpp','mc'),
(15,1,15,'which of the following algo requires sorted array?','','','mc'),
(16,1,16,'the statement prints','printf(&quot;%c&quot;,100);','cpp','mc'),
(17,1,17,'all srings end up with a null zero....','','','tf'),
(18,1,18,'character variable may contain up to seven literals...','','','tf'),
(19,1,19,'find the error','main(){\nextern int i;\ni=20;\nprintf(&quot;%d&quot;,i);\n}','cpp','mc'),
(20,2,20,'find the error','	main()\r\n{\r\nchar string[]=&quot;Hello World&quot;;\r\n	display(string);\r\n}\r\nvoid display(char *string)\r\n{\r\n	printf(&quot;%s&quot;,string);\r\n}\r\n','cpp','mc'),
(21,2,21,'find the error and the  output...','#include&lt;stdio.h&gt;\r\nvoid main() \r\n{ \r\nint a=320; \r\nchar *ptr; \r\nptr=(char *)&amp;a; \r\nprintf(&quot;%d&quot;,*ptr); \r\ngetch();\r\n}\r\n','cpp','mc'),
(22,1,22,'find the output','void main()\r\n\r\n{\r\n\r\n  int a = 1, b=2, c=3;\r\n\r\n  char d = 0;\r\n\r\n  if(a,b,c,d)\r\n\r\n  {\r\n\r\n    printf(&quot;EXAM&quot;);\r\n\r\n  }\r\n','cpp','mc'),
(23,2,23,'find the  error','#include&lt;stdio.h&gt;\nint main(){\nchar ch;\nint i;\nscanf(&quot;%c&quot;, &amp;i);\nscanf(&quot;%d&quot;, &amp;ch);\nprintf(&quot;%c %d&quot;, ch, i);\nreturn0;\n}','cpp','mc'),
(25,2,25,'find the error','#include&lt;stdio.h&gt;\r\nchar *str = &quot;char *str = %c%s%c; main(){ \r\nprintf(str, 34, str, 34);}&quot;;\r\n\r\nint main()\r\n{\r\nprintf(str, 34, str, 34);\r\nreturn 0;\r\n}\r\n','cpp','mc'),
(26,2,26,'find the output','enum colors {BLACK,BLUE,GREEN}\r\nmain()\r\n{\r\n\r\nprintf(&quot;%d..%d..%d&quot;,BLACK,BLUE,GREEN);\r\n\r\nreturn(1);\r\n}\r\n','cpp','mc'),
(27,2,27,'find the error...','typedefstruct\r\n{\r\nint data;\r\n    NODEPTR link;\r\n}*NODEPTR;\r\n','cpp','mc'),
(29,2,29,'find the error','#include&lt;stdio.h&gt;\r\nint main()\r\n{\r\nvoid fun();\r\ninti = 1;\r\nwhile(i&lt;= 5)\r\n    {\r\nprintf(&quot;%d\\n&quot;, i);\r\nif(i&gt;2)\r\ngoto here;\r\n    }\r\nreturn0;\r\n}\r\nvoid fun()\r\n{\r\n    here:\r\nprintf(&quot;It works&quot;);\r\n}\r\n','cpp','mc'),
(30,1,30,'Which of the following statements is correct? \r\n1.	Once a reference variable has been defined to refer to a particular variable it can refer to any other variable. \r\n2.	A reference is not a constant pointer. \r\n\r\n\r\n','','','mc'),
(31,2,31,'find the error..','4.	#include&lt;stdio.h&gt;\r\nmain()\r\n{\r\nstruct xx\r\n{\r\nint x=3;\r\nchar name[]=&quot;hello&quot;;\r\n };\r\nstruct xx *s;\r\nprintf(&quot;%d&quot;,s-&gt;x);\r\nprintf(&quot;%s&quot;,s-&gt;name);\r\n}\r\n','cpp','mc'),
(32,2,32,'find the error..','main() \r\n{ \r\ninti; \r\nclrscr(); \r\nprintf(&quot;%d&quot;, &amp;i)+1; \r\nscanf(&quot;%d&quot;, i)-1; \r\n} \r\n','cpp','mc'),
(33,1,33,'hahaha','','','tf');
/*!40000 ALTER TABLE `questions_entitybak` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quiz_takers`
--

DROP TABLE IF EXISTS `quiz_takers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quiz_takers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `marks` int(11) NOT NULL DEFAULT 0,
  `percentage` varchar(24) NOT NULL,
  `date_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `duration` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_takers_user_quiz` (`username`,`quiz_id`),
  KEY `idx_takers_quiz_id` (`quiz_id`),
  CONSTRAINT `fk_takers_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_takers`
--
-- ORDER BY:  `id`

LOCK TABLES `quiz_takers` WRITE;
/*!40000 ALTER TABLE `quiz_takers` DISABLE KEYS */;
INSERT INTO `quiz_takers` VALUES
(3,'1245',2,0,'0','2014-03-06 09:53:41',5),
(4,'456789',2,1,'11.111111111111','2014-03-06 09:54:35',8),
(5,'1139095',2,1,'11.111111111111','2014-03-06 10:20:01',638),
(6,'45698777',2,0,'0','2014-03-06 10:26:18',298),
(7,'1139113',1,0,'0','2014-03-06 10:27:14',3079612),
(8,'yoyo',1,4,'20','2014-03-09 18:12:55',61),
(11,'yoyo',2,0,'0','2014-03-09 18:17:18',0),
(14,'877656443',2,0,'0','2014-03-09 18:35:07',49),
(15,'0987',2,0,'0','2014-03-09 18:36:23',0),
(16,'1qaz',2,0,'0','2014-03-09 18:39:33',0),
(17,'1qaz22',2,0,'0','2014-03-09 18:40:25',0),
(18,'11qaz22',2,0,'0','2014-03-09 18:40:49',190),
(19,'123321',2,0,'0','2014-03-09 20:33:35',1472);
/*!40000 ALTER TABLE `quiz_takers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quizes`
--

DROP TABLE IF EXISTS `quizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quizes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL DEFAULT 0,
  `quiz_name` varchar(50) NOT NULL,
  `total_questions` int(11) NOT NULL DEFAULT 0,
  `display_questions` int(11) NOT NULL,
  `time_allotted` int(11) NOT NULL,
  `set_default` int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quizes`
--
-- ORDER BY:  `id`

LOCK TABLES `quizes` WRITE;
/*!40000 ALTER TABLE `quizes` DISABLE KEYS */;
INSERT INTO `quizes` VALUES
(1,1,'LEVEL1(EASY)',22,20,30,0),
(2,2,'LEVEL2(HARD)',9,10,20,1);
/*!40000 ALTER TABLE `quizes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quizes_entitybak`
--

DROP TABLE IF EXISTS `quizes_entitybak`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quizes_entitybak` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(11) NOT NULL DEFAULT 0,
  `quiz_name` varchar(50) NOT NULL,
  `total_questions` int(11) NOT NULL DEFAULT 0,
  `display_questions` int(11) NOT NULL,
  `time_allotted` int(11) NOT NULL,
  `set_default` int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quizes_entitybak`
--
-- ORDER BY:  `id`

LOCK TABLES `quizes_entitybak` WRITE;
/*!40000 ALTER TABLE `quizes_entitybak` DISABLE KEYS */;
INSERT INTO `quizes_entitybak` VALUES
(1,1,'LEVEL1(EASY)',22,20,30,0),
(2,2,'LEVEL2(HARD)',9,10,20,1);
/*!40000 ALTER TABLE `quizes_entitybak` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schema_migrations`
--

DROP TABLE IF EXISTS `schema_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `schema_migrations` (
  `name` varchar(64) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schema_migrations`
--
-- ORDER BY:  `name`

LOCK TABLES `schema_migrations` WRITE;
/*!40000 ALTER TABLE `schema_migrations` DISABLE KEYS */;
INSERT INTO `schema_migrations` VALUES
('001_charset.sql','2026-08-23 11:44:21'),
('002_constraints.sql','2026-08-23 11:44:21'),
('003_indexes.sql','2026-08-23 11:44:21'),
('004_fk.sql','2026-08-23 11:44:22'),
('005a_defaults_strict.sql','2026-08-23 11:44:22'),
('005b_decode_entities.sql','2026-08-23 11:44:22'),
('007_audit_log.sql','2026-08-23 11:44:23');
/*!40000 ALTER TABLE `schema_migrations` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
