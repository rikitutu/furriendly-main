// Notification system for event reminders
class NotificationManager {
    constructor() {
        this.notifications = [];
        this.unreadCount = 0;
        this.init();
    }

    init() {
        this.createNotificationUI();
        this.loadNotifications();
        this.setupEventListeners();
        // Check for new notifications every 30 seconds
        setInterval(() => this.loadNotifications(), 30000);
    }

    createNotificationUI() {
        // Add notification bell to navbar
        const navbar = document.querySelector('.navbar');
        if (!navbar) return;

        const notificationHTML = `
            <div class="notifications-container">
                <div class="notification-bell" id="notificationBell">
                    <i class="fas fa-bell"></i>
                    <span class="notification-count" id="notificationCount">0</span>
                </div>
                <div class="notifications-dropdown" id="notificationsDropdown">
                    <div class="notifications-header">Notifications</div>
                    <div id="notificationsList">
                        <div class="no-notifications">No new notifications</div>
                    </div>
                </div>
            </div>
        `;

        // Insert before the user dropdown (to the left of profile)
        const authButtons = navbar.querySelector('.auth-butt');
        if (authButtons) {
            const userDropdown = authButtons.querySelector('.user-dropdown');
            if (userDropdown) {
                userDropdown.insertAdjacentHTML('beforebegin', notificationHTML);
            } else {
                authButtons.insertAdjacentHTML('afterbegin', notificationHTML);
            }
        }
    }

    async loadNotifications() {
        try {
            const response = await fetch('../php/get_notifications.php?action=get');
            const data = await response.json();

            if (data.notifications) {
                this.notifications = data.notifications;
                this.updateUI();
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    updateUI() {
        const countElement = document.getElementById('notificationCount');
        const listElement = document.getElementById('notificationsList');

        this.unreadCount = this.notifications.length;

        // Update count badge
        if (this.unreadCount > 0) {
            countElement.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
            countElement.style.display = 'block';
        } else {
            countElement.style.display = 'none';
        }

        // Update notifications list
        if (this.notifications.length === 0) {
            listElement.innerHTML = '<div class="no-notifications">No new notifications</div>';
        } else {
            const notificationsHTML = this.notifications.map(notification => `
                <div class="notification-item unread" data-id="${notification.id}">
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-event-info">
                        Event: ${notification.event_title} on ${new Date(notification.event_date).toLocaleDateString()} at ${notification.start_time}
                    </div>
                    <div class="notification-time">${this.formatTime(notification.sent_at)}</div>
                    <button class="mark-read-btn" onclick="notificationManager.markAsRead(${notification.id})">Mark Read</button>
                </div>
            `).join('');

            listElement.innerHTML = notificationsHTML;
        }
    }

    setupEventListeners() {
        const bell = document.getElementById('notificationBell');
        const dropdown = document.getElementById('notificationsDropdown');

        if (bell) {
            bell.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('show');
            });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.notifications-container')) {
                dropdown.classList.remove('show');
            }
        });

        // Handle notification item clicks
        document.addEventListener('click', (e) => {
            if (e.target.closest('.notification-item')) {
                const item = e.target.closest('.notification-item');
                const notificationId = item.dataset.id;
                // Could navigate to event details or mark as read
            }
        });
    }

    async markAsRead(notificationId) {
        try {
            const formData = new FormData();
            formData.append('notification_id', notificationId);

            const response = await fetch('../php/get_notifications.php?action=mark_read', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                // Remove from local array and update UI
                this.notifications = this.notifications.filter(n => n.id != notificationId);
                this.updateUI();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;

        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        return `${days}d ago`;
    }
}

// Initialize notification manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.notificationManager = new NotificationManager();
});
