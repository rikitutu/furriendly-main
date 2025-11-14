<?php
// upload_medical_document.php - FIXED FOREIGN KEY VERSION
session_start();

// Clear any existing output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Start new output buffer
ob_start();

// Include database connection
require 'db_connect.php';

// Set JSON header immediately
header('Content-Type: application/json');

// Function to send error response
function sendError($message) {
    $response = ['success' => false, 'message' => $message];
    ob_clean();
    echo json_encode($response);
    exit();
}

// Function to send success response
function sendSuccess($message) {
    $response = ['success' => true, 'message' => $message];
    ob_clean();
    echo json_encode($response);
    exit();
}

// Main execution
try {
    // Check if user is logged in
    if (!isset($_SESSION['username'])) {
        sendError('Not logged in');
    }

    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Invalid request method');
    }

    // Check required fields
    if (!isset($_POST['pet_id']) || !isset($_FILES['medical_document'])) {
        sendError('Missing required fields');
    }

    // Get and validate inputs
    $pet_id = intval($_POST['pet_id']);
    $username = $_SESSION['username'];
    $description = $_POST['description'] ?? '';

    if ($pet_id <= 0) {
        sendError('Invalid pet ID');
    }

    // Verify pet belongs to user
    $check_stmt = $conn->prepare("SELECT id, pet_name FROM pets WHERE id = ? AND username = ?");
    if (!$check_stmt) {
        sendError('Database error: ' . $conn->error);
    }
    
    $check_stmt->bind_param('is', $pet_id, $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        sendError('Pet not found or access denied');
    }

    $file = $_FILES['medical_document'];

    // Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                sendError('File too large (max 10MB)');
            case UPLOAD_ERR_PARTIAL:
                sendError('File upload incomplete');
            case UPLOAD_ERR_NO_FILE:
                sendError('No file selected');
            default:
                sendError('File upload error');
        }
    }

    // Validate file type
    $allowed_types = ['application/pdf', 'application/x-pdf'];
    if (!in_array($file['type'], $allowed_types)) {
        sendError('Only PDF files are allowed. File type: ' . $file['type']);
    }

    // Validate file size (10MB limit)
    if ($file['size'] > 10 * 1024 * 1024) {
        sendError('File size must be less than 10MB');
    }

    // Create upload directory if it doesn't exist
    $upload_dir = dirname(__DIR__) . '/uploads/medical_documents/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            sendError('Cannot create upload directory');
        }
    }

    // Generate unique filename
    $original_name = basename($file['name']);
    $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
    $filename = 'med_doc_' . time() . '_' . uniqid() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        sendError('Failed to save uploaded file');
    }

    // Store in database - USE NULL for event_id instead of 0
    $relative_path = 'uploads/medical_documents/' . $filename;
    
    $stmt = $conn->prepare("INSERT INTO medical_documents (pet_id, event_id, uploaded_by, original_name, file_path, file_size, description) VALUES (?, NULL, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        unlink($filepath);
        sendError('Database prepare error: ' . $conn->error);
    }

    // Use NULL for event_id - parameter count is now 6
    if (!$stmt->bind_param('isssis', $pet_id, $username, $original_name, $relative_path, $file['size'], $description)) {
        unlink($filepath);
        sendError('Database bind error: ' . $stmt->error);
    }

    if (!$stmt->execute()) {
        unlink($filepath);
        sendError('Database execute error: ' . $stmt->error);
    }

    $inserted_id = $conn->insert_id;
    
    // Success!
    sendSuccess('Document uploaded successfully! ID: ' . $inserted_id);

} catch (Exception $e) {
    sendError('Unexpected error: ' . $e->getMessage());
}

// Clean output buffer and send any remaining response
ob_end_flush();
?>