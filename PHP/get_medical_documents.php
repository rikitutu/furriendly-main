<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if (!isset($_GET['pet_id'])) {
    echo json_encode(['success' => false, 'message' => 'Pet ID required']);
    exit();
}

$pet_id = intval($_GET['pet_id']);
$username = $_SESSION['username'];

// Verify pet belongs to user
$stmt = $conn->prepare("SELECT id FROM pets WHERE id = ? AND username = ?");
$stmt->bind_param('is', $pet_id, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Pet not found or access denied']);
    exit();
}

// Get medical documents - using CORRECT column names
$stmt = $conn->prepare("
    SELECT id, original_name, file_path, file_size, uploaded_at, description, uploaded_by 
    FROM medical_documents 
    WHERE pet_id = ? 
    ORDER BY uploaded_at DESC
");
$stmt->bind_param('i', $pet_id);
$stmt->execute();
$result = $stmt->get_result();

$documents = [];
while ($row = $result->fetch_assoc()) {
    $documents[] = $row;
}

echo json_encode(['success' => true, 'documents' => $documents]);
?>