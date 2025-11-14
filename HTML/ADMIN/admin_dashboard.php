<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../../php/db_connect.php';

// Default values to prevent undefined variable warnings
$isLoggedIn = isset($_SESSION['username']);
$isAdmin = false;
$user = null;

// Redirect if not logged in
if (!$isLoggedIn) {
    header('Location: ../auth.php');
    exit();
}

$username = $_SESSION['username'];

// Fetch user info
$stmt = $conn->prepare("SELECT name, profile_pic, is_admin FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if user exists and is admin
if (!$user || $user['is_admin'] != 1) {
    header('Location: ../index.php');
    exit();
}

$isAdmin = ($user['is_admin'] == 1);

// Fetch all events for admin view
$events_query = "
    SELECT e.*, u.name AS host_name 
    FROM events e
    JOIN users u ON e.host_username = u.username
    ORDER BY 
        CASE e.status
            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'rejected' THEN 3
            WHEN 'completed' THEN 4
        END,
        e.event_date ASC
";
$events = $conn->query($events_query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FURRiendly</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/myhosting.css">
    <link rel="stylesheet" href="../css/notifications.css">
    <style>
        .check-btn {
            background: #007bff;
            color: #fff;
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: 0.3s;
        }
        .check-btn:hover { background: #0056b3; }
        .approve-btn { background: #28a745; }
        .reject-btn { background: #dc3545; }
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

                <!-- Show only if user is admin -->
                <?php if ($isAdmin): ?>
                    <li><a href="admin_dashboard.php" class="active">Admin</a></li>
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

    <main class="myhosting-main">
        <div class="myhosting-container">
            <h1>Admin Dashboard</h1>

            <div class="hosting-stats">
                <div class="stat-card pending">
                    <h3>Pending</h3>
                    <p><?php echo count(array_filter($events, fn($e) => $e['status'] === 'pending')); ?></p>
                </div>
                <div class="stat-card approved">
                    <h3>Approved</h3>
                    <p><?php echo count(array_filter($events, fn($e) => $e['status'] === 'approved')); ?></p>
                </div>
                <div class="stat-card completed">
                    <h3>Completed</h3>
                    <p><?php echo count(array_filter($events, fn($e) => $e['status'] === 'completed')); ?></p>
                </div>
                <div class="stat-card rejected">
                    <h3>Rejected</h3>
                    <p><?php echo count(array_filter($events, fn($e) => $e['status'] === 'rejected')); ?></p>
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
                <?php if (empty($events)): ?>
                    <div class="no-events">
                        <div class="no-events-icon">📂</div>
                        <h3>No Events Found</h3>
                        <p>There are no events yet submitted by users.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
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
                                    <div><strong>Host:</strong> <?php echo htmlspecialchars($event['host_name']); ?></div>
                                    <div><strong>Date:</strong> <?php echo date('F j, Y', strtotime($event['event_date'])); ?></div>
                                    <div><strong>Time:</strong> <?php echo date('g:i A', strtotime($event['start_time'])); ?> - <?php echo date('g:i A', strtotime($event['end_time'])); ?></div>
                                    <div><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></div>
                                </div>
                            </div>

                            <div class="event-actions">
                                <button class="check-btn" onclick="checkRequirements(<?php echo $event['id']; ?>)">View Documents</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="admin_dashboard.js"></script>
    <script src="../js/notifications.js"></script>
</body>
</html>
