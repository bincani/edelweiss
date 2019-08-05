<?php

require_once $_SERVER["DOCUMENT_ROOT"] . '/config/.mysql.php';

//echo sprintf("%s|%s|%s|%s\n", $servername, $username, $password,$databasename);
$conn = mysqli_connect($servername, $username, $password) or die("Cannot connect to database server");
mysqli_select_db($conn, $databasename) or die("Unable to select database");

function getStatusColour($status)
{
	switch ($status)
	{
	case 'Deposit_Paid': $colour = 'Green'; break;
	case 'Fully_Paid': $colour = 'Black'; break;
	case 'Lapsed': $colour = 'LightGrey'; break;
	case 'Cancelled': $colour = 'LightGrey'; break;
	default:
	case 'Unconfirmed': $colour = 'Fuchsia'; break;
	}
	return $colour;
}

function getCost($date, $members, $juniors, $adult_guests, $child_guests)
{
        global $conn;
	$query = "SELECT DISTINCT r.adult, r.junior, r.adult_guest, r.child_guest
	FROM edelweiss_rates AS r
	WHERE r.start <= '$date'
	AND r.finish >= '$date'
	LIMIT 1";
	$result = mysqli_query($conn, $query);
	if (!$result)
	{
		$total = 0;
	}
	else
	{
		$query_data = mysqli_fetch_array($result);
		$p_mem = $query_data['adult'];
		$p_junior = $query_data['junior'];
		$p_guest = $query_data['adult_guest'];
		$p_child = $query_data['child_guest'];
		
		$total = $members * $p_mem + $juniors * $p_junior + $adult_guests * $p_guest + $child_guests * $p_child;
		if (isset($debug)) echo "<hr>Member Price = $p_mem<br>Junior Price = $p_junior<br>Guest Price = $p_guest<br>Child Price = $p_child<br>Total = $total<br>";

		if (isSummer($date) && ($total > 50.00))
			$total = 50.00;
	}
	return $total;
}

function dspBookingSummaryForDay($date)
{
        global $conn;
	$query = "SELECT m.first_name, m.last_name, b.booking_id, b.status,
	members + juniors + adult_guests + child_guests AS total
	FROM edelweiss_days AS d, edelweiss_booking AS b, edelweiss_members AS m
	WHERE date = \"$date\"
	AND d.booking_id = b.booking_id
	AND b.member_id = m.member_id
	LIMIT 20";
	$result = mysqli_query($conn, $query);
	if (! $result ) die ("<hr>Database Error: <br><pre>". mysqli_error() ."</pre><hr>");
	$count = 0;
	while ($query_data = mysqli_fetch_array($result))
	{
		$name = $query_data["first_name"]."&nbsp;".$query_data["last_name"];
		$total = $query_data["total"];
		$id = $query_data["booking_id"];
		$status = $query_data["status"];
		$colour = getStatusColour($status);
##		echo "<a href=\"" . $_SERVER['PHP_SELF'] . "?action=show_booking&id=$id\"><font size=1 color=$colour>$name&nbsp;$total</font></a><br>";
		echo "<a href=\"booking/display_booking.php?booking=$id\"><font size=1 color=$colour>$name&nbsp;$total</font></a><br>";
		$count++;
	}
	
	// add extra empty lines
	while ($count < 3)
	{
		echo "&nbsp;<br>";
		$count++;
	}
}

function dspDayBookings($date)
{
	$query = "SELECT m.first_name, m.last_name, b.booking_id, members, juniors, adult_guests, child_guests,
	members + juniors + adult_guests + child_guests AS total
	FROM edelweiss_days AS d, edelweiss_booking AS b, edelweiss_members AS m
	WHERE date = \"$date\"
	AND d.booking_id = b.booking_id
	AND b.member_id = m.member_id
	LIMIT 20";
	$result = mysqli_query($conn, $query);
	if (! $result ) die ("<hr>Database Error: <br><pre>". mysqli_error() ."</pre><hr>");
	
	echo "<table border=1>";
	echo "<thead><tr><td>Name</td><td>Members</td><td>Juniors</td>".
		"<td>Adult Guests</td><td>Child Guests</td><td>Total</td><td>&nbsp;</td></tr></thead>";
	while ($query_data = mysqli_fetch_array($result))
	{
		$name = $query_data["first_name"]." ".$query_data["last_name"];
		$members = $query_data["members"];
		$juniors = $query_data["juniors"];
		$adult_guests = $query_data["adult_guests"];
		$child_guests  = $query_data["child_guests"];
		
		if ($members == 0) $members = "-";
		if ($juniors  == 0) $juniors = "-";
		if ($adult_guests == 0) $adult_guests = "-";
		if ($child_guests == 0) $child_guests = "-";
		
		echo "<tr><td><b>".$name."</b></td>";
		echo "<td align='right'>" . $members ."</td>";
		echo "<td align='right'>" . $juniors ."</td>";
		echo "<td align='right'>" . $adult_guests ."</td>";
		echo "<td align='right'>" . $child_guests ."</td>";
		echo "<td align='right'><b>" . $query_data["total"] ."</b></td>";
		echo "<td><a href=\"" . $_SERVER['PHP_SELF'] . "?action=show_booking&id=".$query_data["booking_id"]."\">Details</a></td></tr>";
	}
	
}

