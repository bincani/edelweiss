<html>
<head><title>Edelweiss Membership</title></head>
<body>
<h1>Edelweiss Membership</h1>

<table border=1>
  <tbody align="left" valign="top">

<?php
require("class.edelweiss.php");

$mem = new Member();
$db->query("SELECT `$mem->id_name` FROM " . $mem->table_name . " WHERE `membership_type` NOT LIKE 'Spouse' AND `membership_type` NOT LIKE 'Junior' AND `membership_type` NOT LIKE 'Resigned' ORDER BY last_name");
if(mysqli_num_rows($db->result) == 0)
{
  echo "Error";
}
else
{
  $member_ids = array();
  while ($row = mysqli_fetch_array($db->result, MYSQLI_ASSOC))
    foreach($row as $key => $val)
      $member_ids[] = $val;

  foreach($member_ids as $key => $val)
  {
    $mem = new Member($val);
?>

    <tr>
      <td valign="top">
  <table border=0><COLGROUP><COL width='250'><COL width='250'><COL width='250' align=center>
  <tbody align="left" valign="top">
  <tr valign="top">
  <td valign="top">
    <?
      echo '<b>' . $mem->first_name ." ". $mem->last_name . "</b> (". $mem->membership_type .")";
    ?>
  </td>
  <td valign="top" align="right">
  <? /* Print all phone numbers for this member */
      if ($phone = $mem->phone())
      {
        foreach ($phone as $key => $val)
        {
          if ($val->area_code == "")
            echo $val->phone_type . ": ". $val->number ."<br>";
          else
            echo $val->phone_type . ": (". $val->area_code . ") ". $val->number ."<br>";
        }
      }
  ?>
  </td>
  <td valign="top" align="right">
  <?
      if ($email = $mem->email())
      {
        foreach ($email as $key => $val)
          echo "<a href=\"mailto:". $val->address ."\">". $val->address ."</a><br>";
      }
  ?>
  </td>
  </tr>
  <? /* Print all members who live under this person */
    if ($dependants = $mem->dependants())
    {
      foreach ($dependants as $key => $dependant)
      {
//    echo "Dependants: ". $val->first_name." ". $val->last_name ." (". $val->membership_type .")<br>";
  ?>
  <tr valign="top">
  <td valign="top">&nbsp;&nbsp;
    <?
       echo $dependant->first_name ." ". $dependant->last_name ." (". $dependant->membership_type .")";
    ?>
  </td>
  <td valign="top" align="right">
  <? /* Print all phone numbers for this member */
      if ($phone = $dependant->phone())
      {
        foreach ($phone as $key => $val)
        {
          if ($val->area_code == "")
            echo $val->phone_type . ": ". $val->number ."<br>";
          else
            echo $val->phone_type . ": (". $val->area_code . ") ". $val->number ."<br>";
        }
      }
  ?>
  </td>
  <td valign="top" align="right">
  <?
      if ($email = $dependant->email())
      {
        foreach ($email as $key => $val)
          echo "<a href=\"mailto:". $val->address ."\">". $val->address ."</a><br>";
      }
  ?>
  </td>
  </tr>
  <?
      } // foreach dependant member
    } // if dependants
  ?>
  </tbody>
  </table>    
      </td> 
      <td valign="top">
    <?
      if ($address = $mem->address())
      {
        echo $address->street ."<br>"
          . $address->city ."<br>". $address->state .", ". $address->postcode;
      }
    ?>
  </td>
    </tr>
<?
    } // foreach main member
  } // if any members
?>
  </tbody>
</table>


</body>
</html>
