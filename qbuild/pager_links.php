<?php
/**
* @package qbuild
*
* Modify the variables in this document to cutomize the links
* - $append is the method argument to add to the end of the link
* - $get_var is the variable that is passed in the URL to change the page. This is set in the qbuilder.php file
* The rest is self explanatory.
*/

$first_link = ' <a href="?'.$get_var.'=1'.$append.'" title="First page">« First</a> ';
$last_link  = ' <a href="?'.$get_var.'='.$total_pages.$append.'" title="Last page">Last »</a> ';
$next_link  = ' <a href="?'.$get_var.'='.$next_page.$append.'" title="Next page">»</a> ';
$back_link  = ' <a href="?'.$get_var.'='.$last_page.$append.'" title="Previous page">«</a> ';

?>
