<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    $event_id = intval($_POST['event_id']);
    $username = $_SESSION['username'];
    
    // Debug: Log the request
    error_log("Rejoin event request - Event ID: $event_id, Username: $username");
    
    // Check if event exists and is approved
    $event_stmt = $conn->prepare("
        SELECT id, event_date FROM events 
        WHERE id = ? AND status = 'approved'
    ");
    $event_stmt->bind_param("i", $event_id);
    $event_stmt->execute();
    $event_result = $event_stmt->get_result();
    
    if ($event_result->num_rows === 0) {
        error_log("Event not found or not approved - Event ID: $event_id");
        echo json_encode(['success' => false, 'message' => 'Event not found or not approved']);
        exit;
    }
    
    $event = $event_result->fetch_assoc();
    
    // Check if event date is in the future
    $event_date = new DateTime($event['event_date']);
    $today = new DateTime();
    if ($event_date < $today) {
        error_log("Event date has passed - Event ID: $event_id");
        echo json_encode(['success' => false, 'message' => 'This event has already passed']);
        exit;
    }
    
    // Check if user already has a record for this event
    $check_stmt = $conn->prepare("
        SELECT id, status FROM event_participants 
        WHERE event_id = ? AND username = ?
    ");
    $check_stmt->bind_param("is", $event_id, $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing record to 'joined'
        $participant = $check_result->fetch_assoc();
        $update_stmt = $conn->prepare("
            UPDATE event_participants 
            SET status = 'joined', joined_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $update_stmt->bind_param("i", $participant['id']);
        
        if ($update_stmt->execute()) {
            error_log("Successfully updated participation record - Participant ID: " . $participant['id']);

            // Always send a rejoin confirmation notification
            $event_stmt = $conn->prepare("SELECT event_title, event_date, start_time, location FROM events WHERE id = ?");
            $event_stmt->bind_param("i", $event_id);
            $event_stmt->execute();
            $event_result = $event_stmt->get_result();
            $event_details = $event_result->fetch_assoc();

            if ($event_details) {
                $join_message = "You have successfully rejoined the event '{$event_details['event_title']}' scheduled for {$event_details['event_date']} at {$event_details['start_time']} in {$event_details['location']}.";

                $insert_join_notification = $conn->prepare("
                    INSERT INTO notifications (event_id, username, notification_type, message)
                    VALUES (?, ?, 'event_rejoin', ?)
                ");
                $insert_join_notification->bind_param("iss", $event_id, $username, $join_message);
                $insert_join_notification->execute();

                $event_datetime = $event_details['event_date'] . ' ' . ($event_details['start_time'] ?? '00:00:00');
                $hours_until_event = (strtotime($event_datetime) - time()) / 3600;

                // Send immediate reminder if event is within 24 hours
                if ($hours_until_event > 0 && $hours_until_event <= 24) {
                    // Check if reminder already exists for this event and user
                    $check_reminder = $conn->prepare("
                        SELECT id FROM notifications
                        WHERE event_id = ? AND username = ? AND notification_type = 'event_reminder'
                    ");
                    $check_reminder->bind_param("is", $event_id, $username);
                    $check_reminder->execute();

                    if ($check_reminder->get_result()->num_rows == 0) {
                        $reminder_message = "Event Reminder: '{$event_details['event_title']}' starts in " .
                            (floor($hours_until_event) > 0 ? floor($hours_until_event) . " hours" : "less than 1 hour") .
                            " at {$event_details['start_time']} in {$event_details['location']}.";

                        $insert_reminder = $conn->prepare("
                            INSERT INTO notifications (event_id, username, notification_type, message)
                            VALUES (?, ?, 'event_reminder', ?)
                        ");
                        $insert_reminder->bind_param("iss", $event_id, $username, $reminder_message);
                        $insert_reminder->execute();
                    }
                }
            }

            echo json_encode(['success' => true, 'message' => 'Successfully rejoined the event!']);
        } else {
            error_log("Database error updating participation: " . $update_stmt->error);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $update_stmt->error]);
        }
        $update_stmt->close();
    } else {
        // Create new participation record
        $insert_stmt = $conn->prepare("
            INSERT INTO event_participants (event_id, username, status) 
            VALUES (?, ?, 'joined')
        ");
        $insert_stmt->bind_param("is", $event_id, $username);
        
        if ($insert_stmt->execute()) {
            error_log("Successfully created new participation record");
            echo json_encode(['success' => true, 'message' => 'Successfully joined the event!']);
        } else {
            error_log("Database error inserting participation: " . $insert_stmt->error);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $insert_stmt->error]);
        }
        $insert_stmt->close();
    }
    
    // Close statements
    $event_stmt->close();
    $check_stmt->close();
    
} else {
    error_log("Invalid rejoin request - No event_id provided");
    echo json_encode(['success' => false, 'message' => 'Invalid request - Event ID required']);
}
?>