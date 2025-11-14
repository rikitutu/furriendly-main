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
$event_id = $_POST['event_id'] ?? 0;
$pet_id = $_POST['pet_id'] ?? 0;

// Validate inputs
if (empty($event_id) || empty($pet_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing event or pet information']);
    exit;
}

try {
    // Check if the event exists and is approved
    $stmt = $conn->prepare("SELECT id FROM events WHERE id = ? AND status = 'approved'");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Event not found or not approved']);
        exit;
    }

    // Check if the pet belongs to the user
    $stmt = $conn->prepare("SELECT id FROM pets WHERE id = ? AND username = ?");
    $stmt->bind_param("is", $pet_id, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Pet not found or does not belong to you']);
        exit;
    }

    // Check if this specific pet has already joined this event
    $stmt = $conn->prepare("SELECT id FROM event_participants WHERE event_id = ? AND pet_id = ? AND status = 'joined'");
    $stmt->bind_param("ii", $event_id, $pet_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This pet has already joined this event']);
        exit;
    }

    // Check if the user has other pets in this event (for informational purposes, but don't block)
    $stmt = $conn->prepare("SELECT COUNT(*) as other_pets FROM event_participants WHERE event_id = ? AND username = ? AND status = 'joined'");
    $stmt->bind_param("is", $event_id, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $other_pets = $result->fetch_assoc()['other_pets'];

    // If user already has pets in this event, send an additional notification
    if ($other_pets > 0) {
        $additional_join_message = "You have added another pet to the event '{$event['event_title']}' scheduled for {$event['event_date']} at {$event['start_time']} in {$event['location']}. You now have " . ($other_pets + 1) . " pets attending this event.";

        $insert_additional_notification = $conn->prepare("
            INSERT INTO notifications (event_id, username, notification_type, message)
            VALUES (?, ?, 'event_join', ?)
        ");
        $insert_additional_notification->bind_param("iss", $event_id, $username, $additional_join_message);
        $insert_additional_notification->execute();
    }

    // Insert the participation record
    $stmt = $conn->prepare("INSERT INTO event_participants (event_id, username, pet_id, status, joined_at) VALUES (?, ?, ?, 'joined', NOW())");
    $stmt->bind_param("isi", $event_id, $username, $pet_id);

    if ($stmt->execute()) {
        // Always send a join confirmation notification
        $event_stmt = $conn->prepare("SELECT event_title, event_date, start_time, location FROM events WHERE id = ?");
        $event_stmt->bind_param("i", $event_id);
        $event_stmt->execute();
        $event_result = $event_stmt->get_result();
        $event = $event_result->fetch_assoc();

        if ($event) {
            $join_message = "You have successfully joined the event '{$event['event_title']}' scheduled for {$event['event_date']} at {$event['start_time']} in {$event['location']}.";

            $insert_join_notification = $conn->prepare("
                INSERT INTO notifications (event_id, username, notification_type, message)
                VALUES (?, ?, 'event_join', ?)
            ");
            $insert_join_notification->bind_param("iss", $event_id, $username, $join_message);
            $insert_join_notification->execute();

            $event_datetime = $event['event_date'] . ' ' . ($event['start_time'] ?? '00:00:00');
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
                    $reminder_message = "Event Reminder: '{$event['event_title']}' starts in " .
                        (floor($hours_until_event) > 0 ? floor($hours_until_event) . " hours" : "less than 1 hour") .
                        " at {$event['start_time']} in {$event['location']}.";

                    $insert_reminder = $conn->prepare("
                        INSERT INTO notifications (event_id, username, notification_type, message)
                        VALUES (?, ?, 'event_reminder', ?)
                    ");
                    $insert_reminder->bind_param("iss", $event_id, $username, $reminder_message);
                    $insert_reminder->execute();
                }
            }
        }

        $message = 'Successfully joined the event!';
        if ($other_pets > 0) {
            $message .= " You now have " . ($other_pets + 1) . " pets attending this event.";
        }
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>