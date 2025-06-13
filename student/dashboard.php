<?php
session_start();
require_once '../includes/alerts.php';

// check if user is logged in and is a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    redirect_with_error("Unauthorized access", "/hostel_system/index.php");
}

$pageTitle = "Student Dashboard";
ob_start();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h4>Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? $_SESSION['username']); ?>!</h4>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Room Status Card -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Room Status</h5>
            </div>
            <div class="card-body" id="roomStatus">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fee Status Card -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">Fee Status</h5>
            </div>
            <div class="card-body" id="feeStatus">
                <div class="text-center">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Complaints -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-warning">
                <h5 class="card-title mb-0">Recent Complaints</h5>
            </div>
            <div class="card-body" id="recentComplaints">
                <div class="text-center">
                    <div class="spinner-border text-warning" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">Recent Notifications</h5>
            </div>
            <div class="card-body" id="notifications">
                <div class="text-center">
                    <div class="spinner-border text-info" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$pageScript = <<<'SCRIPT'
<script>
function loadRoomStatus() {
    fetch('../api/student_room.php?action=status')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const room = data.data;
                let html = '';
                if (room) {
                    html = `
                        <h3 class="mb-4">Room ${room.room_number}</h3>
                        <ul class="list-unstyled">
                            <li><strong>Block:</strong> ${room.block}</li>
                            <li><strong>Floor:</strong> ${room.floor}</li>
                            <li><strong>Room Type:</strong> ${room.room_type}</li>
                            <li><strong>Status:</strong> <span class="badge bg-success">Allocated</span></li>
                        </ul>
                    `;
                } else {
                    html = `
                        <div class="text-center">
                            <p class="mb-3">No room allocated yet</p>
                            <a href="room.php" class="btn btn-primary">Request Room</a>
                        </div>
                    `;
                }
                document.getElementById('roomStatus').innerHTML = html;
            } else {
                throw new Error(data.message || 'Failed to load room status');
            }
        })
        .catch(error => {
            document.getElementById('roomStatus').innerHTML = `
                <div class="alert alert-danger">
                    ${error.message || 'Failed to load room status'}
                </div>
            `;
        });
}

function loadFeeStatus() {
    fetch('../api/student_fees.php?action=summary')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const fees = data.data;
                document.getElementById('feeStatus').innerHTML = `
                    <div class="d-flex justify-content-between mb-3">
                        <div>Total Due:</div>
                        <div>₹${fees.total_due.toLocaleString()}</div>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <div>Paid This Month:</div>
                        <div>₹${fees.paid_this_month.toLocaleString()}</div>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <div>Next Due Date:</div>
                        <div>${fees.next_due_date || 'No pending dues'}</div>
                    </div>
                    <div class="text-center mt-3">
                        <a href="fees.php" class="btn btn-success">View Details</a>
                    </div>
                `;
            } else {
                throw new Error(data.message || 'Failed to load fee status');
            }
        })
        .catch(error => {
            document.getElementById('feeStatus').innerHTML = `
                <div class="alert alert-danger">
                    ${error.message || 'Failed to load fee status'}
                </div>
            `;
        });
}

function loadRecentComplaints() {
    fetch('../api/student_complaints.php?action=recent')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const complaints = data.data;
                if (complaints.length === 0) {
                    document.getElementById('recentComplaints').innerHTML = `
                        <p class="text-center">No recent complaints</p>
                        <div class="text-center">
                            <a href="complaints.php" class="btn btn-warning">Submit Complaint</a>
                        </div>
                    `;
                    return;
                }

                let html = '<div class="list-group">';
                complaints.forEach(complaint => {
                    const statusBadge = complaint.status === 'Resolved' 
                        ? 'bg-success' 
                        : (complaint.status === 'In Progress' ? 'bg-warning' : 'bg-secondary');
                    html += `
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${complaint.title}</h6>
                                <small><span class="badge ${statusBadge}">${complaint.status}</span></small>
                            </div>
                            <small class="text-muted">${new Date(complaint.created_at).toLocaleDateString()}</small>
                        </div>
                    `;
                });
                html += '</div>';
                html += `
                    <div class="text-center mt-3">
                        <a href="complaints.php" class="btn btn-warning">View All</a>
                    </div>
                `;
                document.getElementById('recentComplaints').innerHTML = html;
            } else {
                throw new Error(data.message || 'Failed to load complaints');
            }
        })
        .catch(error => {
            document.getElementById('recentComplaints').innerHTML = `
                <div class="alert alert-danger">
                    ${error.message || 'Failed to load complaints'}
                </div>
            `;
        });
}

function loadNotifications() {
    fetch('../api/notifications.php?action=recent')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notifications = data.data;
                if (notifications.length === 0) {
                    document.getElementById('notifications').innerHTML = '<p class="text-center">No new notifications</p>';
                    return;
                }

                let html = '<div class="list-group">';
                notifications.forEach(notification => {
                    html += `
                        <div class="list-group-item ${notification.is_read ? '' : 'bg-light'}">
                            <div class="d-flex w-100 justify-content-between">
                                <p class="mb-1">${notification.message}</p>
                                <small class="text-muted">${new Date(notification.created_at).toLocaleDateString()}</small>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                document.getElementById('notifications').innerHTML = html;
            } else {
                throw new Error(data.message || 'Failed to load notifications');
            }
        })
        .catch(error => {
            document.getElementById('notifications').innerHTML = `
                <div class="alert alert-danger">
                    ${error.message || 'Failed to load notifications'}
                </div>
            `;
        });
}

// Initialize all dashboard components
document.addEventListener('DOMContentLoaded', () => {
    loadRoomStatus();
    loadFeeStatus();
    loadRecentComplaints();
    loadNotifications();
});
</script>
SCRIPT;

require_once '../includes/template.php';
?>
