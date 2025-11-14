<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../php/db_connect.php';

if (!isset($_SESSION['username'])) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

$username = $_SESSION['username'];

// Get approved events for the dropdown
$query = "
    SELECT id, event_title, event_date, location
    FROM events
    WHERE status = 'approved'
    ORDER BY event_date DESC
";

$result = $conn->query($query);
$events = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($events);
?>
