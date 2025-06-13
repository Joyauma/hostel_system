<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header('Location: ../index.php');
    exit();
}

$pageTitle = "Complaint Management";
ob_start();
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Pending</h5>
                <h2 id="pendingCount">0</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">In Progress</h5>
                <h2 id="inProgressCount">0</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Resolved</h5>
                <h2 id="resolvedCount">0</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Resolution Rate</h5>
                <h2 id="resolutionRate">0%</h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title">Complaints</h5>
            <div>
                <select class="form-select d-inline-block w-auto me-2" id="statusFilter">
                    <option value="all">All Status</option>
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Resolved">Resolved</option>
                </select>
                <select class="form-select d-inline-block w-auto" id="categoryFilter">
                    <option value="all">All Categories</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="cleanliness">Cleanliness</option>
                    <option value="security">Security</option>
                    <option value="food">Food Service</option>
                    <option value="other">Other</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="complaintsTable">
                    <!-- Complaints will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Complaint Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="updateStatusForm">
                    <input type="hidden" name="complaint_id" id="complaintId">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="In Progress">In Progress</option>
                            <option value="Resolved">Resolved</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comments/Resolution</label>
                        <textarea class="form-control" name="resolution" rows="4" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="updateStatus()">Update Status</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$pageScript = <<<SCRIPT
<script>
let complaints = [];

function loadComplaints() {
    fetch('../api/complaints.php')
        .then(response => response.json())
        .then(data => {
            complaints = data;
            updateDashboard();
            applyFilters();
        });
}

function updateDashboard() {
    const pending = complaints.filter(c => c.status === 'Pending').length;
    const inProgress = complaints.filter(c => c.status === 'In Progress').length;
    const resolved = complaints.filter(c => c.status === 'Resolved').length;
    const total = complaints.length;
    const rate = total ? Math.round((resolved/total) * 100) : 0;

    document.getElementById('pendingCount').textContent = pending;
    document.getElementById('inProgressCount').textContent = inProgress;
    document.getElementById('resolvedCount').textContent = resolved;
    document.getElementById('resolutionRate').textContent = rate + '%';
}

function applyFilters() {
    const status = document.getElementById('statusFilter').value;
    const category = document.getElementById('categoryFilter').value;
    
    let filtered = [...complaints];
    if (status !== 'all') {
        filtered = filtered.filter(c => c.status === status);
    }
    if (category !== 'all') {
        filtered = filtered.filter(c => c.category === category);
    }

    updateTable(filtered);
}

function updateTable(complaints) {
    const tbody = document.getElementById('complaintsTable');
    tbody.innerHTML = complaints.map(complaint => 
        '<tr>' +
            '<td>#' + complaint.id + '</td>' +
            '<td>' + complaint.student_name + '</td>' +
            '<td>' +
                '<span class="badge bg-secondary">' + complaint.category + '</span>' +
            '</td>' +
            '<td>' +
                '<span class="badge bg-' + getPriorityColor(complaint.priority) + '">' +
                    complaint.priority +
                '</span>' +
            '</td>' +
            '<td>' + complaint.description.substring(0, 50) + '...</td>' +
            '<td>' +
                '<span class="badge bg-' + getStatusColor(complaint.status) + '">' +
                    complaint.status +
                '</span>' +
            '</td>' +
            '<td>' + new Date(complaint.submitted_at).toLocaleDateString() + '</td>' +
            '<td>' +
                (complaint.status !== 'Resolved' ? 
                    '<button class="btn btn-sm btn-primary" onclick="openUpdateModal(' + complaint.id + ')">' +
                        'Update Status' +
                    '</button>' : 
                    '<button class="btn btn-sm btn-info" onclick="viewResolution(' + complaint.id + ')">' +
                        'View Resolution' +
                    '</button>'
                ) +
            '</td>' +
        '</tr>'
    ).join('');
}

function getPriorityColor(priority) {
    switch(priority) {
        case 'high': return 'danger';
        case 'medium': return 'warning';
        case 'low': return 'success';
        default: return 'secondary';
    }
}

function getStatusColor(status) {
    switch(status) {
        case 'Pending': return 'warning';
        case 'In Progress': return 'info';
        case 'Resolved': return 'success';
        default: return 'secondary';
    }
}

function openUpdateModal(complaintId) {
    document.getElementById('complaintId').value = complaintId;
    new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
}

function updateStatus() {
    const form = document.getElementById('updateStatusForm');
    const formData = new FormData(form);
    
    fetch('../api/complaints.php', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(Object.fromEntries(formData))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('updateStatusModal')).hide();
            form.reset();
            loadComplaints();
        } else {
            alert(data.message);
        }
    });
}

// Event listeners
document.getElementById('statusFilter').addEventListener('change', applyFilters);
document.getElementById('categoryFilter').addEventListener('change', applyFilters);

// Load complaints when page loads
document.addEventListener('DOMContentLoaded', loadComplaints);
</script>
SCRIPT;

require_once '../includes/template.php';
?>
