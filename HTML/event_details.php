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

// Get event ID from URL
$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch event details - REMOVED the status filter so completed events can be viewed
$event = null;
if ($event_id > 0) {
    $stmt = $conn->prepare("
        SELECT * FROM events 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
}

// If event not found, redirect to events page
if (!$event) {
    header("Location: events.php");
    exit();
}

// Check if user has joined this event and get their participation status
$has_joined = false;
$participant_status = '';
$joined_pet_id = null;
$joined_pet_name = null;

if ($isLoggedIn) {
    $stmt = $conn->prepare("
        SELECT ep.status, ep.pet_id, p.pet_name 
        FROM event_participants ep 
        LEFT JOIN pets p ON ep.pet_id = p.id 
        WHERE ep.event_id = ? AND ep.username = ?
    ");
    $stmt->bind_param("is", $event_id, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($participant = $result->fetch_assoc()) {
        $has_joined = true;
        $participant_status = $participant['status'];
        $joined_pet_id = $participant['pet_id'];
        $joined_pet_name = $participant['pet_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['event_title']); ?> - FURRiendly</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/event_details.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/notifications.css">
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

    <main class="event-details-main">
        <div class="back-container">
            <a href="javascript:history.back()" class="back-btn">
                <span class="back-arrow">←</span> Back
            </a>
        </div>

        <div class="event-details-container">
            <div class="event-header">
                <h1><?php echo htmlspecialchars($event['event_title']); ?></h1>
                
                <!-- Event Status Badge -->
                <div class="event-status-badges">
                    <?php if ($event['status'] === 'completed'): ?>
                        <span class="event-status-badge status-completed">Completed Event</span>
                    <?php elseif ($event['status'] === 'approved'): ?>
                        <span class="event-status-badge status-approved">Upcoming Event</span>
                    <?php elseif ($event['status'] === 'pending'): ?>
                        <span class="event-status-badge status-pending">Pending Approval</span>
                    <?php elseif ($event['status'] === 'cancelled'): ?>
                        <span class="event-status-badge status-cancelled">Cancelled</span>
                    <?php endif; ?>
                    
                    <!-- User Participation Status -->
                    <?php if ($has_joined): ?>
                        <span class="event-status-badge status-<?php echo $participant_status; ?>">
                            You <?php echo ucfirst($participant_status); ?>
                            <?php if ($joined_pet_name): ?>
                                with <?php echo htmlspecialchars($joined_pet_name); ?>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="event-content">
                <div class="event-info">
                    <div class="info-section">
                        <h3>📝 Description</h3>
                        <p><?php echo htmlspecialchars($event['description']); ?></p>
                    </div>

                    <div class="info-section">
                        <h3>📅 Event Details</h3>
                        <div class="details-grid">
                            <div class="detail-item">
                                <strong>Date:</strong>
                                <span><?php echo date('F j, Y', strtotime($event['event_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Time:</strong>
                                <span><?php echo date('g:i A', strtotime($event['start_time'])); ?> - <?php echo date('g:i A', strtotime($event['end_time'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Location:</strong>
                                <span><?php echo htmlspecialchars($event['location']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Event Status:</strong>
                                <span class="event-status-text status-<?php echo $event['status']; ?>">
                                    <?php echo ucfirst($event['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="info-section">
                        <h3>👤 Host Information</h3>
                        <div class="details-grid">
                            <div class="detail-item">
                                <strong>Name:</strong>
                                <span><?php echo htmlspecialchars($event['full_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Position:</strong>
                                <span><?php echo htmlspecialchars($event['position']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Contact:</strong>
                                <span><?php echo htmlspecialchars($event['contact_number']); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php 
                    $services = json_decode($event['services'], true) ?? [];
                    if (!empty($services)): 
                    ?>
                    <div class="info-section">
                        <h3>🩺 Services Offered</h3>
                        <div class="service-tags">
                            <?php foreach ($services as $service): ?>
                                <span class="service-tag"><?php echo htmlspecialchars($service); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Additional info for completed events -->
                    <?php if ($event['status'] === 'completed' && $has_joined && $participant_status === 'completed'): ?>
                    <div class="info-section completed-event-info">
                        <h3>✅ Event Completed</h3>
                        <div class="details-grid">
                            <div class="detail-item">
                                <strong>Your Participation:</strong>
                                <span>Successfully completed this event</span>
                            </div>
                            <div class="detail-item">
                                <strong>Attended With:</strong>
                                <span>
                                    <?php echo $joined_pet_name ? htmlspecialchars($joined_pet_name) : 'Your pet'; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <strong>Completed On:</strong>
                                <span><?php echo date('F j, Y', strtotime($event['event_date'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="event-sidebar">
                    <?php if (!$has_joined && $isLoggedIn && $event['status'] === 'approved'): ?>
                        <div class="action-card">
                            <h3>Join This Event</h3>
                            <p>Ready to join this event with your pet?</p>
                            <button class="join-btn" onclick="openPetSelection(<?php echo $event['id']; ?>)">
                                Join Event 🐾
                            </button>
                        </div>
                    <?php elseif ($has_joined && $participant_status === 'joined' && $event['status'] === 'approved'): ?>
                        <div class="action-card">
                            <h3>Event Joined</h3>
                            <p>You're participating in this event!</p>
                            <?php if ($joined_pet_name): ?>
                                <p><strong>With:</strong> <?php echo htmlspecialchars($joined_pet_name); ?></p>
                            <?php endif; ?>
                            <button class="cancel-btn" onclick="cancelJoinFromDetails(<?php echo $event['id']; ?>)">
                                Cancel Participation
                            </button>
                        </div>
                    <?php elseif ($has_joined && $participant_status === 'completed'): ?>
                        <div class="action-card completed-card">
                            <h3>🎉 Event Completed</h3>
                            <p>You successfully attended this event!</p>
                            <?php if ($joined_pet_name): ?>
                                <p><strong>Attended with:</strong> <?php echo htmlspecialchars($joined_pet_name); ?></p>
                            <?php endif; ?>
                            <div class="completion-badge">
                                ✅ Completed on <?php echo date('F j, Y', strtotime($event['event_date'])); ?>
                            </div>
                        </div>
                    <?php elseif (!$isLoggedIn && $event['status'] === 'approved'): ?>
                        <div class="action-card">
                            <h3>Join This Event</h3>
                            <p>Login to join this event with your pet!</p>
                            <a href="auth.php" class="join-btn login-join-btn">
                                Login to Join 🐾
                            </a>
                        </div>
                    <?php elseif ($event['status'] === 'completed'): ?>
                        <div class="action-card completed-card">
                            <h3>Event Completed</h3>
                            <p>This event has already taken place.</p>
                            <div class="event-completed-badge">
                                📅 Past Event
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="info-card">
                        <h3>📋 Event Requirements</h3>
                        <ul>
                            <li>Valid ID may be required</li>
                            <li>Pet vaccination records</li>
                            <li>Follow safety guidelines</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Pet Selection Modal (same as events.php) -->
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

    <script src="js/notifications.js"></script>
    <script src="js/event_details.js"></script>
</body>
</html>
