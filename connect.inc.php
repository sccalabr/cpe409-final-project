<?php
$db_host = "mydbinstance.cqp85dchbqjz.us-west-2.rds.amazonaws.com;port=3306";
$db_name = "twiddit";
$db_user = "stanley";
$db_pass = "ims01337";

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    // set the PDO error mode to exception
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    /* echo "Connected successfully"; */
    }
catch(PDOException $e)
    {
       echo "Connection failed: " . $e->getMessage();
    }

