<?php

class Member extends DBObject
{
  function __construct($id = "")
  {  // table    primary_key      column names           [load record with this id]
    parent::__construct('edelweiss_members', 'member_id', array('first_name', 'last_name', 'membership_type', 'dependant_of', 'address_id'), $id);
  }

  function email()
  {
    global $db;

    $email_array = array();
    $email = new Email();
    $db->query("SELECT `$email->id_name` FROM " . $email->table_name . " WHERE `$this->id_name` = '$this->id'");
    if(mysql_num_rows($db->result) == 0)
      return false;
    else
    {
      $email_ids = array();
      while ($row = mysql_fetch_array($db->result, MYSQL_ASSOC))
        foreach($row as $key => $val)
          $email_ids[] = $val;

      foreach($email_ids as $key => $val)
        $email_array[] =& new Email($val);
      return $email_array;
    }
  }

  function phone()
  {
    global $db;

    $join_array = array();
    $join = new Phone();
    $db->query("SELECT `$join->id_name` FROM " . $join->table_name . " WHERE `$this->id_name` = '$this->id'");
    if(mysql_num_rows($db->result) == 0)
      return false;
    else
    {
      $join_ids = array();
      while ($row = mysql_fetch_array($db->result, MYSQL_ASSOC))
        foreach($row as $key => $val)
          $join_ids[] = $val;

      foreach($join_ids as $key => $val)
        $join_array[] =& new Phone($val);
      return $join_array;
    }
  }

  function address()
  {
    if ($this->address_id == null)
    {
      if ($this->dependant_of != null)
      {
        $parent = new Member($this->dependant_of);
        return new Address($parent->address_id);
      }
    }
    else
      return new Address($this->address_id);
  }

  function dependants()
  {
    global $db;

    $join_array = array();
    $db->query("SELECT `$this->id_name` FROM " . $this->table_name . " WHERE `dependant_of` = '$this->id'");
    if(mysql_num_rows($db->result) == 0)
      return false;
    else
    {
      $join_ids = array();
      while ($row = mysql_fetch_array($db->result, MYSQL_ASSOC))
        foreach($row as $key => $val)
          $join_ids[] = $val;

      foreach($join_ids as $key => $val)
        $join_array[] =& new Member($val);
      return $join_array;
    }
  }

	function bookings($year = "20070101")
	{
	   global $db;

		$join_array = array();
		$booking = new Booking();
    $db->query("SELECT `$booking->id_name` FROM " . $booking->table_name . " WHERE `$this->id_name` = '$this->id' AND `booking_date` > '$year' AND `booking_date` < DATE_ADD('$year', INTERVAL 1 YEAR)");
		if(mysql_num_rows($db->result) == 0)
		  return false;
		else
		{
		  $join_ids = array();
		  while ($row = mysql_fetch_array($db->result, MYSQL_ASSOC))
			foreach($row as $key => $val)
			  $join_ids[] = $val;

		  foreach($join_ids as $key => $val)
			$join_array[] =& new Booking($val);
		  return $join_array;
		}
	}
}

class Address extends DBObject
{
  function __construct($id = "")
  {  // table    primary_key      column names           [load record with this id]
    parent::__construct('edelweiss_addresses', 'address_id', array('street', 'city', 'state', 'postcode'), $id);
  }

  function __toString()
  {
    return $this->street .", ". $this->city .", ". $this->state ." ". $this->postcode;
  }
}


class Email extends DBObject
{
  function __construct($id = "")
  {  // table    primary_key      column names           [load record with this id]
    parent::__construct('edelweiss_email', 'email_id', array('member_id', 'address', 'notes'), $id);
  }
  
  function __toString()
  {
   return $this->address;
  }
}

class Member_Type extends DBObject
{
  function __construct($id = "")
  {  // table    primary_key      column names           [load record with this id]
    parent::__construct('edelweiss_member_type', 'type_id', array('name', 'letter'), $id);
  }
}

class Phone extends DBObject
{
  function __construct($id = "")
  {  // table    primary_key      column names           [load record with this id]
    parent::__construct('edelweiss_phone', 'phone_id', array('type_id', 'member_id', 'area_code', 'number', 'phone_type', 'notes'), $id);
  }
  
  function __toString()
  {
    if (!empty($this->area_code)) $out = "(". $this->area_code .")";
    return $this->phone_type .": ". $out ." ". $this->number;
  }
}

function test()
{
$mem = new Member();
$db->query("SELECT `$mem->id_name` FROM " . $mem->table_name . " WHERE `membership_type` NOT LIKE 'Spouse' AND `membership_type` NOT LIKE 'Junior'");
if(mysql_num_rows($db->result) == 0)
{
  echo "Error";
}
else
{
  $member_ids = array();
  while ($row = mysql_fetch_array($db->result, MYSQL_ASSOC))
    foreach($row as $key => $val)
      $member_ids[] = $val;

    foreach($member_ids as $key => $val)
    {
      $mem = new Member($val);
      echo "<b>". $mem->first_name ." ". $mem->last_name ." (". $mem->membership_type .")</b><br>";
      
      if ($email = $mem->email())
      {
        foreach ($email as $key => $val)
          echo "Email: ". $val->address ."<br>";
        echo "<p>";
      }
      
      if ($phone = $mem->phone())
      {
      foreach ($phone as $key => $val)
        echo "Phone: ". $val->number . " (". $val->phone_type .")<br>";
      echo "<p>";
      }
      
      if ($address = $mem->address())
      {
        echo "Address: ". $address->street .", ". $address->city .", ". $address->state .", ". $address->postcode;
        echo "<p>";
      }
      
      if ($dependants = $mem->dependants())
      {
        foreach ($dependants as $key => $val)
          echo "Dependants: ". $val->first_name." ". $val->last_name ." (". $val->membership_type .")<br>";
        echo "<p>";
      }
    }
}
}


?>