function dspDayBooking($id)
{
	// Get the details of this booking
	$query = "SELECT m.first_name, m.last_name, b.booking_id, b.status, b.booking_date,
  DATE_ADD(b.booking_date, INTERVAL 14 DAY) AS deposit_due
	FROM edelweiss_days AS d, edelweiss_booking AS b, edelweiss_members AS m
	WHERE b.booking_id = $id
	AND d.booking_id = b.booking_id
	AND b.member_id = m.member_id
	LIMIT 20";
	$result = mysqli_query($conn, $query);
	if (! $result ) die ("<hr>Database Error: <br><pre>". mysqli_error() ."</pre><hr>");
	$query_data = mysqli_fetch_array($result);
	$name = $query_data["first_name"]." ".$query_data["last_name"];
	$booking_date = $query_data["booking_date"];
	$status = $query_data['status'];
	$colour = getStatusColour($status);
  $deposit_due = $query_data['deposit_due'];

	
	echo "<h2>".$name."</h2>";
	echo "Booking Number: ".$id."<p>";
	echo "Booking was made on ".$booking_date."<p>";
	echo "Current Status: <font color='$colour'>$status</font><p>";
	
	// New Query to get each day of this booking
	$query = "SELECT  date, members, juniors, adult_guests, child_guests,
	members + juniors + adult_guests + child_guests AS total
	FROM edelweiss_days AS d, edelweiss_booking AS b
	WHERE b.booking_id = $id
	AND d.booking_id = b.booking_id
	ORDER BY date
	LIMIT 20";
	$result = mysqli_query($conn, $query);
	if (! $result ) die ("<hr>Database Error: <br><pre>". mysqli_error() ."</pre><hr>");
	$total_cost = 0;
	
	echo "<table border=1>";
	echo "<thead><tr><td>Date</td><td>Members</td><td>Juniors</td>".
		"<td>Adult Guests</td><td>Child Guests</td><td>Total Beds</td><td>Cost</td></tr></thead>";
	while ($query_data = mysqli_fetch_array($result))
	{
		$date = $query_data["date"];
		$members = $query_data["members"];
		$juniors = $query_data["juniors"];
		$adult_guests = $query_data["adult_guests"];
		$child_guests  = $query_data["child_guests"];
		
//		$total_cost = $total_cost + $members * 16.50 + $juniors * 8.0 + $adult_guests * 40.0 + $child_guests * 16.50;
		$cost = getCost($date, $members, $juniors, $adult_guests, $child_guests);
		$total_cost = $total_cost + $cost;
		
		if ($members == 0) $members = "-";
		if ($juniors  == 0) $juniors = "-";
		if ($adult_guests == 0) $adult_guests = "-";
		if ($child_guests == 0) $child_guests = "-";
		
		echo "<tr><td>" . $date ."</td>";
		echo "<td align='right'>" . $members ."</td>";
		echo "<td align='right'>" . $juniors ."</td>";
		echo "<td align='right'>" . $adult_guests ."</td>";
		echo "<td align='right'>" . $child_guests ."</td>";
		echo "<td align='right'>" . $query_data["total"] ."</td>";
		echo "<td align='right'>" . sprintf("$%01.2f", $cost) ."</td></tr>";
	}
	printf( "<tr><td colspan=6><b>Total Cost</b></td><td><b>$%01.2f</b></td></tr>", $total_cost);
	echo "</table><p>";
  printf( "<p>A deposit of $%01.2f is due by %s<br>\n", $total_cost * 0.30, $deposit_due);
	
	echo "<h2>Payments</h2>";
	$query = "SELECT date, amount, type, details 
	FROM edelweiss_payments AS p
	WHERE p.booking_id = $id
	LIMIT 20";
	$result = mysqli_query($conn, $query);
	if (! $result ) die ("<hr>Database Error: <br><pre>". mysqli_error() ."</pre><hr>");
	$paid = 0;
	
	echo "<table border=1>";
	echo "<thead><tr><td>Date</td><td>Type</td><td>Detials</td>".
		"<td>Amount</td></tr></thead>";
	while ($query_data = mysqli_fetch_array($result))
	{
		$date = $query_data["date"];
		$amount = $query_data["amount"];
		$type = $query_data["type"];
		$details = $query_data["details"];
		
		$paid = $paid + $amount;
		echo "<tr><td>" . $date ."</td>";
		echo "<td align='center'>" . $type ."</td>";
		echo "<td align='left'>" . $details ."</td>";
		printf( "<td align='right'>$%01.2f</td><tr>", $amount);
	}	
	printf( "<tr><td colspan=3><b>Total Amount Paid</b></td><td align='right'><b>$%01.2f</b></td><tr>", $paid);
	echo "</table><p>";
}

