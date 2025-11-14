<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../php/db_connect.php';

if (!isset($_SESSION['username'])) {
    header('Location: auth.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];
    $username = $_SESSION['username'];
    
    // Verify user owns this event
    $check_stmt = $conn->prepare("SELECT id FROM events WHERE id = ? AND host_username = ?");
    $check_stmt->bind_param("is", $event_id, $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $update_stmt = $conn->prepare("UPDATE events SET status = 'completed' WHERE id = ?");
        $update_stmt->bind_param("i", $event_id);
        
        if ($update_stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Event not found or access denied']);
    }
}
?>