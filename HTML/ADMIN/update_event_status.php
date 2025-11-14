<?php
session_start();
require '../../php/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Check if user is admin
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || $user['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Get and validate inputs
$event_id = $_POST['event_id'] ?? 0;
$status = $_POST['status'] ?? '';
$reason = $_POST['reason'] ?? '';

if (empty($event_id) || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

// Validate status
$allowed_statuses = ['approved', 'rejected', 'completed'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    // Update event status
    if ($status === 'rejected' && !empty($reason)) {
        $stmt = $conn->prepare("UPDATE events SET status = ?, rejection_reason = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $reason, $event_id);
    } else {
        $stmt = $conn->prepare("UPDATE events SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $event_id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => "Event {$status} successfully"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>