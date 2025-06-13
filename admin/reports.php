<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$pageTitle = "Reports & Analytics";
ob_start();
?>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Room Occupancy Report</h5>
                <div>
                    <canvas id="roomOccupancyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Fee Collection Trend</h5>
                <div>
                    <canvas id="feeCollectionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Complaint Statistics</h5>
                <div>
                    <canvas id="complaintChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Student Activity</h5>
                <div>
                    <canvas id="studentActivityChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title">Generate Reports</h5>
                    <div>
                        <select class="form-select d-inline-block w-auto me-2" id="reportType">
                            <option value="occupancy">Room Occupancy</option>
                            <option value="fees">Fee Collection</option>
                            <option value="complaints">Complaint Resolution</option>
                            <option value="students">Student Activity</option>
                        </select>
                        <input type="month" class="form-control d-inline-block w-auto me-2" id="reportMonth">
                        <button class="btn btn-primary" onclick="generateReport()">
                            Generate Report
                        </button>
                    </div>
                </div>
                <div id="reportContent">
                    <!-- Report content will be displayed here -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$pageScript = <<<SCRIPT
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let charts = {};

function loadRoomOccupancy() {
    fetch('../api/reports.php?type=room_occupancy')
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('roomOccupancyChart').getContext('2d');
            if (charts.roomOccupancy) {
                charts.roomOccupancy.destroy();
            }
            charts.roomOccupancy = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Occupied', 'Available'],
                    datasets: [{
                        data: [data.occupied, data.available],
                        backgroundColor: ['#198754', '#0dcaf0']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Room Occupancy Status'
                        }
                    }
                }
            });
        });
}

function loadFeeCollection() {
    fetch('../api/reports.php?type=fee_collection')
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('feeCollectionChart').getContext('2d');
            if (charts.feeCollection) {
                charts.feeCollection.destroy();
            }
            charts.feeCollection = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.months,
                    datasets: [{
                        label: 'Collection Amount',
                        data: data.amounts,
                        borderColor: '#198754',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Monthly Fee Collection'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Amount (₹)'
                            }
                        }
                    }
                }
            });
        });
}

function loadComplaintStats() {
    fetch('../api/reports.php?type=complaints')
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('complaintChart').getContext('2d');
            if (charts.complaints) {
                charts.complaints.destroy();
            }
            charts.complaints = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Resolved', 'In Progress', 'Pending'],
                    datasets: [{
                        label: 'Number of Complaints',
                        data: [data.resolved, data.in_progress, data.pending],
                        backgroundColor: ['#198754', '#0dcaf0', '#ffc107']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Complaint Resolution Status'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Complaints'
                            }
                        }
                    }
                }
            });
        });
}

function loadStudentActivity() {
    fetch('../api/reports.php?type=student_activity')
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('studentActivityChart').getContext('2d');
            if (charts.studentActivity) {
                charts.studentActivity.destroy();
            }
            charts.studentActivity = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.dates,
                    datasets: [{
                        label: 'Admissions',
                        data: data.admissions,
                        borderColor: '#198754',
                        tension: 0.1
                    }, {
                        label: 'Exits',
                        data: data.exits,
                        borderColor: '#dc3545',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Student Activity Trends'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Students'
                            }
                        }
                    }
                }
            });
        });
}

function generateReport() {
    const type = document.getElementById('reportType').value;
    const month = document.getElementById('reportMonth').value;
    
    fetch('../api/reports.php?type=' + type + '&month=' + month)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('reportContent');
            let html = '<div class="table-responsive mt-3">';
            
            switch(type) {
                case 'occupancy':
                    html += generateOccupancyReport(data);
                    break;
                case 'fees':
                    html += generateFeesReport(data);
                    break;
                case 'complaints':
                    html += generateComplaintsReport(data);
                    break;
                case 'students':
                    html += generateStudentReport(data);
                    break;
            }
            
            html += '</div>';
            container.innerHTML = html;
        });
}

function generateOccupancyReport(data) {
    return '<table class="table table-bordered">' +
           '<thead><tr><th>Room Type</th><th>Total</th><th>Occupied</th><th>Available</th><th>Rate</th></tr></thead>' +
           '<tbody>' + data.types.map(type => 
               '<tr>' +
                   '<td>' + type.name + '</td>' +
                   '<td>' + type.total + '</td>' +
                   '<td>' + type.occupied + '</td>' +
                   '<td>' + (type.total - type.occupied) + '</td>' +
                   '<td>' + Math.round((type.occupied/type.total) * 100) + '%</td>' +
               '</tr>'
           ).join('') + '</tbody></table>';
}

function generateFeesReport(data) {
    return '<table class="table table-bordered">' +
           '<thead><tr><th>Category</th><th>Amount</th><th>Collected</th><th>Pending</th><th>Collection Rate</th></tr></thead>' +
           '<tbody>' + data.categories.map(cat => 
               '<tr>' +
                   '<td>' + cat.name + '</td>' +
                   '<td>₹' + cat.total.toLocaleString() + '</td>' +
                   '<td>₹' + cat.collected.toLocaleString() + '</td>' +
                   '<td>₹' + (cat.total - cat.collected).toLocaleString() + '</td>' +
                   '<td>' + Math.round((cat.collected/cat.total) * 100) + '%</td>' +
               '</tr>'
           ).join('') + '</tbody></table>';
}

function generateComplaintsReport(data) {
    return '<table class="table table-bordered">' +
           '<thead><tr><th>Category</th><th>Total</th><th>Resolved</th><th>Pending</th><th>Avg Resolution Time</th></tr></thead>' +
           '<tbody>' + data.categories.map(cat => 
               '<tr>' +
                   '<td>' + cat.name + '</td>' +
                   '<td>' + cat.total + '</td>' +
                   '<td>' + cat.resolved + '</td>' +
                   '<td>' + (cat.total - cat.resolved) + '</td>' +
                   '<td>' + cat.avg_resolution_time + ' days</td>' +
               '</tr>'
           ).join('') + '</tbody></table>';
}

function generateStudentReport(data) {
    return '<table class="table table-bordered">' +
           '<thead><tr><th>Date</th><th>Admissions</th><th>Exits</th><th>Total Students</th><th>Occupancy Rate</th></tr></thead>' +
           '<tbody>' + data.days.map(day => 
               '<tr>' +
                   '<td>' + day.date + '</td>' +
                   '<td>' + day.admissions + '</td>' +
                   '<td>' + day.exits + '</td>' +
                   '<td>' + day.total + '</td>' +
                   '<td>' + Math.round((day.total/day.capacity) * 100) + '%</td>' +
               '</tr>'
           ).join('') + '</tbody></table>';
}

// Load charts when page loads
document.addEventListener('DOMContentLoaded', () => {
    loadRoomOccupancy();
    loadFeeCollection();
    loadComplaintStats();
    loadStudentActivity();
});
</script>
SCRIPT;

require_once '../includes/template.php';
?>
