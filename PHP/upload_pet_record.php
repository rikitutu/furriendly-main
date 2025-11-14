<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get all POST data
$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
$pet_id = isset($_POST['pet_id']) ? intval($_POST['pet_id']) : 0;
$participant_id = isset($_POST['participant_id']) ? intval($_POST['participant_id']) : 0;
$username = $_SESSION['username'];
$record_type = $_POST['record_type'] ?? 'other';
$description = $_POST['record_description'] ?? '';

// Debug logging
error_log("Upload Request - Event ID: $event_id, Pet ID: $pet_id, Participant ID: $participant_id, User: $username");

// Validate required fields
if ($event_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
    exit();
}

// If pet_id is 0 but participant_id is provided, get pet_id from participant
if ($pet_id <= 0 && $participant_id > 0) {
    $participant_stmt = $conn->prepare("SELECT pet_id, username FROM event_participants WHERE id = ?");
    if ($participant_stmt) {
        $participant_stmt->bind_param('i', $participant_id);
        $participant_stmt->execute();
        $participant_result = $participant_stmt->get_result();
        
        if ($participant_result->num_rows > 0) {
            $participant_data = $participant_result->fetch_assoc();
            $pet_id = $participant_data['pet_id'];
            $participant_username = $participant_data['username'];
            error_log("Retrieved pet ID from participant: " . $pet_id . " for user: " . $participant_username);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid participant ID']);
            exit();
        }
        $participant_stmt->close();
    }
}

// Final validation of pet_id
if ($pet_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Could not determine valid pet ID. Please ensure the participant has a registered pet.']);
    exit();
}

// 1. Check if the event exists and user is the host
$event_stmt = $conn->prepare("SELECT id, event_title, host_username, status FROM events WHERE id = ?");
if (!$event_stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$event_stmt->bind_param('i', $event_id);
$event_stmt->execute();
$event_result = $event_stmt->get_result();

if ($event_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Event not found']);
    exit();
}

$event_data = $event_result->fetch_assoc();

// Check if user is the host
if ($event_data['host_username'] !== $username) {
    echo json_encode(['success' => false, 'message' => 'You are not the host of this event']);
    exit();
}

// Check event status
if (!in_array($event_data['status'], ['completed', 'approved'])) {
    echo json_encode(['success' => false, 'message' => 'Event must be completed or approved to upload records']);
    exit();
}

// 2. Verify the pet exists and get pet owner info
$pet_stmt = $conn->prepare("SELECT p.id, p.pet_name, p.username as pet_owner FROM pets p WHERE p.id = ?");
if (!$pet_stmt) {
    echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
    exit();
}

$pet_stmt->bind_param('i', $pet_id);
$pet_stmt->execute();
$pet_result = $pet_stmt->get_result();

if ($pet_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Pet not found']);
    exit();
}

$pet_data = $pet_result->fetch_assoc();
$pet_owner = $pet_data['pet_owner'];

// 3. Verify the pet participated in the event
$participation_stmt = $conn->prepare("
    SELECT ep.id 
    FROM event_participants ep 
    WHERE ep.event_id = ? AND ep.pet_id = ? AND ep.status IN ('joined', 'completed')
");
if (!$participation_stmt) {
    echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
    exit();
}

$participation_stmt->bind_param('ii', $event_id, $pet_id);
$participation_stmt->execute();
$participation_result = $participation_stmt->get_result();

if ($participation_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'This pet did not participate in the specified event']);
    exit();
}

// File upload handling
if (!isset($_FILES['record_file']) || $_FILES['record_file']['error'] !== UPLOAD_ERR_OK) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
        UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
        UPLOAD_ERR_PARTIAL => 'File upload incomplete',
        UPLOAD_ERR_NO_FILE => 'No file selected',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
        UPLOAD_ERR_EXTENSION => 'PHP extension stopped upload'
    ];
    $error_msg = $upload_errors[$_FILES['record_file']['error']] ?? 'Unknown upload error';
    echo json_encode(['success' => false, 'message' => 'File upload error: ' . $error_msg]);
    exit();
}

