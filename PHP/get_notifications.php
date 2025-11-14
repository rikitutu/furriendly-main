<?php
// API endpoint to get user notifications
session_start();
require 'db_connect.php';

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$username = $_SESSION['username'];
$action = $_GET['action'] ?? 'get';

switch ($action) {
    case 'get':
        // Get unread notifications
        $notifications = getUserNotifications($username);
        echo json_encode(['notifications' => $notifications]);
        break;

    case 'mark_read':
        // Mark notification as read
        $notification_id = $_POST['notification_id'] ?? 0;
        if (markNotificationRead($notification_id, $username)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Failed to mark notification as read']);
        }
        break;

    case 'get_count':
        // Get count of unread notifications
        $count = getUnreadNotificationCount($username);
        echo json_encode(['count' => $count]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

// Function to get unread notifications for a user
function getUserNotifications($username) {
    global $conn;

    $stmt = $conn->prepare("
        SELECT n.*, e.event_title, e.event_date, e.start_time, e.location
        FROM notifications n
        JOIN events e ON n.event_id = e.id
        WHERE n.username = ? AND n.is_read = 0
        ORDER BY n.sent_at DESC
    ");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

    return $notifications;
}

// Function to mark notification as read
function markNotificationRead($notification_id, $username) {
    global $conn;

    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE id = ? AND username = ?
    ");
    $stmt->bind_param('is', $notification_id, $username);
    return $stmt->execute();
}

// Function to get count of unread notifications
function getUnreadNotificationCount($username) {
    global $conn;

    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM notifications
        WHERE username = ? AND is_read = 0
    ");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row['count'];
}
?>
