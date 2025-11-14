<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id']) && isset($_POST['status'])) {
    $event_id = intval($_POST['event_id']);
    $status = $_POST['status'];
    $username = $_SESSION['username'];
    
    // Validate status
    if (!in_array($status, ['joined', 'canceled', 'completed'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
    // Update participation status
    $stmt = $conn->prepare("UPDATE event_participants SET status = ? WHERE event_id = ? AND username = ?");
    $stmt->bind_param("sis", $status, $event_id, $username);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Participation status updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No participation record found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>