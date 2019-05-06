<html>
<head><title>Booking Form</title></head>
<body>

<?php
// Set up the database connection and get a list of the members
require_once( 'edelweiss.php' );

function AddBooking()
{
        global $conn;
	// Extract the data submitted from the form
	$member_id = $_REQUEST['member_id'];
	$date_day = $_REQUEST['date_day'];
	$date_month = $_REQUEST['date_month'];
	$date_year = $_REQUEST['date_year'];
	$length = $_REQUEST['length'];
	$num_members  = $_REQUEST['num_members'];
	$num_juniors = $_REQUEST['num_juniors'];
	$num_guests = $_REQUEST['num_guests'];
	$num_child_guests = $_REQUEST['num_child_guests'];
	$start_date = date("Y-m-d", mktime(0,0,0,$date_month,$date_day,$date_year));

	// Validate user entered values
	if ($member_id == "0") die ("The Member Name was not selected !");
	if ($num_juniors == "") $num_juniors = 0;
	if ($num_guests == "") $num_guests = 0;
	if ($num_child_guests == "") $num_child_guests = 0;
	if ((!preg_match("/^[0-9]+$/", $length)) or ($length < 0) or ($length > 30)) die ("Length ($length) of stay must be less than 30 days");
	if ((!preg_match("/^[0-9]+$/", $num_members)) or ($num_members < 0) or ($num_members > 14)) die ("Number of Members ($num_members) must be less than 14");
	if ((!preg_match("/^[0-9]+$/", $num_juniors)) or ($num_juniors < 0) or ($num_juniors > 13)) die ("Number of Juniors ($num_juniors) must be less than 13");
	if ((!preg_match("/^[0-9]+$/", $num_guests)) or ($num_guests < 0) or ($num_guests > 13)) die ("Number of Adult Guests ($num_guests) must be less than 13");
	if ((!preg_match("/^[0-9]+$/", $num_child_guests)) or ($num_child_guests < 0) or ($num_child_guests > 13)) die ("Number of Child Guests ($num_child_guests) must be less than 13");
	
	// Check the submitted values
	$query = "SELECT DISTINCT m.member_id, m.first_name, m.last_name
				FROM edelweiss_members AS m
				WHERE m.member_id = $member_id";
	$result = mysqli_query($conn, $query);
	if (! $result ) die ("<hr>Database Error: <br><pre>". mysqli_error() ."</pre><hr>");
	$query_data = mysqli_fetch_array($result);
	$m_id = $query_data['member_id'];
	$m_first = $query_data['first_name'];
	$m_last = $query_data['last_name'];

	$query = "SELECT DISTINCT e.address
				FROM edelweiss_email AS e
				WHERE e.member_id = $m_id";
	$result = mysqli_query($conn, $query);
	$m_email = "";
	if (! $result )
	{
		echo "<hr>Email address missing<hr>";
	}
	else
	{
		$query_data = mysqli_fetch_array($result);
		$m_email = $query_data['address'];
	}
	
	// Check cabin availability & Calculate the price
	$total_cost = 0;
	for ($i = 0; $i < $length; $i++)
	{
		$query = "SELECT DATE_ADD('$start_date', INTERVAL $i DAY) AS date";
		$result = mysqli_query($conn, $query);
		if (! $result ) die ("<hr>Database Error: <br><pre>". mysqli_error() ."</pre><hr>");
		$query_data = mysqli_fetch_array($result);
		$date = $query_data['date'];
		
		$cost = getCost($date, $num_members, $num_juniors, $num_guests, $num_child_guests);
		if ($cost <= 0) die ("<hr>Cabin Cost Calculation Error: <br><pre>". mysqli_error() ."</pre><hr>");
		$total_cost = $total_cost + $cost;
		
		$query = "SELECT SUM(members) + SUM(juniors) + SUM(adult_guests) + SUM(child_guests) AS sum
					FROM edelweiss_days AS d, edelweiss_booking AS b
					WHERE d.date = '$date'
					AND d.booking_id = b.booking_id
					AND b.status !='Lapsed' AND b.status !='Cancelled'";
		$result = mysqli_query($conn, $query);
		if (! $result ) die ("<hr>Database Error: <br><pre>". mysqli_error() ."</pre><hr>");
		$query_data = mysqli_fetch_array($result);
		$sum = $query_data['sum'];
		
		if ($sum + $num_members + $num_juniors + $num_guests + $num_child_guests > 14)
		{
			echo "Day $date has $sum beds already booked<p>";
			echo "Please go back and try again<p>";
			die ("Unable to process booking");
		}

		// All winter bookings less than 7 nights must be made after May 1st
		if (! isSummer($date) && ($length < 7) )
		{
			$query = "select date from edelweiss_days WHERE CURDATE() < CONCAT( YEAR(CURDATE()),'-05-01') limit 1";
			$result = mysqli_query($conn, $query);
			if (! $result ) die ("<hr>Database Error: <br><pre>". mysqli_error() ."</pre><hr>");
			if ( mysqli_num_rows($result) != 0 ) die ("All winter bookings less than 7 nights must be made after May 1st");
		}

		// All winter bookings must be made after Jan 1st
		if (! isSummer($date) )
		{
			$query = "select date from edelweiss_days WHERE CURDATE() < CONCAT( YEAR(CURDATE()),'-01-01') limit 1";
			$result = mysqli_query($conn, $query);
			if (! $result ) die ("<hr>Database Error: <br><pre>". mysqli_error() ."</pre><hr>");
			if ( mysqli_num_rows($result) != 0 ) die ("All winter bookings must be made after Jan 1st");
		}
	}
	
	// Insert into Database
	$insert = "INSERT INTO edelweiss_booking (booking_id , member_id, booking_date, status) 
			VALUES ( '', '$m_id', CURDATE() , 'Unconfirmed' )";
	$result = mysqli_query($conn, $insert);
	if (isset($debug)) echo "<pre>" . $insert ."</pre>";
	if (! $result ) die( "Failed to add booking to the Database");		
	$booking_id = mysqli_insert_id($conn);
	for ($i = 0; $i < $length; $i++)
	{
		$insert = "INSERT INTO edelweiss_days ( day_id, date , booking_id, members, juniors , adult_guests, child_guests ) 
				VALUES ( '', DATE_ADD('$start_date', INTERVAL $i DAY), '$booking_id','$num_members', '$num_juniors', '$num_guests', '$num_child_guests')";
		$result = mysqli_query($conn, $insert);
		if (isset($debug)) echo "<pre>" . $insert ."</pre>";
		if (! $result )
		{
			echo mysqli_error();
			die( "Failed to add day ".$i. "  to the Database");
		}
	}

	$query = "SELECT DATE_ADD(CURDATE(), INTERVAL 14 DAY) AS deposit_date, DATE_SUB('$start_date', INTERVAL 30 DAY) AS remainder_date";
	$result = mysqli_query($conn, $query);
	if (! $result ) die ("<hr>Database Error: <br><pre>". mysqli_error() ."</pre><hr>");
	$query_data = mysqli_fetch_array($result);
	$deposit_date = $query_data['deposit_date'];	
	$remainder_date = $query_data['remainder_date'];	
	
	$total_booked = $num_members + $num_juniors + $num_guests + $num_child_guests;

	// Display the submitted values
	echo "<h1>Edelweiss Booking Request</h1>";
	
	$mail_body  = "Booking Number $booking_id\n\n";
	$mail_body .= "$m_first $m_last (Member ID $m_id)\n";
	$mail_body .= "Booking for $start_date\n";
	$mail_body .= "$length nights\n\n";
	$mail_body .= "Members = $num_members \nJuniors = $num_juniors\n";
	$mail_body .= "Guests = $num_guests \nChild Guests = $num_child_guests\n";
	$mail_body .= "Total Beds = $total_booked\n";
	$mail_body .= sprintf("\nTotal Cost : \$%01.2f\n", $total_cost);
	$mail_body .= sprintf("A deposit of \$%01.2f is due by %s\n", $total_cost * 0.30, $deposit_date);
	$mail_body .= sprintf("The booking must be paid in full by %s (remainder is \$%01.2f)\n", $remainder_date, $total_cost * 0.70);

	$mail_header  = "From: \"Edelweiss Bookings\"  <bookings@edelweiss-ski.club>\r\n";
	if ($m_email != "") $mail_header  .= "Cc: \"$m_first $m_last\"  <$m_email>\r\n";
	$mail_header .= "Reply-to: bookings@edelweiss-ski.club\r\n";
	$mail_header .= "Return-path: bookings@edelweiss-ski.club\r\n";

	echo "<hr width='50%' align='left'><pre>$mail_body</pre>";
	;
	if ( mail("bookings@edelweiss-ski.club", "Booking for $m_first $m_last ($m_id)",$mail_body, $mail_header))
	{
		echo "<hr>An email has been sent to bookings@edelweiss-ski.club ";
		if ($m_email != "") echo "and $m_email ";
		echo "with the above infomation.<p>";
	}
	else
	{
		echo "<hr>Unfortunatly sending email to bookings@edelweiss-ski.club or $m_email failed unexpectedly. The booking has still been accepted.<p>";
	}
}

