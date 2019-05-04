<?php
require_once( dirname(__FILE__).'/class.Calendar.php' );
require_once( 'edelweiss.php' );

class Calendar_Booked extends Calendar {
    
function dspDayCell ( $day )
{
?>
 <?php
	$show_date = date("Y-m-d", mktime(0,0,0,$this->month,$day,$this->year));
	if (isSummer($show_date))
		echo "<td valign='top'><b>$day</b> <font color='red' size='1'>S</font><p align='right'>&nbsp;";
	else if (isHighPeak($show_date))
		echo "<td valign='top'><b>$day</b> <font color='blue' size='1'>H</font><p align='right'>&nbsp;";
	else
		echo "<td valign='top'><b>$day</b> <font color='blue' size='1'>L</font><p align='right'>&nbsp;";
 
	dspBookingSummaryForDay($show_date);
  ?>
 </td>
<?php     
}

} // end class
?>














