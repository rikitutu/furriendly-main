<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    $event_id = intval($_POST['event_id']);
    $username = $_SESSION['username'];
    
    // Check if user has a record for this event
    $check_stmt = $conn->prepare("
        SELECT id, status 
        FROM event_participants 
        WHERE event_id = ? AND username = ?
    ");
    $check_stmt->bind_param("is", $event_id, $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No participation record found for this event']);
        exit;
    }
    
    $participant = $check_result->fetch_assoc();
    
    // Toggle between joined and canceled status
    $new_status = ($participant['status'] === 'joined') ? 'canceled' : 'joined';
    
    // Update the participant status
    $update_stmt = $conn->prepare("UPDATE event_participants SET status = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_status, $participant['id']);
    
    if ($update_stmt->execute()) {
        $message = $new_status === 'canceled' ? 'Event participation canceled' : 'Event participation reactivated';
        echo json_encode(['success' => true, 'message' => $message, 'new_status' => $new_status]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    
    $update_stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>