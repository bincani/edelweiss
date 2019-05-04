<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
  <title>Member Phone List</title>
  <meta name="GENERATOR" content="Quanta Plus">
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>
<h1>Member Phone List</h1>
<?
include("../../../.mysql.php");
mysql_connect($servername, $username, $password);
@mysql_select_db($databasename) or die("Unable to select database");

$aquery = 'SELECT DISTINCT m.member_id, m.first_name, m.last_name, m.dependant_of
		FROM edelweiss_addresses AS a, edelweiss_members AS m

		WHERE a.address_id = m.address_id AND m.dependant_of IS NULL
		ORDER BY m.last_name, m.first_name';

$aresult = mysql_query($aquery);
$arow_count = mysql_numrows($aresult);
?>

<table border=1>
  <tbody align="left" valign="top">
  
<?  for ($i = 0; $i < $arow_count; $i++) { ?>
    <tr>
      <td valign="top">
	<table border=0><COLGROUP><COL width='250'><COL width='250'><COL width='250' align=center>
	<tbody align="left" valign="top">
	<tr valign="top">
	<td valign="top">
		<? echo '<b>' . mysql_result($aresult, $i, "first_name") ." ". 
			mysql_result($aresult, $i, "last_name") . "</b>"; ?>
	</td>
	<td valign="top" align="right">
	<? /* Print all phone numbers for this member */
		$presult = mysql_query('SELECT CONCAT_WS("", p.phone_type, " (", p.area_code, ") ", p.number)
			FROM edelweiss_phone AS p
			WHERE p.member_id = ' . mysql_result($aresult, $i, "member_id"));
		$prow_count = mysql_numrows($presult);
		for ($k = 0; $k < $prow_count; $k++) {
			echo mysql_result($presult, $k) . '<br>';
		}
	?>
	</td>
	</tr>
	<? /* Print all members who live under this person */
		$squery = 'SELECT DISTINCT m.member_id, m.first_name, m.last_name
				FROM edelweiss_members AS m, edelweiss_email AS e
				WHERE m.dependant_of = ' . mysql_result($aresult, $i, "member_id");
		$sresult = mysql_query($squery);
		$srow_count = mysql_numrows($sresult);
		for ($j = 0; $j < $srow_count; $j++) {	
	?>
	<tr valign="top">
	<td valign="top">&nbsp;&nbsp;
		<? echo mysql_result($sresult, $j, "first_name") ." ". 
			mysql_result($sresult, $j, "last_name") ; ?>
	</td>
	<td valign="top" align="right">
	<? /* Print all phone numbers for this member */
		$presult = mysql_query('SELECT CONCAT_WS("", p.phone_type, " (", p.area_code, ") ", p.number)
			FROM edelweiss_phone AS p
			WHERE p.member_id = ' . mysql_result($sresult, $j, "member_id"));
		$prow_count = mysql_numrows($presult);
		for ($k = 0; $k < $prow_count; $k++) {
			echo mysql_result($presult, $k) . '<br>';
		}
	?>
	</td>
	</tr>
	<? } ?>
	</tbody>
	</table>    
      </td>
    </tr>
<? } ?>
  </tbody>
</table>


</body>
</html>
