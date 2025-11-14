<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../php/db_connect.php';

if (!isset($_SESSION['username'])) {
    header('Location: auth.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_SESSION['username'];
    
    // Handle file uploads
    $upload_dir = '../uploads/events/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    function uploadFile($file, $prefix) {
        global $upload_dir;
        if ($file['error'] === UPLOAD_ERR_OK) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $prefix . '_' . time() . '_' . uniqid() . '.' . $extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                return $filename;
            }
        }
        return null;
    }
    
    // Upload files
    $id_upload = null;
    if (!empty($_FILES['id_upload']['name'])) {
        $id_upload = uploadFile($_FILES['id_upload'], 'id');
    }
    
    $valid_id = uploadFile($_FILES['valid_id'], 'valid_id');
    $permit = uploadFile($_FILES['permit'], 'permit');
    $veterinarians_list = uploadFile($_FILES['veterinarians_list'], 'vets');
    $safety_plan = uploadFile($_FILES['safety_plan'], 'safety');
    
    // Validate date
    $event_date = $_POST['event_date'];
    $today = date('Y-m-d');
    if ($event_date <= $today) {
        $_SESSION['error'] = 'Please select a future date for your event.';
        header('Location: add_event.php');
        exit();
    }
    
    // Process services
    $services = json_decode($_POST['services'], true) ?? [];
    
    try {
        $stmt = $conn->prepare("INSERT INTO events (
            host_username, event_title, event_date, start_time, end_time, location, 
            services, description, full_name, contact_number, position, id_upload,
            valid_id, permit, veterinarians_list, safety_plan
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param(
            "ssssssssssssssss",
            $username,
            $_POST['event_title'],
            $_POST['event_date'],
            $_POST['start_time'],
            $_POST['end_time'],
            $_POST['location'],
            json_encode($services),
            $_POST['description'],
            $_POST['full_name'],
            $_POST['contact_number'],
            $_POST['position'],
            $id_upload,
            $valid_id,
            $permit,
            $veterinarians_list,
            $safety_plan
        );
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Event submitted successfully! It will be reviewed by our team.';
            header('Location: myhosting.php');
        } else {
            throw new Exception('Failed to submit event.');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error submitting event: ' . $e->getMessage();
        header('Location: add_event.php');
    }
} else {
    header('Location: add_event.php');
}
?>