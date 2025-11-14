<?php
session_start();
require '../../php/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Check admin privileges
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || $user['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    // Update past approved events to completed
    $update_stmt = $conn->prepare("
        UPDATE events 
        SET status = 'completed' 
        WHERE status = 'approved' 
        AND event_date < CURDATE()
    ");
    
    if ($update_stmt->execute()) {
        $completed_count = $conn->affected_rows;
        echo json_encode([
            'success' => true, 
            'completed_count' => $completed_count,
            'message' => "Updated {$completed_count} events to completed"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>