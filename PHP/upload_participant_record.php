<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!isset($_POST['event_id']) || !isset($_POST['pet_id']) || !isset($_POST['participant_username'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

$event_id = intval($_POST['event_id']);
$pet_id = intval($_POST['pet_id']);
$participant_username = $_POST['participant_username'];
$host_username = $_SESSION['username'];
$record_notes = $_POST['record_notes'] ?? '';

try {
    // Step 1: Verify the event exists and current user is the host
    $verify_event = $conn->prepare("SELECT id, event_title FROM events WHERE id = ? AND host_username = ?");
    $verify_event->bind_param("is", $event_id, $host_username);
    $verify_event->execute();
    $event_result = $verify_event->get_result();
    
    if ($event_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Event not found or you are not the host']);
        exit;
    }
    $event_info = $event_result->fetch_assoc();
    
    // Step 2: Verify the participant and pet actually participated in this event
    $verify_participation = $conn->prepare("
        SELECT ep.id 
        FROM event_participants ep 
        WHERE ep.event_id = ? 
        AND ep.username = ? 
        AND ep.pet_id = ?
        AND ep.status IN ('joined', 'completed')
    ");
    $verify_participation->bind_param("isi", $event_id, $participant_username, $pet_id);
    $verify_participation->execute();
    $participation_result = $verify_participation->get_result();
    
    if ($participation_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'This pet did not participate in the specified event']);
        exit;
    }
    
    // Step 3: Verify the pet belongs to the participant
    $verify_pet = $conn->prepare("SELECT id FROM pets WHERE id = ? AND username = ?");
    $verify_pet->bind_param("is", $pet_id, $participant_username);
    $verify_pet->execute();
    $pet_result = $verify_pet->get_result();
    
    if ($pet_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Pet not found or does not belong to the participant']);
        exit;
    }
    
    // Step 4: Handle file upload if provided
    $file_path = null;
    if (isset($_FILES['medical_record']) && $_FILES['medical_record']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/medical_records/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . bin2hex(random_bytes(8)) . '_' . $_FILES['medical_record']['name'];
        $file_path = $upload_dir . $file_name;
        
        if (!move_uploaded_file($_FILES['medical_record']['tmp_name'], $file_path)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
            exit;
        }
    }
    
    // Step 5: Insert the medical record
    $insert_record = $conn->prepare("
        INSERT INTO pet_medical_records 
        (pet_id, event_id, uploaded_by, record_notes, file_path, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $insert_record->bind_param("iisss", $pet_id, $event_id, $host_username, $record_notes, $file_path);
    
    if ($insert_record->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Medical record uploaded successfully',
            'record_id' => $conn->insert_id
        ]);
    } else {
        // Clean up uploaded file if database insert failed
        if ($file_path && file_exists($file_path)) {
            unlink($file_path);
        }
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    // Clean up uploaded file if error occurred
    if (isset($file_path) && $file_path && file_exists($file_path)) {
        unlink($file_path);
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>