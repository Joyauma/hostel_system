<?php
session_start();
require_once '../includes/alerts.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    redirect_with_error("Unauthorized access", "../index.php");
}

$pageTitle = "My Fees";
ob_start();
?>

<div class="row">
    <?php
    if (isset($_GET['error'])) {
        display_alert($_GET['error'], 'error');
    }
    if (isset($_GET['success'])) {
        display_alert($_GET['success'], 'success');
    }
    ?>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Fee Summary</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Total Due:</span>                <strong id="totalDue">Ksh 0.00</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Paid This Month:</span>
                        <strong id="paidThisMonth">Ksh 0.00</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Next Due Date:</span>
                        <strong id="nextDueDate">-</strong>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Fee History</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Payment Date</th>
                            </tr>
                        </thead>
                        <tbody id="feeHistory">
                            <!-- Fee history will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$pageScript = <<<SCRIPT
<script>
function showAlert(message, type = 'error') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-\${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        \${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.querySelector('.row').prepend(alertDiv);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

function loadFeeSummary() {
    fetch('../api/student_fees.php?action=summary')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load fee summary');
            }
            return response.json();
        })
        .then(data => {            document.getElementById('totalDue').textContent = 'Ksh ' + data.total_due.toLocaleString();
            document.getElementById('paidThisMonth').textContent = 'Ksh ' + data.paid_this_month.toLocaleString();
            document.getElementById('nextDueDate').textContent = data.next_due_date || '-';
        })
        .catch(error => {
            showAlert('Failed to load fee summary. Please try again later.');
            console.error('Error:', error);
        });
}

function loadFeeHistory() {
    fetch('../api/student_fees.php?action=history')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load fee history');
            }
            return response.json();
        })
        .then(data => {
            const tbody = document.getElementById('feeHistory');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">No fee records found</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.map(fee => 
                '<tr>' +
                    '<td>' + (fee.description || 'Monthly Fee') + '</td>' +
                    '<td>₹' + fee.amount.toLocaleString() + '</td>' +
                    '<td>' + new Date(fee.due_date).toLocaleDateString() + '</td>' +
                    '<td>' +
                        '<span class="badge bg-' + (fee.status === 'Paid' ? 'success' : 'warning') + '">' +
                            fee.status +
                        '</span>' +
                    '</td>' +
                    '<td>' + (fee.paid_at ? new Date(fee.paid_at).toLocaleDateString() : '-') + '</td>' +
                '</tr>'
            ).join('');
        })
        .catch(error => {
            showAlert('Failed to load fee history. Please try again later.');
            console.error('Error:', error);
        });
}

// Load data when page loads
document.addEventListener('DOMContentLoaded', () => {
    loadFeeSummary();
    loadFeeHistory();
    
    // Refresh data every 5 minutes
    setInterval(() => {
        loadFeeSummary();
        loadFeeHistory();
    }, 300000);
});
</script>
SCRIPT;

require_once '../includes/template.php';
?>
