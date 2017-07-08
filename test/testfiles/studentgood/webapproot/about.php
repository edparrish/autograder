<?php
/**
* CIS-165PH  Asn 6
* about.php
* Purpose: Displays information about my project and
* displays queries from my project database.
*
* @author  Ed Parrish
* @version 1.1 10/09/05
*/
?>
<html>
<head>
<title>Artzy Art Supplies</title>
</head>

<body>
<h1>Artzy Art Supplies</h1>
<p>We are an entirely fictitious e-commerce site.</p>

<p>We serve as an example of for the <font color="green">fine</font> course: <br>
<a href="http://www.edparrish.net/cis165/04s/">CIS-165PH: Introduction to Programming for Database-Driven Web Site Development</a></p>

<h4>Recent Customers</h4>
<?php
require_once "includes/dbconvars.php";

$dbCnx = mysql_connect($dbhost, $dbuser, $dbpwd)
    or die("Could not connect");
mysql_select_db($dbname, $dbCnx)
    or die("Could not select db");

$result = mysql_query("SELECT * FROM customers")
    or die("Invalid query");

print "<p><table border>\n";
print "<tr><td>Last Name</td><td>";
print mysql_result($result, 0, "Lname");
print "</td></tr>\n<td>First Name</td><td>";
print mysql_result($result, 0, "FName");
print "</td></tr>\n<td>Phone</td><td>";
print mysql_result($result, 1, "Phone");
print "</td></tr>\n</table>\n";

mysql_free_result($result);
mysql_close($dbCnx);
?>
</body>
</html>
