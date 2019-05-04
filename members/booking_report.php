<html>
<head><title>Booking Report</title></head>
<body>

<?php
  echo "<h2>Booking Report 2007</h2>";
  include("booking/annual_booking_report.php");
  echo "<hr>\n";

  echo "<h2>Booking Report 2006</h2>";
  dspBookingsForYear("20060101");
  echo "<hr>\n";

  echo "<h2>Booking Report 2005</h2>";
  dspBookingsForYear("20050101");
  echo "<hr>\n";

?>

</body>
</html>

