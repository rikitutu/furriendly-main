<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if (!isset($_GET['event_id'])) {
    echo json_encode(['success' => false, 'message' => 'Event ID required']);
    exit();
}

$event_id = intval($_GET['event_id']);
$username = $_SESSION['username'];

// Verify user is the host of this event
$stmt = $conn->prepare("SELECT id FROM events WHERE id = ? AND host_username = ? AND status = 'completed'");
$stmt->bind_param('is', $event_id, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Event not found or access denied']);
    exit();
}

// Get participants with their pets and medical documents
$stmt = $conn->prepare("
    SELECT 
        u.username,
        u.name,
        u.email,
        u.profile_pic,
        ep.joined_at,
        ep.pet_id,
        p.pet_name,
        p.pet_species,
        p.pet_breed,
        p.pet_age,
        p.pet_gender,
        p.medical_history,
        p.vaccines,
        p.medical_condition,
        p.pet_profile_pic
    FROM event_participants ep
    JOIN users u ON ep.username = u.username
    LEFT JOIN pets p ON ep.pet_id = p.id
    WHERE ep.event_id = ? AND ep.status = 'completed'
    ORDER BY ep.joined_at DESC
");
$stmt->bind_param('i', $event_id);
$stmt->execute();
$result = $stmt->get_result();

$participants = [];
while ($row = $result->fetch_assoc()) {
    // Get medical documents for each pet
    $documents = [];
    if ($row['pet_id']) {
        $doc_stmt = $conn->prepare("
            SELECT id, original_name, file_path, file_size, uploaded_at, description, uploaded_by 
            FROM medical_documents 
            WHERE pet_id = ? 
            ORDER BY uploaded_at DESC
        ");
        $doc_stmt->bind_param('i', $row['pet_id']);
        $doc_stmt->execute();
        $doc_result = $doc_stmt->get_result();
        
        while ($doc_row = $doc_result->fetch_assoc()) {
            $documents[] = $doc_row;
        }
        $doc_stmt->close();
    }
    
    $row['medical_documents'] = $documents;
    $participants[] = $row;
}

echo json_encode(['success' => true, 'participants' => $participants]);
?>