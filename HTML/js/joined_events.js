// ===== JOINED EVENTS FUNCTIONALITY =====

// Cancel event participation
function cancelJoin(participantId) {
    if (confirm('Are you sure you want to cancel your participation in this event?')) {
        fetch('../php/cancel_event_join.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'participant_id=' + participantId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Successfully canceled event participation');
                // Redirect to canceled tab after cancellation
                window.location.href = 'joined_events.php?tab=canceled';
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while canceling the event');
        });
    }
}

// Rejoin a canceled event
function rejoinEvent(eventId) {
    if (confirm('Are you sure you want to rejoin this event?')) {
        fetch('../php/update_participation_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'event_id=' + eventId + '&status=joined'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Successfully rejoined the event!');
                window.location.href = 'joined_events.php?tab=joined';
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while rejoining the event');
        });
    }
}

// Cancel event participation from event details page
function cancelJoinFromDetails(eventId) {
    if (confirm('Are you sure you want to cancel your participation in this event?')) {
        fetch('../php/cancel_event_join_by_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'event_id=' + eventId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Successfully canceled event participation');
                window.location.href = 'joined_events.php?tab=canceled';
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while canceling the event');
        });
    }
}

// Initialize joined events functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('Joined events page loaded');
    
    // Add any initialization code here if needed
    // For example, adding event listeners to dynamically loaded content
});