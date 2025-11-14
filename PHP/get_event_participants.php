<?php
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
$username = $_SESSION['username'];

// Verify that the event belongs to the current user (as host)
$verify_stmt = $conn->prepare("SELECT id, event_title, event_date FROM events WHERE id = ? AND host_username = ?");
$verify_stmt->bind_param("is", $event_id, $username);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Event not found or access denied']);
    exit;
}

$event_info = $verify_result->fetch_assoc();

// Get participants with their pet information - FIXED QUERY
$query = "
    SELECT 
        ep.username,
        ep.joined_at,
        ep.status as participation_status,
        u.name as user_name,
        u.email as user_email,
        p.id as pet_id,
        p.pet_name,
        p.pet_species,
        p.pet_age,
        p.pet_breed,
        p.pet_gender,
        p.birthdate
    FROM event_participants ep
    JOIN users u ON ep.username = u.username
    JOIN pets p ON ep.pet_id = p.id  -- Changed from LEFT JOIN to JOIN and fixed join condition
    WHERE ep.event_id = ? 
    ORDER BY ep.joined_at ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

$participants = [];
while ($row = $result->fetch_assoc()) {
    // Calculate proper age from birthdate if available
    $age_display = '';
    if (!empty($row['birthdate'])) {
        $birthdate = new DateTime($row['birthdate']);
        $now = new DateTime();
        $age = $now->diff($birthdate);
        
        if ($age->y > 0) {
            $age_display = $age->y . ' years';
            if ($age->m > 0) {
                $age_display .= ', ' . $age->m . ' months';
            }
        } else if ($age->m > 0) {
            $age_display = $age->m . ' months';
        } else {
            $age_display = $age->d . ' days';
        }
    } else if (!empty($row['pet_age'])) {
        $age_display = $row['pet_age'] . ' years';
    } else {
        $age_display = 'Age not specified';
    }
    
    $participants[] = [
        'username' => $row['username'],
        'user_name' => $row['user_name'],
        'user_email' => $row['user_email'],
        'pet_id' => $row['pet_id'],
        'pet_name' => $row['pet_name'],
        'pet_species' => $row['pet_species'],
        'pet_age' => $age_display,
        'pet_breed' => $row['pet_breed'],
        'pet_gender' => $row['pet_gender'],
        'joined_at' => $row['joined_at'],
        'participation_status' => $row['participation_status']
    ];
}

echo json_encode([
    'success' => true,
    'event' => $event_info,
    'participants' => $participants
]);

$stmt->close();
?>