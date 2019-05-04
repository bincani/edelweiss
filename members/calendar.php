<html>
<head><title>Edelweiss Booking Calendar</title></head>
<body>
<h1>Edelweiss Booking Calendar</h1>

<?php
require_once( 'class.Calendar.php' );
require_once( 'class.Calendar_Booked.php' );
require_once( 'edelweiss.php' );

function ShowMonthHeader($year)
{
	echo "<a href=\"$PHP_SELF?action=ShowMonths&year=".($year -1)."\">".($year -1)."</a>&nbsp;";
	echo " &lt; ";
	for ($i = 1; $i <= 12; $i++)
	{
		$month = date("F", mktime(0,0,0,$i,1,$year));
		echo "<a href=\"$PHP_SELF?action=ShowMonths&year=$year&month=$i\">" . $month ."</a>&nbsp;";
	}
	echo " &gt; ";
	echo "<a href=\"$PHP_SELF?action=ShowMonths&year=".($year +1)."\">".($year +1)."</a>&nbsp;";
	echo "<p>";
}

function ShowMonthFooter()
{
	echo "<p><h3>Key</h3>";
	echo "<font color='red' size='1'>S</font> - Summer Rates<br>";
	echo "<font color='blue' size='1'>L</font> - Winter Low Peak Rates<br>";
	echo "<font color='blue' size='1'>H</font> - Winter High Peak Rates<br>";
	echo "<font  size='1'>First Name XX</font> - Members Name and number of beds booked<p>";
	printf("<font  color='%s'>Unconfirmed</font> - Initial booking made<br>", getStatusColour("Unconfirmed"));
	printf("<font  color='%s'>Deposit_Paid</font> - Booking has been confirmed with a 30%% depost<br>", getStatusColour("Deposit_Paid"));;
	printf("<font  color='%s'>Fully_Paid</font> - Booking has been paid in Full<br>", getStatusColour("Fully_Paid"));
	printf("<font  color='%s'>Lapsed</font> - Booking has lapsed because deposit or full payment has not been made<br>", getStatusColour("Lapsed"));
	printf("<font  color='%s'>Cancelled</font> - Booking has been cancelled<br>", getStatusColour("Cancelled"));
}

function ShowMonths()
{
if (ereg("^[0-9]+$", $_GET['year'], $match))
	$year = $match[0];
else
	$year = date("Y");

if (ereg("^[0-9]+$", $_GET['month'], $match))
	$month = $match[0];
else
	$month = date("n");

ShowMonthHeader($year);

$cal = new Calendar_Booked ($year, $month);
$cal->setTableWidth('80%');
echo "<b>".$cal->getFullMonthName()." ".$cal->getYear()."</b>"; 
echo $cal->display();
ShowMonthFooter();
}

function ShowBooking()
{
	$id = $_GET["id"];
	dspDayBooking($id);
}

function ShowBookings()
{
	$day = $_GET['day'];
	$month = $_GET['month'];
	$year = $_GET['year'];
	$show_date = date("Y-m-d", mktime(0,0,0,$this->month,$day,$this->year));
	dspDayBookings($show_date);
}

if (empty($_GET['action']))
{
	$_GET['action'] = "";
}
switch ($_GET['action'])
{
	case "show_bookings":
		ShowBookings();
		break;
	case "show_booking":
		ShowBooking();
		break;
	default:
	case "show_months":
		ShowMonths();
		break;
}
?>

</body>








