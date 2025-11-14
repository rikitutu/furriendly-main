<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../php/db_connect.php';

$isLoggedIn = isset($_SESSION['username']);
$user = null;
$isAdmin = false;

if ($isLoggedIn) {
    $username = $_SESSION['username'];
    $stmt = $conn->prepare("SELECT name, profile_pic, is_admin FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (isset($user['is_admin']) && $user['is_admin'] == 1) {
        $isAdmin = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FURRiendly | Events</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/events_popup.css">
    <link rel="stylesheet" href="css/events_grid.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/notifications.css">
</head>
<body>
    <!-- Navigation Bar -->
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

    <main class="content">
        <h2 class="page-title">Upcoming Events</h2>

        <!-- Events Grid -->
        <div class="events-grid">
            <?php
            $events_stmt = $conn->prepare("
                SELECT * FROM events 
                WHERE status = 'approved' AND event_date >= CURDATE() 
                ORDER BY event_date ASC
            ");
            $events_stmt->execute();
            $events_result = $events_stmt->get_result();
            $approved_events = $events_result->fetch_all(MYSQLI_ASSOC);
            
            if (empty($approved_events)): ?>
                <div class="no-events">
                    <div class="no-events-icon">🐕‍🦺</div>
                    <h3>No Upcoming Events</h3>
                    <p>Check back later for new pet-friendly events in your area!</p>
                    <a href="add_event.php" class="join-btn host-event-btn">
                        Host Your Own Event
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($approved_events as $index => $event): 
    $isFeatured = $index < 2;
    $eventDate = new DateTime($event['event_date']);
    
    // Check if user has pets attending this event
    $user_pets_attending = [];
    if ($isLoggedIn) {
        $attending_stmt = $conn->prepare("
            SELECT p.id, p.pet_name 
            FROM event_participants ep 
            JOIN pets p ON ep.pet_id = p.id 
            WHERE ep.event_id = ? AND ep.username = ? AND ep.status = 'joined'
        ");
        $attending_stmt->bind_param("is", $event['id'], $username);
        $attending_stmt->execute();
        $attending_result = $attending_stmt->get_result();
        $user_pets_attending = $attending_result->fetch_all(MYSQLI_ASSOC);
    }
?>
<div class="event-card <?php echo $isFeatured ? 'featured' : ''; ?>">
    <?php if ($isFeatured): ?>
        <div class="featured-badge">🌟 Featured</div>
    <?php endif; ?>
    
    <div class="event-date-badge">
        <?php echo $eventDate->format('M j'); ?>
    </div>
    
    <div class="event-header">
        <h3 class="event-title"><?php echo htmlspecialchars($event['event_title']); ?></h3>
        <span class="event-service">Free Service</span>
    </div>
    
    <div class="event-body">
        <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
        
        <!-- Show attending pets if any -->
        <?php if (!empty($user_pets_attending)): ?>
        <div class="attending-pets">
            <strong>🎯 Your pets attending:</strong>
            <div class="attending-pets-list">
                <?php foreach ($user_pets_attending as $attending_pet): ?>
                    <span class="attending-pet-tag"><?php echo htmlspecialchars($attending_pet['pet_name']); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="event-details">
            <div class="event-detail-item">
                <strong>📅 Date:</strong> <?php echo date('F j, Y', strtotime($event['event_date'])); ?>
            </div>
            <div class="event-detail-item">
                <strong>⏰ Time:</strong> <?php echo date('g:i A', strtotime($event['start_time'])); ?> - <?php echo date('g:i A', strtotime($event['end_time'])); ?>
            </div>
            <div class="event-detail-item">
                <strong>📍 Location:</strong> <?php echo htmlspecialchars($event['location']); ?>
            </div>
            <div class="event-detail-item">
                <strong>👤 Host:</strong> <?php echo htmlspecialchars($event['full_name']); ?>
            </div>
        </div>
        
        <?php $services = json_decode($event['services'], true); ?>
        <?php if (!empty($services)): ?>
        <div class="event-services">
            <strong>🩺 Services Offered:</strong>
            <div class="service-tags">
                <?php foreach ($services as $service): ?>
                    <span class="service-tag"><?php echo htmlspecialchars($service); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="event-footer">
        <?php if ($isLoggedIn): ?>
            <button class="join-btn" onclick="openPetSelection(<?php echo $event['id']; ?>)">
                <?php echo empty($user_pets_attending) ? 'Join This Event 🐾' : 'Add Another Pet 🐾'; ?>
            </button>
        <?php else: ?>
            <a href="auth.php" class="join-btn login-join-btn">
                Login to Join Event 🐾
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <div class="add-event-placeholder">
        <a href="add_event.php" class="add-event-static-btn">Add Event</a>
    </div>

    <!-- Pet Selection Modal -->
    <div id="petSelectionModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closePetSelection()">&times;</span>
            <h3>Select Your Pet</h3>
            <div id="petsContainer">
                <!-- Pets will be loaded here -->
            </div>
            <div id="noPetsMessage" class="no-pets-message" style="display: none;">
                <p>You don't have any pets registered yet.</p>
                <button class="add-pet-btn" onclick="redirectToAddPet()">Add Pet</button>
            </div>
            <button id="joinConfirmBtn" class="join-confirm-btn" disabled onclick="joinEvent()">Join Event</button>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content success-modal">
            <div class="success-icon">✅</div>
            <h3>Success!</h3>
            <p>You have successfully joined the event!</p>
            <button class="join-confirm-btn" onclick="closeSuccessModal()">OK</button>
        </div>
    </div>

    <script src="js/notifications.js"></script>
    <script src="js/events_popup.js"></script>
</body>
</html>
