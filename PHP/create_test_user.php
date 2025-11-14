<?php
include 'db_connect.php'; // make sure this file exists and is correct

$username = 'admin';
$password = password_hash('12345', PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, password) VALUES ('$username', '$password')";
if ($conn->query($sql)) {
    echo "✅ Test user created!<br>";
    echo "Username: admin<br>Password: 12345";
} else {
    echo "❌ Error: " . $conn->error;
}

$conn->close();
?>