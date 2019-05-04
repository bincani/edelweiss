<?php
require("class.edelweiss.php");

if (empty($_GET['booking']))
{
        $_GET['booking'] = "";
}
else
{
  $booking_val = new Booking($_GET['booking']);
  $member = new Member($booking_val->member_id);
  echo "<html><head><title>Edelweiss Booking</title></head>";
  echo "<body><h1>Booking ". $booking_val->id ."</h1>\n";

  echo "<ul>\n";
  echo $member->first_name ." ". $member->last_name ."<br>\n";
  $address = $member->address();
  echo $address ."<br>\n";
  $emails = $member->email();
  if (!empty($emails)) echo "<a href=\"mailto:". $emails[0] ."\">". $emails[0] ."</a><br>\n";
  echo "</ul><p>\n";
  
  echo "This booking was made on ". $booking_val->booking_date .". <p>";
  echo "Current Status: <font color=\"". $booking_val->statusColour() ."\">". $booking_val->status ."</font><p>";
  $total_cost = $booking_val->cost();
  if ($days = $booking_val->days())
  {
      echo "<table border=1>";
      echo "<tr><thead>";
      echo "<th width=100>Night</th><th>Members</th><th>Juniors</th><th>Adult Guests</th><th>Junior Guests</th><th width=80>Amount $</th>";
      echo "</tr></thead>";
    foreach($days as $day_key => $day_val)
    {
      echo "<tr><td>". $day_val->date ."</td>";
      echo "<td>". $day_val->members   ."</td>";
      echo "<td>". $day_val->juniors  ."</td>";
      echo "<td>". $day_val->adult_guests  ."</td>";
      echo "<td>". $day_val->child_guests  ."</td>";
      echo "<td align=right>". format_price($day_val->cost())  ."</td></tr>";
    }
      echo "<tr bgcolor=silver><td colspan=5>Total</td><td align=right><b>" . $total_cost ."</b></td></tr>";
    echo "</table><br>";
  }

  $total_cost = -1 * $total_cost;
#  $deposit = round($total_cost * 0.30);
  $deposit = $total_cost * 0.30;
  $remainder = $total_cost - $deposit;
  $balance = $total_cost;

  $deposit_date = new DateTime($booking_val->booking_date);
  $deposit_date->modify("+14 days");
  $remainder_date = new DateTime($days[0]->date);
  $remainder_date->modify("-1 month");

  $strike = ""; $strikend = "";
  if ($booking_val->status == 'Cancelled')
  {
    $strike = "<del>";
    $strikend = "</del>";
  }

  echo "<table border=1><thead>";
  echo "<tr><th width=120>Item</th><th width=300>Details</th><th width=80>Amount $</th></tr></thead>";
  echo "<tr><td>Deposit Due</td><td>30% deposit due on ". $deposit_date->format("Y-m-d") ."</td>";
  echo "<td align=right>". $strike . format_price($deposit) . $strikend ."</td></tr>";
  echo "<tr><td>Remainder Due</td><td>70% remainder due on ". $remainder_date->format("Y-m-d") ."</td>";
  echo "<td align=right>". $strike . format_price($remainder). $strikend ."</td></tr>";
  if ($payments = $booking_val->payments())
  {
    foreach($payments as $payment_key => $payment_val)
    {
      echo "<tr><td>Payment</td>";
      echo "<td>Recieved ". $payment_val->type ." on ". $payment_val->date;
      echo " ". $payment_val->details  ."</td>";
      echo "<td align=right>". format_price($payment_val->amount) ."</td></tr>";
      $balance += $payment_val->amount;
    }
  }
  echo "<tr bgcolor=silver><td colspan=2>Balance</td><td align=right><b>";
  echo $strike . format_price($balance). $strikend ."</b></td></tr>";
  echo "</table><br>";
}
?>
