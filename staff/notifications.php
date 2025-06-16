<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../index.php');
    exit();
}

$pageTitle = "Notifications";
ob_start();
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title">Notifications</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="markAllAsRead()">
                        Mark All as Read
                    </button>
                </div>
                <div id="notifications-list">
                    <!-- Notifications will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function loadNotifications() {
    try {
        const response = await fetch('../api/notifications.php');
        const data = await response.json();
        
        if (data.success) {
            const notificationsList = document.getElementById('notifications-list');
            notificationsList.innerHTML = '';
            
            if (data.notifications.length === 0) {
                notificationsList.innerHTML = '<div class="alert alert-info">No notifications found</div>';
                return;
            }

            data.notifications.forEach(notification => {
                const notificationElement = document.createElement('div');
                notificationElement.className = `alert ${notification.read_at ? 'alert-light' : 'alert-info'}`;
                notificationElement.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-0">${notification.message}</p>
                            <small class="text-muted">${new Date(notification.created_at).toLocaleString()}</small>
                        </div>
                        ${!notification.read_at ? `
                            <button class="btn btn-sm btn-link" onclick="markAsRead(${notification.id})">
                                Mark as read
                            </button>
                        ` : ''}
                    </div>
                `;
                notificationsList.appendChild(notificationElement);
            });
        }
    } catch (error) {
        console.error('Error:', error);
        const notificationsList = document.getElementById('notifications-list');
        notificationsList.innerHTML = '<div class="alert alert-danger">Error loading notifications</div>';
    }
}

async function markAsRead(id) {
    try {
        const response = await fetch('../api/notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'mark_read',
                notification_id: id
            })
        });
        const data = await response.json();
        if (data.success) {
            loadNotifications();
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to mark notification as read');
    }
}

async function markAllAsRead() {
    try {
        const response = await fetch('../api/notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'mark_all_read'
            })
        });
        const data = await response.json();
        if (data.success) {
            loadNotifications();
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to mark all notifications as read');
    }
}

// Load notifications when the page loads
document.addEventListener('DOMContentLoaded', loadNotifications);
</script>

<?php
$content = ob_get_clean();
require_once '../includes/template.php';
?>