function isSummer($date)
{
        global $conn;
	$query = "SELECT r.name
	FROM edelweiss_rates AS r
	WHERE r.start <= '$date'
	AND r.finish >= '$date'
	AND r.name LIKE 'Summer'
	LIMIT 1";
	$result = mysqli_query($conn, $query);
	if (! $result ) die ("<hr>Database Error: <br><pre>". mysqli_error($conn) ."</pre><hr>");
	$query_data = mysqli_fetch_array($result);
	return (preg_match("/Summer/i", $query_data['name']));
}

function isHighPeak($date)
{
        global $conn;
	$query = "SELECT r.name
	FROM edelweiss_rates AS r
	WHERE r.start <= '$date'
	AND r.finish >= '$date'
	AND r.name LIKE 'Winter'
	LIMIT 1";
	$result = mysqli_query($conn, $query);
	if (! $result ) die ("<hr>Database Error: <br><pre>". mysqli_error($conn) ."</pre><hr>");
	$query_data = mysqli_fetch_array($result);
	return (preg_match("/Winter/i", $query_data['name']));
}

// Display the bookings for an entrire year
function dspBookingsForYear($report_year)
{
  $query = "SELECT b.booking_id,
      CONCAT(m.first_name, \" \", m.last_name) AS member,
      d.date AS start_date,
      b.status,
      COUNT(d.booking_id) AS nights,
      sum(d.members) + sum(d.juniors) + SUM(d.adult_guests) + SUM(d.child_guests) AS total_bed_nights 
    FROM edelweiss_booking AS b, edelweiss_days AS d, edelweiss_members AS m
    WHERE b.member_id = m.member_id
      AND b.booking_id = d.booking_id
      AND d.date > $report_year
      AND d.date < DATE_ADD($report_year, INTERVAL 1 YEAR)
      GROUP BY b.booking_id
      ORDER BY d.date";

  $result = mysqli_query($query);
  $row_count = mysqli_num_rows($result);
  $total_beds_count = 0;
  $total_cancelled_count = 0;
  
  echo "<table border=1>\n";
  echo "<tr bgcolor=lightgray>";
  echo "<td>Booking</td><td>Name</td><td>Start Date</td><td>Status</td><td>Nights</td><td>Bed Nights</td></tr>\n";
        while ($query_data = mysqli_fetch_array($result))
        {
                $id = $query_data["booking_id"];
                $name = $query_data["member"];
                $start_date = $query_data["start_date"];
                $status = $query_data["status"];
                $colour = getStatusColour($status);
    $nights = $query_data["nights"];
    $total_bed_nights = $query_data["total_bed_nights"];

    if (($status == 'Cancelled') || ($status == 'Lapsed'))
    {
      $total_cancelled_count += $total_bed_nights;
    }
    else
    {
      $total_beds_count += $total_bed_nights;
    }

    echo "<tr>";
    echo "<td><a href=\"calendar.php?action=show_booking&id=$id\">$id</a></td>";
    echo "<td><font color=$colour>$name</font></td>";
    echo "<td><font color=$colour>$start_date</font></td>";
    echo "<td><font color=$colour>$status</font></td>";
    echo "<td><font color=$colour>$nights</font></td>";
    echo "<td><font color=$colour>$total_bed_nights</font></td></tr>";
  }

  // Totals
  echo "<tr bgcolor=lightgray>";
  echo "<td>&nbsp;</td>";
  echo "<td><b>Total Beds</b></td>";
  echo "<td>&nbsp;</td>";
  echo "<td>&nbsp;</td>";
  echo "<td>&nbsp;</td>";
  echo "<td><b>$total_beds_count</b></td>";
  echo "</tr>";

  echo "<tr bgcolor=lightgray>";
  echo "<td>&nbsp;</td>";
  echo "<td><b>Cancelled</b></td>";
  echo "<td>&nbsp;</td>";
  echo "<td>&nbsp;</td>";
  echo "<td>&nbsp;</td>";
  echo "<td><b>$total_cancelled_count</b></td>";
  echo "</tr>";

  echo "</table>\n";
} // function


?>
