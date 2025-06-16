<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$pageTitle = "Fee Management";
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Fee Management</h2>
    <div>
        <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#generateFeesModal">
            <i class="bi bi-plus-circle"></i> Generate Monthly Fees
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFeeModal">
            <i class="bi bi-plus-circle"></i> Add Individual Fee
        </button>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Total Fees</h5>                <h2 id="totalFees">Ksh 0</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Collected Fees</h5>
                <h2 id="collectedFees">Ksh 0</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h5 class="card-title">Pending Fees</h5>
                <h2 id="pendingFees">Ksh 0</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">Collection Rate</h5>
                <h2 id="collectionRate">0%</h2>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Fee Records</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th>Room</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Payment Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="feesTable">
                    <!-- Fees will be loaded here dynamically -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Generate Monthly Fees Modal -->
<div class="modal fade" id="generateFeesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate Monthly Fees</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="generateFeesForm">
                    <div class="mb-3">
                        <label class="form-label">Month</label>
                        <input type="month" class="form-control" name="month" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Base Amount per Student</label>
                        <input type="number" class="form-control" name="amount" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-control" name="due_date" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="generateFees()">Generate</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Individual Fee Modal -->
<div class="modal fade" id="addFeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Individual Fee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addFeeForm">
                    <div class="mb-3">
                        <label class="form-label">Student</label>
                        <select class="form-select" name="student_id" required>
                            <!-- Students will be loaded here -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" class="form-control" name="amount" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-control" name="due_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="addFee()">Add Fee</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$pageScript = <<<SCRIPT
<script>
let fees = [];
let students = [];

function loadDashboard() {
    fetch('../api/fees.php?action=dashboard')
        .then(response => response.json())
        .then(data => {            document.getElementById('totalFees').textContent = 'Ksh ' + data.total.toLocaleString();
            document.getElementById('collectedFees').textContent = 'Ksh ' + data.collected.toLocaleString();
            document.getElementById('pendingFees').textContent = 'Ksh ' + data.pending.toLocaleString();
            document.getElementById('collectionRate').textContent = 
                data.total ? Math.round((data.collected/data.total) * 100) + '%' : '0%';
        });
}

function loadFees() {
    fetch('../api/fees.php')
        .then(response => response.json())
        .then(data => {
            fees = data;
            updateFeesTable();
        });
}

function loadStudents() {
    fetch('../api/students.php')
        .then(response => response.json())
        .then(data => {
            students = data;
            updateStudentSelect();
        });
}

function updateFeesTable() {
    const tbody = document.getElementById('feesTable');
    tbody.innerHTML = fees.map(fee => 
        '<tr>' +
            '<td>' + fee.student_name + '</td>' +
            '<td>' + (fee.room_no || '-') + '</td>' +
            '<td>Ksh ' + fee.amount.toLocaleString() + '</td>' +
            '<td>' + new Date(fee.due_date).toLocaleDateString() + '</td>' +
            '<td>' +
                '<span class="badge bg-' + (fee.status === 'Paid' ? 'success' : 'warning') + '">' +
                    fee.status +
                '</span>' +
            '</td>' +
            '<td>' + (fee.paid_at ? new Date(fee.paid_at).toLocaleDateString() : '-') + '</td>' +
            '<td>' +
                (fee.status === 'Unpaid' ? 
                    '<button class="btn btn-sm btn-success" onclick="markAsPaid(' + fee.id + ')">' +
                        'Mark as Paid' +
                    '</button>' +
                    '<button class="btn btn-sm btn-warning" onclick="sendReminder(' + fee.id + ')">' +
                        'Send Reminder' +
                    '</button>' :
                    '<button class="btn btn-sm btn-info" onclick="viewPayment(' + fee.id + ')">' +
                        'View Details' +
                    '</button>'
                ) +
            '</td>' +
        '</tr>'
    ).join('');
}

function updateStudentSelect() {
    const select = document.querySelector('#addFeeForm select[name="student_id"]');
    select.innerHTML = students.map(student => 
        '<option value="' + student.id + '">' + student.name + ' (' + student.roll + ')</option>'
    ).join('');
}

function generateFees() {
    const form = document.getElementById('generateFeesForm');
    const formData = new FormData(form);

    fetch('../api/fees.php?action=generate', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('generateFeesModal')).hide();
            form.reset();
            loadDashboard();
            loadFees();
        } else {
            alert(data.message);
        }
    });
}

function addFee() {
    const form = document.getElementById('addFeeForm');
    const formData = new FormData(form);

    fetch('../api/fees.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('addFeeModal')).hide();
            form.reset();
            loadDashboard();
            loadFees();
        } else {
            alert(data.message);
        }
    });
}

function markAsPaid(feeId) {
    if (confirm('Mark this fee as paid?')) {
        fetch('../api/fees.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: feeId,
                action: 'mark_paid'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadDashboard();
                loadFees();
            } else {
                alert(data.message);
            }
        });
    }
}

function sendReminder(feeId) {
    fetch('../api/fees.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: feeId,
            action: 'send_reminder'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Reminder sent successfully');
        } else {
            alert(data.message);
        }
    });
}

// Load data when page loads
document.addEventListener('DOMContentLoaded', () => {
    loadDashboard();
    loadFees();
    loadStudents();
});
</script>
SCRIPT;

require_once '../includes/template.php';
?>
