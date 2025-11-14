<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../php/db_connect.php';

$isLoggedIn = isset($_SESSION['username']);
$user = null;
$isAdmin = false;

if ($isLoggedIn) {
    $username = $_SESSION['username'];
    $stmt = $conn->prepare("SELECT id, name, profile_pic, is_admin FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (isset($user['is_admin']) && $user['is_admin'] == 1) {
        $isAdmin = true;
    }
}

// Get the active tab from URL parameter
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'joined';

// Fetch events based on active tab
$joined_events = [];
$completed_events = [];
$canceled_events = [];

if ($isLoggedIn && $user) {
    $username = $_SESSION['username'];
    
    // Check if event_participants table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'event_participants'");
    
    if ($check_table && $check_table->num_rows > 0) {
        // Check if pets table has enhanced columns
        $check_pet_columns = $conn->query("SHOW COLUMNS FROM pets LIKE 'birthdate'");
        $has_enhanced_pets = $check_pet_columns && $check_pet_columns->num_rows > 0;
        
        // SIMPLIFIED Base query - removed the complex column selection
        $base_query = "
            SELECT 
                ep.id as participant_id,
                ep.joined_at,
                ep.status as participant_status,
                e.id as event_id,
                e.event_title,
                e.description,
                e.event_date,
                e.start_time,
                e.end_time,
                e.location,
                e.services,
                e.full_name as host_name,
                p.id as pet_id,
                p.pet_name,
                p.pet_species,
                p.pet_age
        ";
        
        // Add enhanced columns if they exist
        if ($has_enhanced_pets) {
            $base_query .= ", p.birthdate, p.pet_breed, p.pet_gender";
        } else {
            $base_query .= ", NULL as birthdate, '' as pet_breed, '' as pet_gender";
        }
        
        $base_query .= "
            FROM event_participants ep
            JOIN events e ON ep.event_id = e.id
            LEFT JOIN pets p ON ep.pet_id = p.id
            WHERE ep.username = ?
        ";
        
        // Fetch joined events
        $joined_query = $base_query . " AND ep.status = 'joined' ORDER BY e.event_date ASC, e.start_time ASC";
        $stmt = $conn->prepare($joined_query);
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                // Calculate age properly
                $age_display = calculatePetAge($row);
                
                $joined_events[] = [
                    'participant_id' => $row['participant_id'],
                    'event_id' => $row['event_id'],
                    'title' => $row['event_title'],
                    'description' => $row['description'],
                    'date' => date('F j, Y', strtotime($row['event_date'])),
                    'time' => date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time'])),
                    'location' => $row['location'],
                    'host' => $row['host_name'],
                    'services' => json_decode($row['services'], true) ?? [],
                    'pet' => [
                        'id' => $row['pet_id'],
                        'name' => $row['pet_name'],
                        'species' => $row['pet_species'],
                        'age' => $age_display,
                        'breed' => $row['pet_breed'] ?? '',
                        'gender' => $row['pet_gender'] ?? ''
                    ],
                    'joined_at' => $row['joined_at'],
                    'participant_status' => $row['participant_status']
                ];
            }
        }
        
        // Fetch completed events
        $completed_query = $base_query . " AND ep.status = 'completed' ORDER BY e.event_date DESC, e.start_time DESC";
        $stmt = $conn->prepare($completed_query);
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $age_display = calculatePetAge($row);
                
                $completed_events[] = [
                    'participant_id' => $row['participant_id'],
                    'event_id' => $row['event_id'],
                    'title' => $row['event_title'],
                    'description' => $row['description'],
                    'date' => date('F j, Y', strtotime($row['event_date'])),
                    'time' => date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time'])),
                    'location' => $row['location'],
                    'host' => $row['host_name'],
                    'services' => json_decode($row['services'], true) ?? [],
                    'pet' => [
                        'id' => $row['pet_id'],
                        'name' => $row['pet_name'],
                        'species' => $row['pet_species'],
                        'age' => $age_display,
                        'breed' => $row['pet_breed'] ?? '',
                        'gender' => $row['pet_gender'] ?? ''
                    ],
                    'joined_at' => $row['joined_at'],
                    'completed_at' => $row['joined_at'],
                    'participant_status' => $row['participant_status']
                ];
            }
        }
        
        // Fetch canceled events
        $canceled_query = $base_query . " AND ep.status = 'canceled' ORDER BY ep.joined_at DESC";
        $stmt = $conn->prepare($canceled_query);
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $age_display = calculatePetAge($row);
                
                $canceled_events[] = [
                    'participant_id' => $row['participant_id'],
                    'event_id' => $row['event_id'],
                    'title' => $row['event_title'],
                    'description' => $row['description'],
                    'date' => date('F j, Y', strtotime($row['event_date'])),
                    'time' => date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time'])),
                    'location' => $row['location'],
                    'host' => $row['host_name'],
                    'services' => json_decode($row['services'], true) ?? [],
                    'pet' => [
                        'id' => $row['pet_id'],
                        'name' => $row['pet_name'],
                        'species' => $row['pet_species'],
                        'age' => $age_display,
                        'breed' => $row['pet_breed'] ?? '',
                        'gender' => $row['pet_gender'] ?? ''
                    ],
                    'joined_at' => $row['joined_at'],
                    'canceled_at' => $row['joined_at'],
                    'participant_status' => $row['participant_status']
                ];
            }
        }
    }
}

