<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if (!isset($_POST['pet_id'])) {
    echo json_encode(['success' => false, 'message' => 'Pet ID required']);
    exit();
}

$pet_id = intval($_POST['pet_id']);
$username = $_SESSION['username'];

// Verify the pet belongs to the user
$check_stmt = $conn->prepare("SELECT id FROM pets WHERE id = ? AND username = ?");
$check_stmt->bind_param("is", $pet_id, $username);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Pet not found or access denied']);
    exit();
}

// Check if pet is associated with any event participants
$participant_stmt = $conn->prepare("SELECT id FROM event_participants WHERE pet_id = ?");
$participant_stmt->bind_param("i", $pet_id);
$participant_stmt->execute();
$participant_result = $participant_stmt->get_result();

if ($participant_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete pet that is associated with event participations']);
    exit();
}

// Delete the pet
$delete_stmt = $conn->prepare("DELETE FROM pets WHERE id = ? AND username = ?");
$delete_stmt->bind_param("is", $pet_id, $username);

if ($delete_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Pet deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
?>