<?php
session_start();
require 'db_connect.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if (!isset($_SESSION['username'])) {
        throw new Exception('Not authenticated');
    }

    if (!isset($_POST['event_id'])) {
        throw new Exception('Event ID required');
    }

    $event_id = intval($_POST['event_id']);
    $username = $_SESSION['username'];

    // Verify the event belongs to the current user and is approved
    $verify_stmt = $conn->prepare("SELECT id, status FROM events WHERE id = ? AND host_username = ? AND status = 'approved'");
    if (!$verify_stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $verify_stmt->bind_param("is", $event_id, $username);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();

    if ($verify_result->num_rows === 0) {
        throw new Exception('Event not found, access denied, or event is not in approved status');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update event status to completed
        $update_event_stmt = $conn->prepare("UPDATE events SET status = 'completed', completed_at = NOW() WHERE id = ?");
        if (!$update_event_stmt) {
            throw new Exception('Prepare failed for events update: ' . $conn->error);
        }
        $update_event_stmt->bind_param("i", $event_id);
        $update_event_stmt->execute();

        // Update participant statuses to completed
        $update_participants_stmt = $conn->prepare("UPDATE event_participants SET status = 'completed', completed_at = NOW() WHERE event_id = ? AND status = 'joined'");
        if (!$update_participants_stmt) {
            throw new Exception('Prepare failed for participants update: ' . $conn->error);
        }
        $update_participants_stmt->bind_param("i", $event_id);
        $update_participants_stmt->execute();

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Event successfully marked as completed! All participants have been updated.',
            'debug' => [
                'event_id' => $event_id,
                'participants_updated' => $update_participants_stmt->affected_rows
            ]
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'debug' => ['event_id' => $event_id ?? 'unknown']
    ]);
}
?>