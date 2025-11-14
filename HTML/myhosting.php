<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../php/db_connect.php';

if (!isset($_SESSION['username'])) {
    header('Location: auth.php');
    exit();
}

$username = $_SESSION['username'];
$user = null;
$isAdmin = false;

$stmt = $conn->prepare("SELECT name, profile_pic, is_admin FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if user is admin
if (isset($user['is_admin']) && $user['is_admin'] == 1) {
    $isAdmin = true;
}

// Get events hosted by user
$events_stmt = $conn->prepare("
    SELECT * FROM events 
    WHERE host_username = ? 
    ORDER BY 
        CASE status 
            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'rejected' THEN 3
            WHEN 'completed' THEN 4
        END,
        event_date ASC
");
$events_stmt->bind_param("s", $username);
$events_stmt->execute();
$events_result = $events_stmt->get_result();
$hosted_events = $events_result->fetch_all(MYSQLI_ASSOC);

// Get participant counts for each event
$participant_counts = [];
if (!empty($hosted_events)) {
    $event_ids = array_column($hosted_events, 'id');
    $placeholders = str_repeat('?,', count($event_ids) - 1) . '?';
    
    $count_stmt = $conn->prepare("
        SELECT event_id, COUNT(*) as count 
        FROM event_participants 
        WHERE event_id IN ($placeholders) AND status IN ('joined', 'completed')
        GROUP BY event_id
    ");
    $count_stmt->bind_param(str_repeat('i', count($event_ids)), ...$event_ids);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    
    while ($row = $count_result->fetch_assoc()) {
        $participant_counts[$row['event_id']] = $row['count'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Hosting - FURRiendly</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/myhosting.css">
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
                <?php if ($user): ?>
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

    <main class="myhosting-main">
        <div class="back-container">
            <a href="events.php" class="back-btn">
                <span class="back-arrow">←</span> Back to Events
            </a>
        </div>

        <div class="myhosting-container">
            <h1>My Hosted Events</h1>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-msg"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-msg"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="hosting-stats">
                <div class="stat-card pending">
                    <h3>Pending</h3>
                    <p><?php echo count(array_filter($hosted_events, fn($e) => $e['status'] === 'pending')); ?></p>
                </div>
                <div class="stat-card approved">
                    <h3>Approved</h3>
                    <p><?php echo count(array_filter($hosted_events, fn($e) => $e['status'] === 'approved')); ?></p>
                </div>
                <div class="stat-card completed">
                    <h3>Completed</h3>
                    <p><?php echo count(array_filter($hosted_events, fn($e) => $e['status'] === 'completed')); ?></p>
                </div>
                <div class="stat-card rejected">
                    <h3>Rejected</h3>
                    <p><?php echo count(array_filter($hosted_events, fn($e) => $e['status'] === 'rejected')); ?></p>
                </div>
            </div>

            <div class="events-tabs">
                <button class="tab-btn active" onclick="filterEvents('all')">All Events</button>
                <button class="tab-btn" onclick="filterEvents('pending')">Pending</button>
                <button class="tab-btn" onclick="filterEvents('approved')">Approved</button>
                <button class="tab-btn" onclick="filterEvents('completed')">Completed</button>
                <button class="tab-btn" onclick="filterEvents('rejected')">Rejected</button>
            </div>

            <div class="events-grid">
                <?php if (empty($hosted_events)): ?>
                    <div class="no-events">
                        <div class="no-events-icon">🏠</div>
                        <h3>No Hosted Events</h3>
                        <p>You haven't hosted any events yet. Start by creating your first event!</p>
                        <a href="add_event.php" class="action-btn view-btn">Host an Event</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($hosted_events as $event): 
                        $participant_count = $participant_counts[$event['id']] ?? 0;
                    ?>
                    <div class="event-card" data-status="<?php echo $event['status']; ?>">
                        <div class="event-header">
                            <h3 class="event-title"><?php echo htmlspecialchars($event['event_title']); ?></h3>
                            <span class="event-status status-<?php echo $event['status']; ?>">
                                <?php echo ucfirst($event['status']); ?>
                            </span>
                        </div>
                        
                        <div class="event-details">
                            <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
                            
                            <div class="event-meta">
                                <div class="event-meta-item">
                                    <strong>Date:</strong> <?php echo date('F j, Y', strtotime($event['event_date'])); ?>
                                </div>
                                <div class="event-meta-item">
                                    <strong>Time:</strong> <?php echo date('g:i A', strtotime($event['start_time'])); ?> - <?php echo date('g:i A', strtotime($event['end_time'])); ?>
                                </div>
                                <div class="event-meta-item">
                                    <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?>
                                </div>
                                <div class="event-meta-item">
                                    <strong>Participants:</strong> <?php echo $participant_count; ?> joined
                                </div>
                            </div>
                            
                            <?php $services = json_decode($event['services'], true); ?>
                            <?php if (!empty($services)): ?>
                            <div class="event-services">
                                <span class="services-label">Services:</span>
                                <div class="service-tags">
                                    <?php foreach ($services as $service): ?>
                                        <span class="service-tag"><?php echo htmlspecialchars($service); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="event-actions">
                            <span class="event-date">Submitted: <?php echo date('M j, Y g:i A', strtotime($event['created_at'])); ?></span>
                            <div class="action-buttons">
                                <?php $has_participants = $participant_count > 0; ?>

                                <?php if ($event['status'] === 'approved'): ?>
                                    <button class="action-btn complete-btn" onclick="markCompleted(<?php echo $event['id']; ?>, this)">Mark as Completed</button>
                                <?php endif; ?>

                                <?php if ($event['status'] === 'completed' && $has_participants): ?>
                                    <button class="action-btn completed-participants-btn" onclick="viewCompletedParticipants(<?php echo $event['id']; ?>)">
                                        🐾 View Participants
                                    </button>
                                <?php endif; ?>

                                <?php if ($event['status'] === 'completed' && !$has_participants): ?>
                                    <button class="action-btn no-participants-btn" disabled>No Participants</button>
                                <?php endif; ?>

                                <?php if ($event['status'] === 'rejected'): ?>
                                    <button class="action-btn appeal-btn" onclick="editEventForAppeal(<?php echo $event['id']; ?>)">Appeal</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Participants Modal -->
    <div id="participantsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeParticipantsModal()">&times;</span>
            <h3>Event Participants</h3>
            <div id="participantsContainer">
                <!-- Participants will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Appeal Modal -->
    <div id="appealModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeAppealModal()">&times;</span>
            <h3>Appeal Rejected Event</h3>

            <div class="appeal-container">
                <div class="rejection-reason">
                    <h4>Rejection Reason:</h4>
                    <p id="rejectionReasonText"></p>
                </div>

                <form id="appealForm">
                    <input type="hidden" id="appealEventId" name="event_id">

                    <div class="form-group">
                        <label for="appealMessage">Appeal Message *</label>
                        <textarea id="appealMessage" name="appeal_message" rows="4" placeholder="Explain why you believe this event should be approved and what changes you've made..." required></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="action-btn edit-event-btn" onclick="editEventForAppeal()">Edit Event Details</button>
                        <button type="submit" class="submit-btn">Submit Appeal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Upload Records Modal -->
    <div id="uploadRecordsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeUploadModal()">&times;</span>
            <h3>Upload Records for <span id="petNameHeader"></span></h3>
            <div class="upload-records-container">
                <form id="uploadRecordsForm" enctype="multipart/form-data">
                    <input type="hidden" id="uploadEventId" name="event_id">
                    <input type="hidden" id="uploadPetId" name="pet_id">
                    <input type="hidden" id="uploadParticipantId" name="participant_id">

                    <div class="form-group">
                        <label for="record_type">Record Type *</label>
                        <select id="record_type" name="record_type" required>
                            <option value="">Select Record Type</option>
                            <option value="vaccination">Vaccination Record</option>
                            <option value="medication">Medication Record</option>
                            <option value="treatment">Treatment Record</option>
                            <option value="checkup">Checkup Report</option>
                            <option value="other">Other Medical Record</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="record_description">Description</label>
                        <textarea id="record_description" name="record_description" rows="3" placeholder="Brief description of the record (optional)"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="record_file">Upload PDF File *</label>
                        <div class="file-upload-area" id="fileUploadArea">
                            <div class="upload-placeholder">
                                <span class="upload-icon">📄</span>
                                <span class="upload-text">Click to upload PDF file</span>
                                <small class="upload-hint">Max file size: 10MB</small>
                            </div>
                            <input type="file" id="record_file" name="record_file" accept=".pdf" required style="display: none;">
                        </div>
                        <div id="fileNameDisplay" class="file-name-display"></div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="submit-btn">Upload Record</button>
                    </div>
                </form>

                <div id="existingRecords" class="existing-records">
                    <h4>Existing Records</h4>
                    <div id="recordsList" class="records-list">
                        <!-- Existing records will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/notifications.js"></script>
    <script src="js/myhosting.js"></script>
</body>
</html>
