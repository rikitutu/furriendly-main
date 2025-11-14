<?php
// get_participants.php - FIXED VERSION
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (!isset($_GET['event_id'])) {
    echo json_encode(['success' => false, 'message' => 'Event ID required']);
    exit;
}

$event_id = intval($_GET['event_id']);
$host_username = $_SESSION['username'];

try {
    // Verify the user is the host of this event
    $verify_stmt = $conn->prepare("SELECT host_username FROM events WHERE id = ?");
    $verify_stmt->bind_param("i", $event_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Event not found']);
        exit;
    }
    
    $event_data = $verify_result->fetch_assoc();
    if ($event_data['host_username'] !== $host_username) {
        echo json_encode(['success' => false, 'message' => 'You are not the host of this event']);
        exit;
    }

    // Get participants with their pets - FIXED QUERY
    $participants_stmt = $conn->prepare("
        SELECT 
            ep.id as participant_id,
            ep.username,
            ep.pet_id,
            ep.joined_at,
            ep.status,
            u.name,
            u.email,
            u.profile_pic,
            p.id as pet_db_id,
            p.pet_name,
            p.pet_species,
            p.pet_breed,
            p.pet_gender,
            p.birthdate,
            p.pet_age,
            p.pet_profile_pic
        FROM event_participants ep
        LEFT JOIN users u ON ep.username = u.username
        LEFT JOIN pets p ON ep.pet_id = p.id
        WHERE ep.event_id = ? AND ep.status IN ('joined', 'completed')
        ORDER BY ep.joined_at DESC
    ");
    
    $participants_stmt->bind_param("i", $event_id);
    $participants_stmt->execute();
    $participants_result = $participants_stmt->get_result();
    
    $participants = [];
    $processed_users = [];
    
    while ($row = $participants_result->fetch_assoc()) {
        $username = $row['username'];
        
        // Initialize user if not processed yet
        if (!isset($processed_users[$username])) {
            $processed_users[$username] = [
                'id' => $row['participant_id'],
                'username' => $username,
                'name' => $row['name'] ?? $username,
                'email' => $row['email'] ?? '',
                'profile_pic' => $row['profile_pic'] ?? '../images/default-avatar.png',
                'joined_at' => $row['joined_at'],
                'status' => $row['status'],
                'pets' => []
            ];
        }
        
        // Add pet if exists
        if ($row['pet_id'] && $row['pet_db_id']) {
            $processed_users[$username]['pets'][] = [
                'id' => $row['pet_db_id'], // This is the crucial field
                'pet_id' => $row['pet_db_id'], // Duplicate for compatibility
                'pet_name' => $row['pet_name'],
                'pet_species' => $row['pet_species'],
                'pet_breed' => $row['pet_breed'],
                'pet_gender' => $row['pet_gender'],
                'birthdate' => $row['birthdate'],
                'age' => $row['pet_age'],
                'pet_profile_pic' => $row['pet_profile_pic'] ?? '../images/default-pet.png'
            ];
        }
    }
    
    $participants = array_values($processed_users);
    
    echo json_encode([
        'success' => true,
        'participants' => $participants,
        'count' => count($participants)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>