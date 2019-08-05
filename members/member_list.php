<?php
$emails = array();
?>
<html>
<head><title>Edelweiss Membership</title></head>
<body>
<h1>Edelweiss Membership</h1>

<?
include("../.mysql.php");
$conn = mysqli_connect($servername, $username, $password);
@mysqli_select_db($conn, $databasename) or die("Unable to select database");

/*$aquery = 'SELECT `member_id`, `first_name` , `last_name` , `membership_type` , `dependant_of` , `street` , `city`
	, `state` , `postcode` , `email` '
	.' FROM `edelweiss_member` '
	.' WHERE 1 AND `dependant_of` IS NULL'
	.' ORDER BY last_name';
*/

$aquery = 'SELECT DISTINCT m.member_id, m.club_title, m.first_name, m.last_name, m.membership_type, m.dependant_of, 
		a.street, a.city, a.state, a.postcode, e.address
		FROM edelweiss_addresses AS a, edelweiss_members AS m, edelweiss_email AS e
		RIGHT OUTER JOIN edelweiss_email ON m.member_id = e.member_id
		WHERE a.address_id = m.address_id AND m.dependant_of IS NULL
		ORDER BY m.last_name, m.first_name';


$aquery = 'SELECT DISTINCT
 m.member_id, m.club_title, m.first_name, m.last_name, m.membership_type, m.dependant_of, a.street, a.city, a.state, a.postcode, e.address
FROM edelweiss_members m
left join edelweiss_addresses a on m.address_id = a.address_id
left join edelweiss_email e on m.member_id = e.member_id
WHERE
m.dependant_of IS NULL
AND
membership_type != "Resigned"
ORDER BY m.last_name, m.first_name
';

$aresult = mysqli_query($conn, $aquery);
$arow_count = mysqli_num_rows($aresult);
?>
<style>
table {
  border-collapse: collapse;
  border: 1px solid #000000;
}
table td {
  padding: 5px;
}
table thead tr th {
  background: #eeeeee;
  padding: 5px 0 5px 10px;
  text-align: left;
  border: 1px solid #000000;
}
.row td{
    border-top: 1px solid #000000;
}
</style>
<table padding="0" spacing="0">
  <thead>
    <tr>
      <th>Title</th>
      <th>Name</th>
      <th>Contact</th>
      <th>Email</th>
      <th>Address</th>
    </tr>
  </thead> 
  <tbody align="left" valign="top">
  <?php for ($i = 0; $i < $arow_count; $i++) { ?>
    <tr valign="top" class="row">
        <td><?php echo mysqli_result($aresult, $i, "club_title") ?></td>
	<td valign="top">
		<? echo '<b>' . mysqli_result($aresult, $i, "first_name") ." ". 
			mysqli_result($aresult, $i, "last_name") . "</b> (".
			mysqli_result($aresult, $i, "membership_type") .")"; ?>
	</td>
	<td valign="top" align="left">
	<? /* Print all phone numbers for this member */
		$presult = mysqli_query($conn, 'SELECT CONCAT_WS("", p.phone_type, " (", p.area_code, ") ", p.number)
			FROM edelweiss_phone AS p
			WHERE p.member_id = ' . mysqli_result($aresult, $i, "member_id"));
		$prow_count = mysqli_num_rows($presult);
		for ($k = 0; $k < $prow_count; $k++) {
			echo mysqli_result($presult, $k) . '<br>';
		}
	?>
	</td>
	<td valign="top" align="left">
        <?php
        $email = mysqli_result($aresult, $i, "address");
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $email;
        }
	echo "<a href=\"mailto:" . mysqli_result($aresult, $i, "address") . "\">"
				.mysqli_result($aresult, $i, "address") . "</a>"; ?></td>

        <? /* Print all members who live under this person */
                $squery = 'SELECT DISTINCT m.member_id, m.first_name, m.last_name, m.membership_type, e.address
                                FROM edelweiss_members AS m, edelweiss_email AS e
                                RIGHT OUTER JOIN edelweiss_email ON m.member_id = e.member_id
                                WHERE m.dependant_of = ' . mysqli_result($aresult, $i, "member_id");

                $squery = 'SELECT DISTINCT m.member_id, m.club_title, m.first_name, m.last_name, m.membership_type, e.address
                                FROM edelweiss_members AS m
                                LEFT JOIN edelweiss_email e ON m.member_id = e.member_id WHERE m.dependant_of = ' . mysqli_result($aresult, $i, "member_id");

                //echo $squery;
                $sresult = mysqli_query($conn, $squery);
                $srow_count = mysqli_num_rows($sresult);

?>
          <td valign="top" rowspan="<?php echo ($srow_count + 1); ?>">
                <? echo mysqli_result($aresult, $i, "street") . "<br>"
                        . mysqli_result($aresult, $i, "city") .","
                        . mysqli_result($aresult, $i, "state") ." "
                        . mysqli_result($aresult, $i, "postcode"); ?>
          </td>

	</tr>
       <?php
		for ($j = 0; $j < $srow_count; $j++) {	
	?>
	<tr valign="top">
          <td><?php echo mysqli_result($sresult, $j, "club_title") ?></td>
	  <td valign="top">&nbsp;&nbsp;
		<? echo mysqli_result($sresult, $j, "first_name") ." ". 
			mysqli_result($sresult, $j, "last_name") . " (".
			mysqli_result($sresult, $j, "membership_type") .")"; ?>
	  </td>
	  <td valign="top" align="left">
	  <? /* Print all phone numbers for this member */
		$presult = mysqli_query($conn, 'SELECT CONCAT_WS("", p.phone_type, " (", p.area_code, ") ", p.number)
			FROM edelweiss_phone AS p
			WHERE p.member_id = ' . mysqli_result($sresult, $j, "member_id"));
		$prow_count = mysqli_num_rows($presult);
		for ($k = 0; $k < $prow_count; $k++) {
			echo mysqli_result($presult, $k) . '<br>';
		}
	  ?>
	  </td>
	  <td valign="top" align="left">
	  <?php
          $email = mysqli_result($sresult, $j, "address");
          if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
              $emails[] = $email;
          }
          echo "<a href=\"mailto:" . mysqli_result($sresult, $j, "address") . "\">"
				.mysqli_result($sresult, $j, "address") . "</a>"; ?></td>
	  <? } ?>
			
      </tr>
  <? } // end foreach ?>
  </tbody>