function ShowForm()
{
        global $conn;
	// Display the Form
	$query = "SELECT DISTINCT m.member_id, m.first_name, m.last_name
				FROM edelweiss_members AS m
				WHERE m.membership_type != 'Junior' AND m.membership_type != 'Spouse' AND m.membership_type != 'Resigned'
				ORDER BY m.last_name";

	$result = mysqli_query($conn, $query);
	$row_count = mysqli_num_rows($result);
	$length = "";
	$num_members = "";
	$num_juniors = "";
	$num_guests = "";
	$num_child_guests = "";
?>

<h2>Booking Form</h2>

<form action="booking_form.php" method="post" >
  <input type="hidden" name="action" value="AddBooking">
  <table width="80%" border=0>
    <tr>
		<td>Member Name:</td>
		<td><select name="member_id">
                    <<option value="0">Name of Member</option>"
			<? for ($i = 0; $i < $row_count; $i++)
				{
					$m_id = mysqli_result($result, $i, "member_id");
					$m_first = mysqli_result($result, $i, "first_name");
					$m_last = mysqli_result($result, $i, "last_name");

					echo "<option value=\"$m_id\">$m_first $m_last</option>";
				}
			?>
			</select>
		</td>
	</tr>
    <tr>
		<td>Booking Start Date:</td>
		<td><select name="date_day">
			<? for ($i = 1; $i <= 31; $i++)
				echo "<option value=\"$i\">$i</option>";
			?> </select>
			<select name="date_month">
			<? for ($i = 1; $i <= 12; $i++)
				echo "<option value=\"$i\">$i</option>";
			?> </select>
			<select name="date_year">
			<option value="2019">2019</option>

			</select>
			</td>
	</tr>
    <tr>
		<td>Number of Nights:</td>
		<td><input type="text" name="length" maxlength="2" size="2" value="<?php echo "$length" ?>" /></td>
	</tr>
    <tr><td>&nbsp;</td></tr>
    <tr>
		<td>Number of Members :</td>
		<td><input type="text" name="num_members" maxlength="2" size="2" value="<?php echo "$num_members" ?>" /> ($20)</td>
	</tr>
    <tr>
		<td>Number of Junior Members :</td>
		<td><input type="text" name="num_juniors" maxlength="2" size="2" value="<?php echo "$num_juniors" ?>" /> ($10)</td>
	</tr>
    <tr>
		<td>Number of Guests :</td>
		<td><input type="text" name="num_guests" maxlength="2" size="2" value="<?php echo "$num_guests" ?>" /> ($50)</td>
	</tr>
    <tr>
		<td>Number of Child Guests :</td>
		<td><input type="text" name="num_child_guests" maxlength="2" size="2" value="<?php echo "$num_child_guests" ?>" /> ($20)</td>
	</tr>
    <tr>
		<td align="center"><i>(To get the Summer Capped rate, please book all 14 beds)</i></td>
	</tr>
    <tr>
		<td align="center">&nbsp;</td>
                <!-- was disabled? date dependant -->
		<td><input type="submit" name="simple" value="Make Booking" /></td>
<!--		<td><input type="submit" name="advanced" value="Advanced Options"/></td>
-->
	</tr>
  </table>
</form>

<?php
} // ShowForm

if (isset($_POST['advanced']))
{
//	AdvancedForm();
}
else if (isset($_POST['simple']))
{
	AddBooking();
}
else
{
	ShowForm();
}

?>

</body>
</html>

