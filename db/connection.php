<?php
$host     = "localhost";
$username = "root";
$password = "";
$database = "hospital_db";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}
define('ADMIN_NOTIFY_EMAIL', 'luqman.ahmad.cs@gmail.com');
?>