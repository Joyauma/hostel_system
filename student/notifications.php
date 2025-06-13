<?php
session_start();
if (!isset($_SESSION['role'])) {
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
                <div id="notificationsList">
                    <!-- Notifications will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$pageScript = <<<SCRIPT
<script>
function loadNotifications() {
    fetch('../api/notifications.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('notificationsList');
            if (data.length === 0) {
                container.innerHTML = '<div class="text-center text-muted">No notifications</div>';
                return;
            }

            container.innerHTML = data.map(notification => 
                '<div class="alert alert-' + getNotificationStyle(notification.type) + ' ' + 
                    (notification.read_at ? 'opacity-75' : '') + '">' +
                    '<div class="d-flex justify-content-between align-items-start">' +
                        '<div>' +
                            '<p class="mb-0">' + notification.message + '</p>' +
                            '<small class="text-muted">' + 
                                new Date(notification.sent_at).toLocaleString() + 
                            '</small>' +
                        '</div>' +
                        (!notification.read_at ? 
                            '<button class="btn btn-sm btn-outline-secondary" ' +
                                'onclick="markAsRead(' + notification.id + ')">' +
                                'Mark as Read' +
                            '</button>' : 
                            ''
                        ) +
                    '</div>' +
                '</div>'
            ).join('');
        });
}

function getNotificationStyle(type) {
    switch(type) {
        case 'fee_generated':
        case 'fee_reminder':
            return 'warning';
        case 'payment_received':
        case 'complaint_resolved':
            return 'success';
        case 'complaint_update':
            return 'info';
        case 'room_allocation':
        case 'room_change':
            return 'primary';
        default:
            return 'secondary';
    }
}

function markAsRead(notificationId) {
    fetch('../api/notifications.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: notificationId,
            action: 'mark_read'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    });
}

function markAllAsRead() {
    if (confirm('Mark all notifications as read?')) {
        fetch('../api/notifications.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'mark_all_read'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications();
            }
        });
    }
}

// Load notifications when page loads
document.addEventListener('DOMContentLoaded', loadNotifications);

// Refresh notifications every minute
setInterval(loadNotifications, 60000);
</script>
SCRIPT;

require_once '../includes/template.php';
?>
