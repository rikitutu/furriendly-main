// ==========================
// ADMIN DASHBOARD JAVASCRIPT
// ==========================

function checkRequirements(eventId) {
    window.location.href = `admin_check_req.php?event_id=${eventId}`;
}

// Filter events by status
function filterEvents(status) {
    const events = document.querySelectorAll('.event-card');
    const tabBtns = document.querySelectorAll('.tab-btn');

    // Update active tab
    tabBtns.forEach(btn => {
        if (btn.textContent.toLowerCase().includes(status)) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    // Show/hide events based on status
    events.forEach(event => {
        if (status === 'all' || event.dataset.status === status) {
            event.style.display = 'block';
            setTimeout(() => {
                event.style.opacity = '1';
                event.style.transform = 'translateY(0)';
            }, 50);
        } else {
            event.style.opacity = '0';
            event.style.transform = 'translateY(20px)';
            setTimeout(() => {
                event.style.display = 'none';
            }, 300);
        }
    });
}

// ==========================
// ADMIN EVENT ACTIONS
// ==========================

let currentRejectEventId = null;

function updateEventStatus(eventId, status, reason = null) {
    if (!confirm(`Are you sure you want to ${status} this event?`)) return;

    let body = `event_id=${eventId}&status=${status}`;
    if (reason) body += `&reason=${encodeURIComponent(reason)}`;

    fetch('update_event_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    })
    .then(res => res.json())
    .then(data => {
        showNotification(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            // Redirect to admin_dashboard after 1.5s
            setTimeout(() => {
                window.location.href = 'admin_dashboard.php';
            }, 1500);
        }
    })
    .catch(() => showNotification("Error updating event status.", 'error'));
}

function rejectEvent(eventId) {
    currentRejectEventId = eventId;
    document.getElementById('rejectModal').style.display = 'flex';
}

// Close modal
document.getElementById('closeRejectModal').onclick = () => {
    document.getElementById('rejectModal').style.display = 'none';
    document.getElementById('rejectReason').value = '';
};

// Submit rejection
document.getElementById('submitReject').onclick = () => {
    const reason = document.getElementById('rejectReason').value.trim();
    if (!reason) {
        alert("Please enter a reason.");
        return;
    }

    updateEventStatus(currentRejectEventId, 'rejected', reason);
    document.getElementById('rejectModal').style.display = 'none';
};

// ==========================
// NOTIFICATION SYSTEM
// ==========================

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 8px;
        color: white;
        font-weight: bold;
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;

    notification.style.background =
        type === 'success'
            ? 'linear-gradient(135deg, #28a745, #6fcf97)'
            : 'linear-gradient(135deg, #dc3545, #ff7676)';

    document.body.appendChild(notification);

    setTimeout(() => notification.remove(), 3000);
}

// Add CSS animation for notification
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
`;
document.head.appendChild(style);

// ==========================
// INITIALIZE
// ==========================
document.addEventListener('DOMContentLoaded', function() {
    const events = document.querySelectorAll('.event-card');
    events.forEach((event, index) => {
        event.style.transition = 'all 0.3s ease';
        event.style.opacity = '0';
        event.style.transform = 'translateY(20px)';

        setTimeout(() => {
            event.style.opacity = '1';
            event.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Auto-complete past events (run on page load)
function autoCompletePastEvents() {
    fetch('auto_complete_events.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    })
    .then(res => res.json())
    .then(data => {
        if (data.completed_count > 0) {
            console.log(`Auto-completed ${data.completed_count} past events`);
        }
    })
    .catch(err => console.error('Auto-complete error:', err));
}

// Call this on admin dashboard load
document.addEventListener('DOMContentLoaded', function() {
    autoCompletePastEvents();
    // ... your existing initialization code
});