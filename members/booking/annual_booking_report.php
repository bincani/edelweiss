<?php
require("class.edelweiss.php");

if (empty($_GET['year']))
{
        $_GET['year'] = date("Y");
}

$year = $_GET['year'];

echo "<html><head><title>Edelweiss Booking Report for ". $year ."</title></head>";
echo "<body><h1>Edelweiss Booking Report for ". $year ."</h1>\n";

echo "<table border=1>\n";
echo "<tr bgcolor=lightgray>";
echo "<td>Booking</td><td>Name</td><td>Start Date</td><td>Status</td><td>Nights</td><td>Beds</td><td>Cost</td><td>Paid</td><td>Balance</td></tr>\n";

$total_beds_count = 0;
$total_cancelled_count = 0;
$total_unknown_count = 0;
$total_cost = 0;
$total_paid = 0;
if ($bookings = Booking::find_by_year($year))
{
  foreach ($bookings as $key => $val)
  {
    $start_date = $val->booking_date;
    $id = $val->id;
    $status = $val->status;
    $colour = $val->statusColour();
    $member = $val->member();
    $name = $member->first_name ." ". $member->last_name;

    $cost = $val->cost();
    $paid = 0;
    if ($payments = $val->payments())
      foreach ($payments as $key_pay => $val_pay)
        $paid += $val_pay->amount;
    $paid = $paid;
    $balance = $paid - $cost;

    $total_bed_nights = 0;
    $nights = 0;
    if ($days = $val->days())
    {
      $start_date = $days[0]->date;
      $nights = count($days);
      foreach($days as $day_key => $day_val)
        $total_bed_nights += $day_val->beds();
    }

    $del_start = "";
    $del_end = "";
    if (($status == 'Deposit_Paid') || ($status == 'Fully_Paid'))
    {
      $total_beds_count += $total_bed_nights;
      $total_cost += $cost;
      $total_paid += $paid;
    }
    else if (($status == 'Lapsed') || ($status == 'Cancelled'))
    {
      $total_cancelled_count += $total_bed_nights;
      $del_start = "<del>";
      $del_end = "</del>";
    }
    else
    {
      $total_unknown_count += $total_bed_nights;
      $total_cost += $cost;
      $total_paid += $paid;
    }

    $cost = format_price($cost);
    $paid = format_price($paid);
    $balance = format_price($balance);

    echo "<tr>";
    echo "<td><a href=\"display_booking.php?booking=$id\">$id</a></td>";
    echo "<td><font color=$colour>$name</font></td>";
    echo "<td><font color=$colour>$start_date</font></td>";
    echo "<td><font color=$colour>$status</font></td>";
    echo "<td><font color=$colour>$nights</font></td>";
    echo "<td><font color=$colour>$total_bed_nights</font></td>";
    echo "<td align=right>$del_start<font color=$colour>$cost</font>$del_end</td>";
    echo "<td align=right>$del_start<font color=$colour>$paid</font>$del_end</td>";
    echo "<td align=right>$del_start$balance$del_end</td></tr>";
  }
}
// Totals
$total_balance = format_price($total_paid - $total_cost);
$total_cost = format_price($total_cost);
$total_paid = format_price($total_paid);


echo "<tr bgcolor=lightgray>";
echo "<td colspan=6><b>Totals (excluding canceled or lapsed)</b></td>";
echo "<td><b>$total_cost</b></td>";
echo "<td><b>$total_paid</b></td>";
echo "<td><b>$total_balance</b></td>";
echo "</tr>";

echo "<tr bgcolor=lightgray>";
echo "<td colspan=4><b>Total Beds</b></td>";
echo "<td><b>$total_beds_count</b></td>";
echo "</tr>";

echo "<tr bgcolor=lightgray>";
echo "<td colspan=4><b>Cancelled</b></td>";
echo "<td><b>$total_cancelled_count</b></td>";
echo "</tr>";

echo "<tr bgcolor=lightgray>";
echo "<td colspan=4><b>Unknown</b></td>";
echo "<td><b>$total_unknown_count</b></td>";
echo "</tr>";

echo "</table>\n";

?>