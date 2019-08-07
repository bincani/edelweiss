<?php
require("class.edelweiss.php");

echo "<html><head><title>Edelweiss Booking Payment</title></head>";
echo "<body>";


function DisplayBookings()
{
    $bookingId = "";
    echo "<h1>Select a Booking</h1>\n";
    echo "<form action=\"make_payment.php\" method=\"post\">\n";
    echo "<table border=1>\n";
    echo sprintf("<tr><td>Booking</td><td><input type=\"text\" name=\"booking\" maxlength=\"4\" size=\"4\" value=\"%s\"/></td></tr>\n", $bookingId);
    echo "<tr><td>&nbsp;</td><td><input type=\"submit\" name=\"select\" value=\"Select\" /></td></tr>\n";

    echo "</table></form>\n";

}

function DisplayPaymentForm()
{
    $booking_id = $_POST['booking'];
    echo "<h1>Enter Payment Details</h1>";


    DisplayPayments($booking_id);

    echo "<form action=\"make_payment.php\" method=\"post\">\n";
    echo "<table border=1>\n";
    echo "<tr><td>Booking</td><td><input type=\"text\" name=\"booking\" maxlength=\"4\" size=\"4\" value=\"$booking_id\"/></td></tr>\n";
    echo "<tr><td>Date of Payment (year-month-day)</td><td>";
    echo "<input type=\"text\" name=\"date_year\" maxlength=\"4\" size=\"4\" value=\"2009\"/> - ";
    echo "<input type=\"text\" name=\"date_month\" maxlength=\"2\" size=\"2\" value=\"\"/> - ";
    echo "<input type=\"text\" name=\"date_day\" maxlength=\"2\" size=\"2\" value=\"\"/>";
    echo "</td></tr>\n";
    echo "<tr><td>Amount $</td><td><input type=\"text\" name=\"amount\" maxlength=\"7\" size=\"7\" value=\"0.00\"/></td></tr>\n";
    echo "<tr><td>Type</td><td><select name=\"type\"/>";
    echo "<option value=\"online\">online</option>";
    echo "<option value=\"cheque\">cheque</option>";
    echo "</td></tr>\n";
    echo "<tr><td>Notes</td><td><input type=\"text\" name=\"details\" maxlength=\"20\" size=\"20\" value=\"\"/></td></tr>";
    echo "<tr><td>&nbsp;</td><td><input type=\"submit\" name=\"payment\" value=\"Make Payment\" /></td></tr>";

    echo "</table></form>\n";


}

function ProcessPayment()
{
    echo "<h1>Payment Complete</h1>\n";
    $booking_id = $_POST['booking'];

    $day = $_POST['date_day'];
    $month = $_POST['date_month'];
    $year = $_POST['date_year'];
    $amount = $_POST['amount'];
    $type = $_POST['type'];
    $details = $_POST['details'];

    $payment = new Payments();
    $payment->date = $year ."-". $month ."-". $day;
    $payment->booking_id = $booking_id;
    $payment->amount = $amount;
    $payment->type = $type;
    $payment->details = $details;
    $payment->insert();

    $booking = new Booking($booking_id);
    $cost = $booking->cost();

    $total_paid = 0;
    if ($payments = $booking->payments())
    {
        foreach($payments as $payment_key => $payment_val)
        {
            $total_paid += $payment_val->amount;
        }
    }

    echo "Total Paid = " . format_price($total_paid) ."<br>\n";
    echo "Cost = " . format_price($cost) ."<br>\n";
    if ($total_paid >= $cost)
    {
        $booking->status = "Fully_Paid";
        $booking->update();
    }
    else if ($total_paid >= $cost * 0.30)
    {
        $booking->status = "Deposit_Paid";
        $booking->update();
    }
    else
    {
        $booking->status = "Unconfirmed";
        $booking->update();
    }

    DisplayPayments($booking_id);
}

function DisplayPayments($booking_id)
{
    $balance = 0;
    echo "<table border=1>\n";
    echo "<tr><td>Payments</td><td>Amount</td></tr>\n";
    $booking = new Booking($booking_id);
    if ($payments = $booking->payments())
    {
        foreach($payments as $payment_key => $payment_val)
        {
            echo "<tr><td>Recieved ". $payment_val->type ." on ". $payment_val->date;
            echo " ". $payment_val->details    ."</td>\n";
            echo "<td align=right>". format_price($payment_val->amount) ."</td></tr>\n";
            $balance += $payment_val->amount;
        }
    }
    else
    {
        echo "<tr><td>None</td><td>&nbsp;</td></tr>\n";
    }
    echo "</table><p>\n";
}

if (isset($_POST['booking']))
{
    if (isset($_POST['payment']))
    {
        ProcessPayment();
    }
    else
    {
        DisplayPaymentForm();
    }
}
else
{
    DisplayBookings();
}

?>