$file = $_FILES['record_file'];

// Validate file type
$allowed_types = [
    'application/pdf', 
    'application/x-pdf'
];

if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Only PDF files are allowed. File type: ' . $file['type']]);
    exit();
}

// Validate file size (10MB)
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File size must be less than 10MB']);
    exit();
}

// Create uploads directory
$upload_dir = dirname(__DIR__) . '/uploads/pet_records/';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Server error: Cannot create upload directory']);
        exit();
    }
}

// Generate unique filename
$original_name = basename($file['name']);
$extension = pathinfo($original_name, PATHINFO_EXTENSION);
$filename = 'record_' . time() . '_' . uniqid() . '.' . $extension;
$filepath = $upload_dir . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // Insert into medical_documents table with host upload flag
    $relative_path = 'uploads/pet_records/' . $filename;
    
    // Check if medical_documents table has record_type and is_host_upload columns
    $check_columns_stmt = $conn->prepare("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'medical_documents' 
        AND TABLE_SCHEMA = DATABASE()
        AND COLUMN_NAME IN ('record_type', 'is_host_upload')
    ");
    $check_columns_stmt->execute();
    $columns_result = $check_columns_stmt->get_result();
    $existing_columns = [];
    while ($col = $columns_result->fetch_assoc()) {
        $existing_columns[] = $col['COLUMN_NAME'];
    }
    
    $has_record_type = in_array('record_type', $existing_columns);
    $has_host_upload = in_array('is_host_upload', $existing_columns);
    
    if ($has_record_type && $has_host_upload) {
        $stmt = $conn->prepare("
            INSERT INTO medical_documents 
            (pet_id, event_id, uploaded_by, original_name, file_path, file_size, description, record_type, is_host_upload) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        if ($stmt) {
            $stmt->bind_param('iisssiss', $pet_id, $event_id, $username, $original_name, $relative_path, $file['size'], $description, $record_type);
        }
    } else if ($has_record_type) {
        $stmt = $conn->prepare("
            INSERT INTO medical_documents 
            (pet_id, event_id, uploaded_by, original_name, file_path, file_size, description, record_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param('iisssiss', $pet_id, $event_id, $username, $original_name, $relative_path, $file['size'], $description, $record_type);
        }
    } else {
        $stmt = $conn->prepare("
            INSERT INTO medical_documents 
            (pet_id, event_id, uploaded_by, original_name, file_path, file_size, description) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param('iisssis', $pet_id, $event_id, $username, $original_name, $relative_path, $file['size'], $description);
        }
    }
    
    if ($stmt && $stmt->execute()) {
        $record_id = $conn->insert_id;
        
        // Also update the pet's medical history if this is a host upload
        if ($has_host_upload) {
            $medical_note = "Medical record uploaded by event host (" . $username . ") for event #" . $event_id . ": " . $record_type;
            if (!empty($description)) {
                $medical_note .= " - " . $description;
            }
            $medical_note .= " on " . date('Y-m-d H:i:s');
            
            $update_pet_stmt = $conn->prepare("
                UPDATE pets 
                SET medical_history = CONCAT(COALESCE(medical_history, ''), '\n', ?) 
                WHERE id = ?
            ");
            if ($update_pet_stmt) {
                $update_pet_stmt->bind_param('si', $medical_note, $pet_id);
                $update_pet_stmt->execute();
                $update_pet_stmt->close();
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Medical record uploaded successfully for ' . $pet_data['pet_name'] . '! The record is now available in the pet\'s profile.',
            'record_id' => $record_id,
            'pet_owner' => $pet_owner
        ]);
    } else {
        unlink($filepath);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . ($stmt ? $stmt->error : $conn->error)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
}

// Close statements
if (isset($event_stmt)) $event_stmt->close();
if (isset($pet_stmt)) $pet_stmt->close();
if (isset($participation_stmt)) $participation_stmt->close();
if (isset($stmt)) $stmt->close();
?>