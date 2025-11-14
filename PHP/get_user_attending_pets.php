<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$event_id = $_GET['event_id'] ?? 0;
$username = $_SESSION['username'];

if (empty($event_id)) {
    echo json_encode(['success' => false, 'message' => 'Event ID required']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT p.id, p.pet_name
        FROM event_participants ep 
        JOIN pets p ON ep.pet_id = p.id 
        WHERE ep.event_id = ? AND ep.username = ? AND ep.status = 'joined'
    ");
    $stmt->bind_param("is", $event_id, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $attending_pets = [];
    while ($row = $result->fetch_assoc()) {
        $attending_pets[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'attending_pets' => $attending_pets
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>