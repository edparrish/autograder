<?php
/**
* CIS-165PH  Asn 6
* about.php
* Purpose: Displays information about my project and
* displays queries from my project database.
*
* @author  Ed Parrish
* @version 1.1 10/09/05
* @version 1.2 07/22/17
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

$dbCnx = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname)
    or die("Could not connect to $dbname");

$result = mysqli_query($dbCnx, "SELECT * FROM customers")
    or die("Invalid query");

print "<p><table border>\n";
print "<tr><td>Last Name</td><td>";
mysqli_data_seek($result, 0);
$row = mysqli_fetch_assoc($result);
print $row['LName'];
print "</td></tr>\n<td>First Name</td><td>";
print $row['FName'];
print "</td></tr>\n<td>Phone</td><td>";
mysqli_data_seek($result, 1);
$row = mysqli_fetch_assoc($result);
print $row['Phone'];
print "</td></tr>\n</table>\n";

mysqli_free_result($result);
mysqli_close($dbCnx);
?>
</body>
</html>
