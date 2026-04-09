<?php
$_SERVER['SERVER_NAME'] = 'localhost';
require 'load.php';

$query = \Booking\Rooms\Model::toDataTable();
echo $query->text();
echo "\n";
