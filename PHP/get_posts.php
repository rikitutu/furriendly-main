<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../php/db_connect.php';

$isLoggedIn = isset($_SESSION['username']);
$username = $isLoggedIn ? $_SESSION['username'] : null;

// Get posts with user info and event info
$query = "
    SELECT
        p.id,
        p.username,
        p.content,
        p.image_path,
        p.event_id,
        p.created_at,
        p.updated_at,
        u.name,
        u.profile_pic,
        e.event_title,
        e.event_date,
        e.location
    FROM posts p
    LEFT JOIN users u ON p.username = u.username
    LEFT JOIN events e ON p.event_id = e.id
    ORDER BY p.created_at DESC
";

$result = $conn->query($query);
$posts = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($posts);
?>
