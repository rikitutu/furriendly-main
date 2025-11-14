<?php
// Event reminder notification system
// This script should be run daily via cron job to send reminders 1 day before events

require 'db_connect.php';

function sendEventReminders() {
    // Get events happening tomorrow
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $current_time = date('H:i:s');

    // Find events starting tomorrow
    $stmt = $conn->prepare("
        SELECT e.id, e.event_title, e.event_date, e.start_time, e.location,
               ep.username, u.email, u.name
        FROM events e
        JOIN event_participants ep ON e.id = ep.event_id
        JOIN users u ON ep.username = u.username
        WHERE e.event_date = ?
        AND e.status = 'approved'
        AND ep.status = 'joined'
    ");
    $stmt->bind_param('s', $tomorrow);
    $stmt->execute();
    $result = $stmt->get_result();

    $reminders_sent = 0;

    while ($row = $result->fetch_assoc()) {
        // Check if reminder already sent for this event and user
        $check_stmt = $conn->prepare("
            SELECT id FROM notifications
            WHERE event_id = ? AND username = ? AND notification_type = 'event_reminder'
        ");
        $check_stmt->bind_param('is', $row['id'], $row['username']);
        $check_stmt->execute();

        if ($check_stmt->get_result()->num_rows == 0) {
            // Send reminder notification
            $message = "Reminder: Event '{$row['event_title']}' is tomorrow at {$row['start_time']} in {$row['location']}.";

            // Insert notification into database
            $insert_stmt = $conn->prepare("
                INSERT INTO notifications (event_id, username, notification_type, message)
                VALUES (?, ?, 'event_reminder', ?)
            ");
            $insert_stmt->bind_param('iss', $row['id'], $row['username'], $message);

            if ($insert_stmt->execute()) {
                // Here you could add email notification if email system is implemented
                // For now, we'll just log to database
                $reminders_sent++;
                echo "Reminder sent to {$row['username']} for event '{$row['event_title']}'\n";
            }
        }
    }

    return $reminders_sent;
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

// Run reminders if called directly
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    $sent = sendEventReminders();
    echo "Sent $sent event reminders.\n";
}
?>
