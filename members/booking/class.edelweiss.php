<?php
include("../../../../.mysql.php");
require("class.dbobject.php");
require("class.database.php");
require("class.member.php");
require("class.bookings.php");

$db = new Database($servername, $username, $password, $databasename);
$db->onError = "die";
$db->connect();

?>