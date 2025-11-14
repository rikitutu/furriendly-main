<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if (!isset($_POST['document_id'])) {
    echo json_encode(['success' => false, 'message' => 'Document ID required']);
    exit();
}

$document_id = intval($_POST['document_id']);
$username = $_SESSION['username'];

// Verify user has access to this document (either owns the pet or uploaded it)
$stmt = $conn->prepare("
    SELECT md.*, p.username as pet_owner
    FROM medical_documents md 
    JOIN pets p ON md.pet_id = p.id 
    WHERE md.id = ? AND (p.username = ? OR md.uploaded_by = ?)
");
$stmt->bind_param('iss', $document_id, $username, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Document not found or access denied']);
    exit();
}

$document = $result->fetch_assoc();

// Delete file from server
$file_path = dirname(__DIR__) . '/' . $document['file_path'];
if (file_exists($file_path)) {
    if (!unlink($file_path)) {
        error_log("Failed to delete file: " . $file_path);
    }
}

// Delete record from database
$stmt = $conn->prepare("DELETE FROM medical_documents WHERE id = ?");
$stmt->bind_param('i', $document_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Document deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
?>