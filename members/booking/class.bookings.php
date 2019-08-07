<?php

class Booking extends DBObject
{
    function __construct($id = "")
    {    // table        primary_key            column names                     [load record with this id]
        parent::__construct('edelweiss_booking', 'booking_id', array('member_id', 'booking_date', 'status'), $id);
    }

    function member()
    {
        return new Member($this->member_id);
    }

    function days()
    {
        global $db;

        $join_array = array();
        $join = new Days();
        $db->query("SELECT `$join->id_name` FROM " . $join->table_name . " WHERE `$this->id_name` = '$this->id' ORDER BY date");
        if(mysqli_num_rows($db->result) == 0)
            return false;
        else
        {
            $join_ids = array();
            while ($row = mysqli_fetch_array($db->result, MYSQLI_ASSOC))
                foreach($row as $key => $val)
                    $join_ids[] = $val;

            foreach($join_ids as $key => $val)
                $join_array[] = new Days($val);
            return $join_array;
        }
    }

    function payments()
    {
        global $db;

        $join_array = array();
        $join = new Payments();
        $db->query("SELECT `$join->id_name` FROM " . $join->table_name . " WHERE `$this->id_name` = '$this->id'");
        if(mysqli_num_rows($db->result) == 0)
            return false;
        else
        {
            $join_ids = array();
            while ($row = mysqli_fetch_array($db->result, MYSQLI_ASSOC))
                foreach($row as $key => $val)
                    $join_ids[] = $val;

            foreach($join_ids as $key => $val)
                $join_array[] = new Payments($val);
            return $join_array;
        }
    }

    function cost()
    {
        $cost = 0;
        if ($days = $this->days())
        {
            foreach($days as $key => $val)
                $cost += $val->cost();
        }
        return $cost;
    }

    function statusColour()
    {
        switch ($this->status)
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

    public static function find_by_year($year = "")
    {
        global $db;

        // default to current year
        if (empty($year)) {
            $year = date("Y");
        }
        // 1st of Jan
        $year = $year ."0101";
        $booking = new Booking();
        // update with sort
        $sql = sprintf(
            //"SELECT eb.booking_id FROM edelweiss_booking eb WHERE `booking_date` >= DATE_SUB('%s', INTERVAL 1 YEAR) AND `booking_date` <= DATE_ADD('%s', INTERVAL 1 YEAR) order by booking_date",
            "SELECT distinct eb.booking_id, min(date) FROM edelweiss_booking eb left join edelweiss_days ed on eb.booking_id = ed.booking_id WHERE `booking_date` >= DATE_SUB('%s', INTERVAL 1 YEAR) AND `booking_date` <= DATE_ADD('%s', INTERVAL 1 YEAR) group by eb.booking_id order by min(date)",
            $year,
            $year
        );
        //echo sprintf("sql: %s", $sql);

        $db->query($sql);
        if (mysqli_num_rows($db->result) == 0) {
            return false;
        }
        else {
            $bookingIds = array();
            $row = mysqli_fetch_array($db->result, MYSQLI_ASSOC);
            while ($row = mysqli_fetch_array($db->result, MYSQLI_ASSOC)) {
                $bookingIds[] = $row[$booking->id_name];
            }
            $year_date = strtotime($year . "-01-01");
            $bookings = array();
            foreach ($bookingIds as $i => $bookingId) {
                $booking = new Booking($bookingId);
                if ($days = $booking->days()) {
                    $start_date = strtotime($days[0]->date);
                    // check its current year
                    if ($start_date > $year_date) {
                        $bookings[] = $booking;
                    }
                }
            }
            return $bookings;
        }
    }
}

class Days extends DBObject
{
    function __construct($id = "")
    {    // table        primary_key            column names                     [load record with this id]
        parent::__construct('edelweiss_days', 'day_id', array('date', 'booking_id', 'members', 'juniors', 'adult_guests', 'child_guests'), $id);
    }

    function cost()
    {
        // Locate the rates for this day
        $rate = Rates::find_by_date($this->date);
        if (is_object($rate))
        {
            $cost = $this->members * $rate->adult;
            $cost += $this->juniors * $rate->junior;
            $cost += $this->adult_guests * $rate->adult_guest;
            $cost += $this->child_guests * $rate->child_guest;

            if (($rate->name == "Summer") && ($cost > 50.00))
                $cost = 50.00;
            return $cost;
        }
    }

    function beds()
    {
        return $this->members + $this->juniors + $this->adult_guests + $this->child_guests;
    }
}

class Payments extends DBObject
{
    function __construct($id = "")
    {    // table        primary_key            column names                     [load record with this id]
        parent::__construct('edelweiss_payments', 'payment_id', array('date', 'amount', 'booking_id', 'type', 'details'), $id);
    }
}

class Rates extends DBObject
{
    function __construct($id = "")
    {    // table        primary_key            column names                     [load record with this id]
        parent::__construct('edelweiss_rates', 'rate_id', array('name', 'start', 'finish', 'adult', 'junior', 'adult_guest', 'child_guest'), $id);
    }

    public static function find_by_date($date = "")
    {
        global $db;

        $rate = new Rates();
        $db->query("SELECT `$rate->id_name` FROM " . $rate->table_name
            ." WHERE `start` <= '$date' AND `finish` >= '$date'");
        if(mysqli_num_rows($db->result) == 0)
            return false;
        else
        {
            $row = mysqli_fetch_array($db->result, MYSQLI_ASSOC);
            return new Rates($row[$rate->id_name]);
        }
    }
}

function format_price($price)
{
    if ($price < 0)
        return "<font color=red>". number_format($price, 2) ."</font>";
    else
        return number_format($price, 2);
}

?>