</table>
<!--
<a href="mailto:<?php // echo implode(",", $emails); ?>">email all members</a>
-->
<a target="_blank" rel="noopener noreferrer" href="mailto:members@edelweiss-ski.club">email all members</a>

<!--
$i = 0;
while ($i < $arow_count)
{
  echo "<tr><td valign=top>\n";
  $address_id = mysqli_result($aresult, $i, "address_id");
  $street = mysqli_result($aresult, $i, "street");
  $city = mysqli_result($aresult, $i, "city");
  $state = mysqli_result($aresult, $i, "state");
  $postcode = mysqli_result($aresult, $i, "postcode");

  echo "<table border=0><COLGROUP><COL width='250'><COL width='200'><COL width='300' align=center>\n";
  $query = "SELECT m.first_name, m.last_name, m.membership_type, m.member_id FROM edelweiss_members AS m WHERE m.address_id = $address_id";
  $result = mysqli_query($conn, $query);
  $row_count = mysqli_num_rows($result);
  $j = 0;
  while ($j < $row_count)
  {
    $first = mysqli_result($result, $j, "first_name");
    $last = mysqli_result($result, $j, "last_name");
    $type = mysqli_result($result, $j, "membership_type");
    $mem_id = mysqli_result($result, $j, "member_id");

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
    $presult = mysqli_query($conn, $query);
    $phone_count = mysqli_num_rows($presult);
    $k = 0;
    while ($k < $phone_count)
    {
        $number = mysqli_result($presult, $k, "number");
        $area = mysqli_result($presult, $k, "area_code");
        $phone_type = mysqli_result($presult, $k, "phone_type");
	echo "$phone_type: ($area) $number<br>";
	++$k;
    }

    echo "</td>\n<td valign=top>\n";
    $query = "SELECT e.address FROM edelweiss_email AS e WHERE $mem_id = e.member_id";
    $presult = mysqli_query($conn, $query);
    $email_count = mysqli_num_rows($presult);
    $k = 0;
    while ($k < $email_count)
    {
        $email = mysqli_result($presult, $k, "address");
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
