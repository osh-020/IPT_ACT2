<?php

$server = 'localhost';
$username = 'root';
$password = '';
$dbname = 'ipt_act2';
$port = 3307;


    $conn = mysqli_connect($server, $username, $password, $dbname, $port);
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }                   

?>