// Helper function to calculate pet age
function calculatePetAge($row) {
    $age_display = '';
    
    if (!empty($row['birthdate']) && $row['birthdate'] != 'NULL') {
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
    
    return $age_display;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Events - FURRiendly</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/notifications.css">
    <link rel="stylesheet" href="css/joined_events.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-left">
                <h4>FURRiendly</h4>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li class="events-dropdown">
                    <a href="events.php" class="events-link">Events</a>
                    <div class="events-dropdown-content">
                        <a href="events.php?filter=upcoming">Upcoming Events</a>
                        <a href="joined_events.php">Joined Events</a>
                    </div>
                </li>
                <li><a href="contact.php">Contact</a></li>

                <!-- Show only if user is admin -->
                <?php if ($isAdmin): ?>
                    <li><a href="../html/ADMIN/admin_dashboard.php">Admin</a></li>
                <?php endif; ?>
            </ul>

            <div class="auth-butt">
                <?php if ($isLoggedIn && $user): ?>
                    <div class="user-dropdown">
                        <a href="#" class="profile-link">
                            <img src="<?php echo htmlspecialchars(!empty($user['profile_pic']) ? $user['profile_pic'] : '../images/default-avatar.png'); ?>" alt="Profile">
                            <span class="profile-name"><?php echo htmlspecialchars($user['name'] ?? $username); ?></span>
                        </a>
                        <div class="dropdown-content">
                            <a href="dashboard.php">Edit Profile</a>
                            <a href="your_pet.php">Pet Profile</a>
                            <a href="events.php">Events</a>
                            <a href="myhosting.php">My Hosting</a>
                            <hr>
                            <a href="../php/logout.php">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="auth.php" class="btn auth">Log-in/Sign-up</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main class="joined-events-main">
        <div class="back-container">
            <a href="events.php" class="back-btn">
                <span class="back-arrow">←</span> Back to Events
            </a>
        </div>

        <div class="joined-events-container">
            <h1>My Events</h1>
            
            <!-- Tabs Navigation -->
            <div class="events-tabs">
                <a href="?tab=joined" class="tab-btn <?php echo $active_tab == 'joined' ? 'active' : ''; ?>">
                    Joined Events
                </a>
                <a href="?tab=completed" class="tab-btn <?php echo $active_tab == 'completed' ? 'active' : ''; ?>">
                    Completed Events
                </a>
                <a href="?tab=canceled" class="tab-btn <?php echo $active_tab == 'canceled' ? 'active' : ''; ?>">
                    Canceled Events
                </a>
            </div>

            <!-- Tab Content -->
            <div class="tab-content">
                <?php if ($active_tab == 'joined'): ?>
                    <!-- Joined Events Tab -->
                    <div class="events-grid">
                        <?php include 'joined_events_content.php'; ?>
                    </div>
                
                <?php elseif ($active_tab == 'completed'): ?>
                    <!-- Completed Events Tab -->
                    <div class="events-grid">
                        <?php 
                        // Pass completed events to the content file
                        $events_to_display = $completed_events;
                        include 'completed_events_content.php'; 
                        ?>
                    </div>
                
                <?php elseif ($active_tab == 'canceled'): ?>
                    <!-- Canceled Events Tab -->
                    <div class="events-grid">
                        <?php 
                        // Pass canceled events to the content file
                        $events_to_display = $canceled_events;
                        include 'canceled_events_content.php'; 
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="js/notifications.js"></script>
    <script src="js/joined_events.js"></script>
</body>
</html>
