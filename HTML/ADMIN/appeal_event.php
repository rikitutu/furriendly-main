<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../../php/db_connect.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['success'=>false,'message'=>'Not logged in']);
    exit;
}

$event_id = $_POST['event_id'] ?? null;
$appeal_message = $_POST['appeal_message'] ?? null;

if (!$event_id || !$appeal_message) {
    echo json_encode(['success'=>false,'message'=>'Missing parameters']);
    exit;
}

// Verify the event belongs to the user and is rejected
$stmt = $conn->prepare("SELECT id FROM events WHERE id=? AND host_username=? AND status='rejected'");
$stmt->bind_param("is", $event_id, $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success'=>false,'message'=>'Event not found or not eligible for appeal']);
    exit;
}

// Update event with appeal
$stmt = $conn->prepare("UPDATE events SET status='pending', appeal_message=?, appealed_at=NOW() WHERE id=?");
$stmt->bind_param("si", $appeal_message, $event_id);

if ($stmt->execute()) {
    echo json_encode(['success'=>true,'message'=>'Appeal submitted successfully. Your event is now pending review.']);
} else {
    echo json_encode(['success'=>false,'message'=>'Failed to submit appeal.']);
}
?>