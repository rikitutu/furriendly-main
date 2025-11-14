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

$username = $_SESSION['username'];

// Check if enhanced columns exist
$check_columns = $conn->query("SHOW COLUMNS FROM pets LIKE 'pet_breed'");
$has_enhanced = $check_columns && $check_columns->num_rows > 0;

if ($has_enhanced) {
    // Get user's pets with enhanced schema
    $stmt = $conn->prepare("SELECT id, pet_name, pet_species, pet_breed, birthdate, pet_age, pet_gender, pet_profile_pic FROM pets WHERE username = ?");
} else {
    // Fallback to basic schema
    $stmt = $conn->prepare("SELECT id, pet_name, pet_species, pet_age FROM pets WHERE username = ?");
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

$pets = [];
while ($row = $result->fetch_assoc()) {
    // Calculate age properly
    $age_display = '';
    
    if ($has_enhanced && !empty($row['birthdate'])) {
        // Calculate age from birthdate
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
            if ($age->d > 0) {
                $age_display .= ', ' . $age->d . ' days';
            }
        } else {
            $age_display = $age->d . ' days';
        }
    } else if (!empty($row['pet_age'])) {
        // Use existing pet_age field as fallback
        $age_display = $row['pet_age'] . ' years';
    } else {
        $age_display = 'Age not specified';
    }
    
    // Ensure consistent response format
    $pets[] = [
        'id' => $row['id'],
        'pet_name' => $row['pet_name'],
        'pet_species' => $row['pet_species'],
        'pet_age' => $age_display, // Use calculated age
        'pet_breed' => $has_enhanced ? ($row['pet_breed'] ?? '') : '',
        'pet_gender' => $has_enhanced ? ($row['pet_gender'] ?? '') : '',
        'pet_profile_pic' => ($has_enhanced && !empty($row['pet_profile_pic']) && file_exists($row['pet_profile_pic'])) 
            ? $row['pet_profile_pic'] 
            : '../images/default-pet-avatar.png'
    ];
}

echo json_encode([
    'success' => true,
    'pets' => $pets,
    'has_enhanced' => $has_enhanced
]);
?>