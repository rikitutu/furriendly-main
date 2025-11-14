<?php
// get_pet_record.php - FIXED VERSION
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
$pet_id = isset($_GET['pet_id']) ? intval($_GET['pet_id']) : 0;

if ($pet_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid pet ID', 'count' => 0]);
    exit;
}

try {
    // Verify the user has permission to view these records (either host or pet owner)
    $username = $_SESSION['username'];
    
    // Check if user is host of the event or owner of the pet
    $permission_stmt = $conn->prepare("
        (SELECT 1 FROM events WHERE id = ? AND host_username = ?)
        UNION
        (SELECT 1 FROM pets WHERE id = ? AND username = ?)
        LIMIT 1
    ");
    $permission_stmt->bind_param('isis', $event_id, $username, $pet_id, $username);
    $permission_stmt->execute();
    $permission_result = $permission_stmt->get_result();
    
    if ($permission_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Access denied', 'count' => 0]);
        exit;
    }

    // Get medical records for this pet and event
    $records_stmt = $conn->prepare("
        SELECT 
            md.id,
            md.pet_id,
            md.event_id,
            md.uploaded_by,
            md.original_name,
            md.file_path,
            md.file_size,
            md.description,
            md.record_type,
            md.is_host_upload,
            md.uploaded_at,
            e.event_title
        FROM medical_documents md
        LEFT JOIN events e ON md.event_id = e.id
        WHERE md.pet_id = ? 
        AND (md.event_id = ? OR md.event_id IS NULL OR ? = 0)
        ORDER BY md.uploaded_at DESC
    ");
    
    $records_stmt->bind_param('iii', $pet_id, $event_id, $event_id);
    $records_stmt->execute();
    $records_result = $records_stmt->get_result();
    
    $records = [];
    while ($row = $records_result->fetch_assoc()) {
        $records[] = [
            'id' => $row['id'],
            'pet_id' => $row['pet_id'],
            'event_id' => $row['event_id'],
            'uploaded_by' => $row['uploaded_by'],
            'original_name' => $row['original_name'],
            'file_path' => $row['file_path'],
            'file_size' => $row['file_size'],
            'description' => $row['description'],
            'record_type' => $row['record_type'] ?? 'other',
            'is_host_upload' => $row['is_host_upload'] ?? 0,
            'uploaded_at' => $row['uploaded_at'],
            'event_title' => $row['event_title']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'records' => $records,
        'count' => count($records)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'count' => 0]);
}
?>