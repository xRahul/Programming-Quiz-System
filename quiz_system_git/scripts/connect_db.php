<?php 
    /*
    PHP, MySQL, Javascript Quiz Interface
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
    	
    	PHP, MySQL, Javascript Timed Quiz  Copyright (C) 2012  Isaac Price
        This program comes with ABSOLUTELY NO WARRANTY.
        This is free software, and you are welcome to redistribute it
        under certain conditions found in the GNU GPL license
    */


    $db_host = "localhost";
// Place the username for the MySQL database here
    $db_username = "root"; 
// Place the password for the MySQL database here
    $db_password = ""; 
// Place the name for the MySQL database here
    $db_name = "debug";

// Run the connection here 
    mysql_connect("$db_host","$db_username","$db_password") or die (mysql_error());
    mysql_select_db("$db_name") or die ("no database named "+$db_name+" exists!");

?>