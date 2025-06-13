<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

$pageTitle = "Complaints";
ob_start();
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Submit New Complaint</h5>
                <form id="complaintForm">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category" required>
                            <option value="maintenance">Maintenance</option>
                            <option value="cleanliness">Cleanliness</option>
                            <option value="security">Security</option>
                            <option value="food">Food Service</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <select class="form-select" name="priority" required>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Submit Complaint</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">My Complaints</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Resolution</th>
                            </tr>
                        </thead>
                        <tbody id="complaintsTable">
                            <!-- complaints will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resolution Details Modal -->
<div class="modal fade" id="resolutionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complaint Resolution</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="resolutionDetails"></div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$pageScript = <<<SCRIPT
<script>
function loadComplaints() {
    fetch('../api/student_complaints.php')
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('complaintsTable');            tbody.innerHTML = data.map(complaint => 
                '<tr>' +
                    '<td>#' + complaint.id + '</td>' +
                    '<td>' +
                        '<span class="badge bg-secondary">' + complaint.category + '</span>' +
                    '</td>' +
                    '<td>' + complaint.description.substring(0, 50) + '...</td>' +
                    '<td>' +
                        '<span class="badge bg-' + getStatusColor(complaint.status) + '">' +
                            complaint.status +
                        '</span>' +
                    '</td>' +
                    '<td>' + new Date(complaint.submitted_at).toLocaleDateString() + '</td>' +
                    '<td>' +
                        (complaint.resolved_at ? 
                            '<button class="btn btn-sm btn-info" onclick="viewResolution(' + complaint.id + ')">' +
                                'View Resolution' +
                            '</button>' : 
                            '-'
                        ) +
                    '</td>' +
                '</tr>'
            ).join('');
        });
}

function getStatusColor(status) {
    switch(status) {
        case 'Pending': return 'warning';
        case 'In Progress': return 'info';
        case 'Resolved': return 'success';
        default: return 'secondary';
    }
}



document.getElementById('complaintForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('../api/student_complaints.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            this.reset();
            loadComplaints();
            alert('Complaint submitted successfully');
        } else {
            alert(data.message);
        }
    });
});

// Load complaints when page loads
document.addEventListener('DOMContentLoaded', loadComplaints);
</script>
SCRIPT;

require_once '../includes/template.php';
?>
