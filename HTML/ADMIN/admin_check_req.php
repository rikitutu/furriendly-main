<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../../php/db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../../html/auth.php");
    exit;
}

// Default variables
$isLoggedIn = true;
$isAdmin = false;
$user = null;

// Fetch user info
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT name, profile_pic, is_admin FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Check if admin
if (isset($user['is_admin']) && $user['is_admin'] == 1) {
    $isAdmin = true;
} else {
    header("Location: ../../html/index.php");
    exit;
}

// Ensure event ID exists
if (!isset($_GET['event_id'])) {
    die("Event ID not provided.");
}

$eventId = $_GET['event_id'];

// Fetch event details
$stmt = $conn->prepare("SELECT e.*, u.name AS uploader_name 
                        FROM events e 
                        JOIN users u ON e.host_username = u.username 
                        WHERE e.id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $eventId);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    die("Event not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Event Requirements | FURRiendly</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/add_event.css">
    <link rel="stylesheet" href="../css/notifications.css">
    <style>
    label::after {
        content: none !important;
    }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <header>
        <nav class="navbar">
            <div class="nav-left">
                <h4>FURRiendly</h4>
            </div>
            <ul class="nav-links">
                <li><a href="../index.php">Home</a></li>
                <li class="events-dropdown">
                    <a href="../events.php" class="events-link">Events</a>
                    <div class="events-dropdown-content">
                        <a href="../events.php?filter=upcoming">Upcoming Events</a>
                        <a href="../joined_events.php">Joined Events</a>
                    </div>
                </li>
                <li><a href="../contact.php">Contact</a></li>
                <?php if ($isAdmin): ?>
                    <li><a href="admin_dashboard.php" class="active">Admin</a></li>
                <?php endif; ?>
            </ul>
            <div class="auth-butt">
                <?php if ($isLoggedIn && $user): ?>
                    <div class="user-dropdown">
                        <a href="#" class="profile-link">
                            <img src="../<?php echo htmlspecialchars(!empty($user['profile_pic']) ? $user['profile_pic'] : 'images/default-avatar.png'); ?>" alt="Profile">
                            <span class="profile-name"><?php echo htmlspecialchars($user['name']); ?></span>
                        </a>
                        <div class="dropdown-content">
                            <a href="../dashboard.php">Edit Profile</a>
                            <a href="../your_pet.php">Pet Profile</a>
                            <a href="../myhosting.php">My Hosting</a>
                            <hr>
                            <a href="../../php/logout.php">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="../auth.php" class="btn auth">Log-in/Sign-up</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main class="add-event-main">

        <div class="back-container">
            <a href="admin_dashboard.php" class="back-btn">
                <span class="back-arrow">←</span> Back
            </a>
        </div>

        <div class="add-event-container">
            <h1>Check Event Requirements</h1>

            <form class="add-event-form" style="opacity: 0.9;">
                <div class="form-group">
                    <label>Event Title</label>
                    <input type="text" value="<?php echo htmlspecialchars($event['event_title']); ?>" readonly>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Event Date</label>
                        <input type="text" value="<?php echo htmlspecialchars($event['event_date']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="text" value="<?php echo htmlspecialchars($event['start_time']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="text" value="<?php echo htmlspecialchars($event['end_time']); ?>" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label>Location</label>
                    <input type="text" value="<?php echo htmlspecialchars($event['location']); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Services</label>
                    <input type="text" value="<?php echo htmlspecialchars($event['services']); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea readonly><?php echo htmlspecialchars($event['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" value="<?php echo htmlspecialchars($event['full_name']); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" value="<?php echo htmlspecialchars($event['contact_number']); ?>" readonly>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" value="<?php echo htmlspecialchars($event['position']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Host Username</label>
                        <input type="text" value="<?php echo htmlspecialchars($event['host_username']); ?>" readonly>
                    </div>
                </div>

                <hr style="margin: 30px 0;">

                <h2>Uploaded Requirements</h2>
                <div class="upload-section">
                    <?php
                    $requirements = [
                        'valid_id' => 'Valid ID',
                        'permit' => 'Permit or Authorization',
                        'veterinarians_list' => 'List of Veterinarians and Partners',
                        'safety_plan' => 'Safety and Cleanliness Plan',
                        'id_upload' => 'Additional ID Upload'
                    ];

                    foreach ($requirements as $key => $label):
                        if (!empty($event[$key])): ?>
                            <div class="form-group">
                                <label><?php echo $label; ?></label><br>
                                <a href="../../uploads/events/<?php echo htmlspecialchars($event[$key]); ?>" 
                                    target="_blank" 
                                    class="btn" 
                                    style="background: #007bff; color: white; padding: 5px 10px; border-radius: 5px;">
                                    View
                                </a>
                            </div>
                        <?php endif;
                    endforeach; ?>
                </div>
            </form>

            <?php if ($event['status'] === 'pending'): ?>
            <div class="form-actions" style="text-align:center; margin-top:25px;">
                <button type="button"
                    onclick="updateEventStatus(<?php echo $eventId; ?>, 'approved')"
                    style="background:#28a745;color:white;padding:10px 25px;border:none;border-radius:8px;cursor:pointer;font-size:16px;">
                    Approve
                </button>

                <button type="button"
                    onclick="rejectEvent(<?php echo $eventId; ?>)"
                    style="background:#dc3545;color:white;padding:10px 25px;border:none;border-radius:8px;cursor:pointer;font-size:16px;margin-left:10px;">
                    Reject
                </button>
            </div>
            <?php else: ?>
            <div class="form-actions" style="text-align:center; margin-top:25px;">
                <p style="color: #666; font-style: italic;">This event has already been <?php echo $event['status']; ?>.</p>
            </div>
            <?php endif; ?>


        </div>
    </main>
    <!-- Reject Reason Modal -->
    <div id="rejectModal" class="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);justify-content:center;align-items:center;">
        <div class="modal-content" style="background:white;padding:20px;border-radius:10px;width:400px;position:relative;">
            <span id="closeRejectModal" style="position:absolute;top:10px;right:15px;cursor:pointer;font-weight:bold;font-size:20px;">&times;</span>
            <h3>Reject Event</h3>
            <p>Please enter the reason for rejection:</p>
            <textarea id="rejectReason" rows="4" style="width:100%;padding:8px;"></textarea>
            <button id="submitReject" style="background:#dc3545;color:white;padding:10px 20px;border:none;border-radius:5px;margin-top:10px;cursor:pointer;">Submit</button>
        </div>
    </div>

    <script src="admin_dashboard.js"></script>
    <script src="../js/notifications.js"></script>

</body>
</html>
