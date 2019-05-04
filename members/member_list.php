<html>
<head><title>Edelweiss Membership</title></head>
<body>
<h1>Edelweiss Membership</h1>

<?
include("../../../.mysql.php");
mysql_connect($servername, $username, $password);
@mysql_select_db($databasename) or die("Unable to select database");

/*$aquery = 'SELECT `member_id`, `first_name` , `last_name` , `membership_type` , `dependant_of` , `street` , `city`
	, `state` , `postcode` , `email` '
	.' FROM `edelweiss_member` '
	.' WHERE 1 AND `dependant_of` IS NULL'
	.' ORDER BY last_name';
*/

$aquery = 'SELECT DISTINCT m.member_id, m.first_name, m.last_name, m.membership_type, m.dependant_of, 
		a.street, a.city, a.state, a.postcode, e.address
		FROM edelweiss_addresses AS a, edelweiss_members AS m, edelweiss_email AS e
		RIGHT OUTER JOIN edelweiss_email ON m.member_id = e.member_id
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
			mysql_result($aresult, $i, "last_name") . "</b> (".
			mysql_result($aresult, $i, "membership_type") .")"; ?>
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
	<td valign="top" align="right">
	<? echo "<a href=\"mailto:" . mysql_result($aresult, $i, "address") . "\">"
				.mysql_result($aresult, $i, "address") . "</a>"; ?></td>
	</tr>
	<? /* Print all members who live under this person */
		$squery = 'SELECT DISTINCT m.member_id, m.first_name, m.last_name, m.membership_type, e.address
				FROM edelweiss_members AS m, edelweiss_email AS e
				RIGHT OUTER JOIN edelweiss_email ON m.member_id = e.member_id
				WHERE m.dependant_of = ' . mysql_result($aresult, $i, "member_id");
		$sresult = mysql_query($squery);
		$srow_count = mysql_numrows($sresult);
		for ($j = 0; $j < $srow_count; $j++) {	
	?>
	<tr valign="top">
	<td valign="top">&nbsp;&nbsp;
		<? echo mysql_result($sresult, $j, "first_name") ." ". 
			mysql_result($sresult, $j, "last_name") . " (".
			mysql_result($sresult, $j, "membership_type") .")"; ?>
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
	<td valign="top" align="right">
	<? echo "<a href=\"mailto:" . mysql_result($sresult, $j, "address") . "\">"
				.mysql_result($sresult, $j, "address") . "</a>"; ?></td>
	</tr>
	<? } ?>
	</tbody>
	</table>    
      </td>
			
      <td valign="top">
		<? echo mysql_result($aresult, $i, "street") . "<br>"
			. mysql_result($aresult, $i, "city") .","
			. mysql_result($aresult, $i, "state") ." "
			. mysql_result($aresult, $i, "postcode"); ?>
	</td>
    </tr>
<? } ?>
  </tbody>
</table>

<!--
$i = 0;
while ($i < $arow_count)
{
  echo "<tr><td valign=top>\n";
  $address_id = mysql_result($aresult, $i, "address_id");
  $street = mysql_result($aresult, $i, "street");
  $city = mysql_result($aresult, $i, "city");
  $state = mysql_result($aresult, $i, "state");
  $postcode = mysql_result($aresult, $i, "postcode");

  echo "<table border=0><COLGROUP><COL width='250'><COL width='200'><COL width='300' align=center>\n";
  $query = "SELECT m.first_name, m.last_name, m.membership_type, m.member_id FROM edelweiss_members AS m WHERE m.address_id = $address_id";
  $result = mysql_query($query);
  $row_count = mysql_numrows($result);
  $j = 0;
  while ($j < $row_count)
  {
    $first = mysql_result($result, $j, "first_name");
    $last = mysql_result($result, $j, "last_name");
    $type = mysql_result($result, $j, "membership_type");
    $mem_id = mysql_result($result, $j, "member_id");

    echo "<tr>\n<td valign=top>\n";
    if ($type == "Junior")
    {
      echo "&nbsp;&nbsp; $first $last ($type)";
    }
    else if ($type == "Spouse")
    {
      echo "&nbsp;&nbsp; $first $last ($type)";
    }
    else
    {
      echo "<b>$first $last</b> ($type)";
    }

    echo "</td>\n<td valign=top>\n";
    $query = "SELECT p.number, p.area_code, p.phone_type FROM edelweiss_phone AS p WHERE $mem_id = p.member_id";
    $presult = mysql_query($query);
    $phone_count = mysql_numrows($presult);
    $k = 0;
    while ($k < $phone_count)
    {
        $number = mysql_result($presult, $k, "number");
        $area = mysql_result($presult, $k, "area_code");
        $phone_type = mysql_result($presult, $k, "phone_type");
	echo "$phone_type: ($area) $number<br>";
	++$k;
    }

    echo "</td>\n<td valign=top>\n";
    $query = "SELECT e.address FROM edelweiss_email AS e WHERE $mem_id = e.member_id";
    $presult = mysql_query($query);
    $email_count = mysql_numrows($presult);
    $k = 0;
    while ($k < $email_count)
    {
        $email = mysql_result($presult, $k, "address");
	echo "<a href=\"mailto:$email\">$email</a><br>";
	++$k;
    }

    echo "</td>\n</tr>\n";

    ++$j;
  }
  echo "</table>\n</td>\n<td valign=top>\n";
  if ($street) echo " $street<br>$city, $state<br>$postcode";
  echo "</td>\n</tr>\n";
  
  ++$i;
}
echo "</table><p>Number of Mailout addresses = $arow_count\n";
-->

</body>
</html>
