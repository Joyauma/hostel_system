<?php
session_start();
require_once '../includes/alerts.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    redirect_with_error("Unauthorized access", "/hostel_system/index.php");
}

$pageTitle = "Notifications";
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Notifications</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sendNotificationModal">
        <i class="bi bi-bell"></i> Send Notification
    </button>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Notifications</h5>
            </div>
            <div class="card-body">
                <div id="notificationsList">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Stats</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Total Notifications
                        <span class="badge bg-primary rounded-pill" id="totalNotifications">0</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Read Notifications
                        <span class="badge bg-success rounded-pill" id="readNotifications">0</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Unread Notifications
                        <span class="badge bg-warning rounded-pill" id="unreadNotifications">0</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Notification Types</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-info" onclick="showSendModal('info')">
                        <i class="bi bi-info-circle"></i> Information
                    </button>
                    <button class="btn btn-outline-warning" onclick="showSendModal('warning')">
                        <i class="bi bi-exclamation-triangle"></i> Warning
                    </button>
                    <button class="btn btn-outline-danger" onclick="showSendModal('urgent')">
                        <i class="bi bi-exclamation-circle"></i> Urgent
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Send Notification Modal -->
<div class="modal fade" id="sendNotificationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="notificationForm">
                    <div class="mb-3">
                        <label class="form-label">Recipients</label>
                        <select class="form-select" name="recipients" required>
                            <option value="all">All Students</option>
                            <option value="year">By Year of Study</option>
                            <option value="course">By Course</option>
                            <option value="custom">Select Students</option>
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="yearSelect">
                        <label class="form-label">Select Year</label>
                        <select class="form-select" name="year">
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
                            <option value="3">Year 3</option>
                            <option value="4">Year 4</option>
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="courseSelect">
                        <label class="form-label">Select Course</label>
                        <select class="form-select" name="course">
                            <!-- Will be populated dynamically -->
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="studentSelect">
                        <label class="form-label">Select Students</label>
                        <select class="form-select" name="students[]" multiple size="5">
                            <!-- Will be populated dynamically -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type" required>
                            <option value="info">Information</option>
                            <option value="warning">Warning</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="message" rows="4" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendNotification()">Send Notification</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$pageScript = <<<'SCRIPT'
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    loadNotificationStats();
    setupRecipientSelector();
    loadCoursesList();
    loadStudentsList();
});

function setupRecipientSelector() {
    const recipientSelect = document.querySelector('select[name="recipients"]');
    const yearSelect = document.getElementById('yearSelect');
    const courseSelect = document.getElementById('courseSelect');
    const studentSelect = document.getElementById('studentSelect');

    recipientSelect.addEventListener('change', function() {
        yearSelect.classList.add('d-none');
        courseSelect.classList.add('d-none');
        studentSelect.classList.add('d-none');

        switch(this.value) {
            case 'year':
                yearSelect.classList.remove('d-none');
                break;
            case 'course':
                courseSelect.classList.remove('d-none');
                break;
            case 'custom':
                studentSelect.classList.remove('d-none');
                break;
        }
    });
}

async function loadCoursesList() {
    try {
        const response = await fetch('../api/students.php?action=courses');
        const data = await response.json();
        if (data.success) {
            const select = document.querySelector('select[name="course"]');
            data.data.forEach(course => {
                const option = document.createElement('option');
                option.value = course;
                option.textContent = course;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading courses:', error);
    }
}

async function loadStudentsList() {
    try {
        const response = await fetch('../api/students.php');
        const data = await response.json();
        if (data.success) {
            const select = document.querySelector('select[name="students[]"]');
            data.data.forEach(student => {
                const option = document.createElement('option');
                option.value = student.user_id;
                option.textContent = `${student.roll} - ${student.first_name} ${student.last_name}`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading students:', error);
    }
}

async function loadNotifications() {
    try {
        const response = await fetch('../api/notifications.php?limit=20');
        const data = await response.json();
        
        if (data.success) {
            const notificationsList = document.getElementById('notificationsList');
            if (data.data.length === 0) {
                notificationsList.innerHTML = '<p class="text-center">No notifications found</p>';
                return;
            }

            notificationsList.innerHTML = data.data.map(notification => `
                <div class="notification-item p-3 border-bottom ${!notification.is_read ? 'bg-light' : ''}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">
                            <i class="bi bi-${getNotificationIcon(notification.type)} me-2"></i>
                            ${notification.sender_name}
                        </h6>
                        <small class="text-muted">
                            ${new Date(notification.created_at).toLocaleString()}
                        </small>
                    </div>
                    <p class="mb-0">${notification.message}</p>
                </div>
            `).join('');
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Failed to load notifications', 'danger');
    }
}

async function loadNotificationStats() {
    try {
        const response = await fetch('../api/notifications.php?action=stats');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('totalNotifications').textContent = data.data.total;
            document.getElementById('readNotifications').textContent = data.data.read;
            document.getElementById('unreadNotifications').textContent = data.data.unread;
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function getNotificationIcon(type) {
    switch(type) {
        case 'warning': return 'exclamation-triangle';
        case 'urgent': return 'exclamation-circle';
        default: return 'info-circle';
    }
}

function showSendModal(type) {
    const modal = new bootstrap.Modal(document.getElementById('sendNotificationModal'));
    document.querySelector('select[name="type"]').value = type;
    modal.show();
}

async function sendNotification() {
    const form = document.getElementById('notificationForm');
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }

    const formData = new FormData(form);
    let recipients = [];

    try {
        switch(formData.get('recipients')) {
            case 'all':
                const studentsResponse = await fetch('../api/students.php');
                const studentsData = await studentsResponse.json();
                recipients = studentsData.data.map(student => student.user_id);
                break;
            case 'year':
                const yearResponse = await fetch(`../api/students.php?year=${formData.get('year')}`);
                const yearData = await yearResponse.json();
                recipients = yearData.data.map(student => student.user_id);
                break;
            case 'course':
                const courseResponse = await fetch(`../api/students.php?course=${formData.get('course')}`);
                const courseData = await courseResponse.json();
                recipients = courseData.data.map(student => student.user_id);
                break;
            case 'custom':
                recipients = Array.from(form.querySelector('select[name="students[]"]').selectedOptions)
                    .map(option => option.value);
                break;
        }

        if (recipients.length === 0) {
            throw new Error('No recipients selected');
        }

        const response = await fetch('../api/notifications.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'send_bulk',
                recipients: recipients,
                message: formData.get('message'),
                type: formData.get('type')
            }),
            headers: {
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();
        if (data.success) {
            showAlert('Notification sent successfully', 'success');
            $('#sendNotificationModal').modal('hide');
            form.reset();
            loadNotifications();
            loadNotificationStats();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        showAlert(error.message || 'Failed to send notification', 'danger');
    }
}

function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.querySelector('.row').prepend(alertDiv);
    
    setTimeout(() => alertDiv.remove(), 5000);
}
</script>
SCRIPT;

include_once '../includes/template.php';
?>
