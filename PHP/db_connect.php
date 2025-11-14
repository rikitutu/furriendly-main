<?php
$host = "localhost";
$user = "root"; // or your db username
$pass = ""; // your db password
$dbname = "furriendly_db"; // change to your database name

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
