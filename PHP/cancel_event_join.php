<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['participant_id'])) {
    $participant_id = intval($_POST['participant_id']);
    $username = $_SESSION['username'];
    
    // Verify that the participant record belongs to the current user
    $verify_stmt = $conn->prepare("
        SELECT id, status 
        FROM event_participants 
        WHERE id = ? AND username = ?
    ");
    $verify_stmt->bind_param("is", $participant_id, $username);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Participant record not found or access denied']);
        exit;
    }
    
    $participant = $verify_result->fetch_assoc();
    
    // Toggle between joined and canceled status
    $new_status = ($participant['status'] === 'joined') ? 'canceled' : 'joined';
    
    // Update the participant status
    $update_stmt = $conn->prepare("UPDATE event_participants SET status = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_status, $participant_id);
    
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