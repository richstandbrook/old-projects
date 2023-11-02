<?php
/**
* Database abstraction functions.
* Update MySQL functions to reflect those of your chosen database
*
* @copyright © 2004, koorb.co.uk
* @author Richard Standbrook <richard@koorb.co.uk>
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
* @package qbuild
* @version 0.1
*/


/**
* This function sets the connection details to a class property
* and connects to the host (using persistance if specified)
*
* @access constructor
* @return boolean true, or false if error
*/
function db_connect($host, $user, $pass, $db, $persist = false) {

    //open link using persistent if set
    @$this->db_link = $persist
    ? mysql_connect ($host, $user, $pass)
    : mysql_pconnect($host, $user, $pass);

    mysql_select_db($db);

    //return any errors
    return db_error() ? db_error() : true;
}


/**
* Database error function
*
* @access public
* @return string
*/
function db_error() {

    return mysql_error();
}


/**
* Database query function
*
* @access public
*/
function db_query($sql) {

    return mysql_query($sql);
}


/**
* Return number of rows in $table
*
* @access public
* @return int
*/
function db_numrows($result) {

    return mysql_numrows($result);
}


/**
* Fetch an associated array of results
*
* @return array
* @access public
*/
function db_array($result) {

    return mysql_fetch_assoc($result);
}

?>
