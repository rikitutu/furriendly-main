<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['username'])) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

if (!isset($_GET['record_id'])) {
    header('HTTP/1.0 400 Bad Request');
    exit('Record ID required');
}

$record_id = intval($_GET['record_id']);
$username = $_SESSION['username'];

// Verify user has access to this record (either owns the pet or uploaded it)
$stmt = $conn->prepare("
    SELECT md.*, p.username as pet_owner
    FROM medical_documents md 
    JOIN pets p ON md.pet_id = p.id 
    WHERE md.id = ? AND (p.username = ? OR md.uploaded_by = ?)
");
$stmt->bind_param('iss', $record_id, $username, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied to this record');
}

$record = $result->fetch_assoc();
$file_path = dirname(__DIR__) . '/' . $record['file_path'];

if (!file_exists($file_path)) {
    header('HTTP/1.0 404 Not Found');
    exit('File not found');
}

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $record['original_name'] . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($file_path);
exit();
?>