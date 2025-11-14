<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if (!isset($_POST['record_id'])) {
    echo json_encode(['success' => false, 'message' => 'Record ID required']);
    exit();
}

$record_id = intval($_POST['record_id']);
$username = $_SESSION['username'];

// Verify user uploaded this record (host can only delete their own uploads)
$stmt = $conn->prepare("
    SELECT file_path 
    FROM medical_documents 
    WHERE id = ? AND uploaded_by = ?
");
$stmt->bind_param('is', $record_id, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Record not found or access denied']);
    exit();
}

$record = $result->fetch_assoc();

// Delete file from server
$file_path = dirname(__DIR__) . '/' . $record['file_path'];
if (file_exists($file_path)) {
    unlink($file_path);
}

// Delete record from database
$stmt = $conn->prepare("DELETE FROM medical_documents WHERE id = ?");
$stmt->bind_param('i', $record_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Record deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
?>