<?php
session_start();
require_once '../includes/alerts.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    redirect_with_error("Unauthorized access", "/hostel_system/index.php");
}

$pageTitle = "Admin Dashboard";
ob_start();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h4>Welcome, Administrator!</h4>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Stats -->
    <div class="col-md-12 mb-4">
        <div class="row">
            <!-- Total Students -->
            <div class="col-md-3 mb-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Total Students</h6>
                        <h2 class="mb-0" id="totalStudents">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </h2>
                    </div>
                </div>
            </div>

            <!-- Occupied Rooms -->
            <div class="col-md-3 mb-4">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Occupied Rooms</h6>
                        <h2 class="mb-0" id="occupiedRooms">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </h2>
                    </div>
                </div>
            </div>

            <!-- Pending Complaints -->
            <div class="col-md-3 mb-4">
                <div class="card bg-warning h-100">
                    <div class="card-body">
                        <h6 class="card-title">Pending Complaints</h6>
                        <h2 class="mb-0" id="pendingComplaints">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </h2>
                    </div>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="col-md-3 mb-4">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Monthly Revenue</h6>
                        <h2 class="mb-0" id="monthlyRevenue">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Applications -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Room Applications</h5>
            </div>
            <div class="card-body" id="recentApplications">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>            </div>
        </div>
    </div>

    <!-- Monthly Revenue -->
    <div class="col-md-3 mb-4">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <h6 class="card-title">Monthly Revenue</h6>
                <h2 class="mb-0" id="monthlyRevenue">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </h2>
            </div>
        </div>
    </div>

    <!-- Pending Complaints -->
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <h6 class="card-title">Pending Complaints</h6>
                <h2 class="mb-0" id="pendingComplaints">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </h2>
            </div>
        </div>
    </div>
</div>

    <!-- Recent Complaints -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Complaints</h5>
                </div>
                <div class="card-body" id="recentComplaints">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Room Applications -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Room Applications</h5>
                </div>
                <div class="card-body" id="recentApplications">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fee Collection Graph -->
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Fee Collection Overview</h5>
            </div>
            <div class="card-body">
                <canvas id="feeCollectionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$pageScript = <<<'SCRIPT'
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let feeChart = null;

    // Function to handle API errors
    function handleApiError(error) {
        console.error('API Error:', error);
        return 'Error loading data';
    }

    // Function to format currency
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }

    // Function to update dashboard stats
    async function updateDashboardStats() {
        try {
            // Total Students
            const studentsResponse = await fetch('../api/reports.php?action=total_students');
            const studentsData = await studentsResponse.json();
            if (studentsData.success) {
                document.getElementById('totalStudents').textContent = studentsData.data.total;
            } else {
                throw new Error(studentsData.message);
            }

            // Occupied Rooms
            const roomsResponse = await fetch('../api/reports.php?action=occupied_rooms');
            const roomsData = await roomsResponse.json();
            if (roomsData.success) {
                document.getElementById('occupiedRooms').textContent = 
                    `${roomsData.data.occupied}/${roomsData.data.total}`;
                
                // Calculate and update occupancy rate
                const occupancyRate = ((roomsData.data.occupied / roomsData.data.total) * 100).toFixed(1);
                document.getElementById('occupancyRate').textContent = occupancyRate + '%';
            } else {
                throw new Error(roomsData.message);
            }

            // Pending Complaints
            const complaintsResponse = await fetch('../api/reports.php?action=pending_complaints');
            const complaintsData = await complaintsResponse.json();
            if (complaintsData.success) {
                document.getElementById('pendingComplaints').textContent = complaintsData.data.total;
            } else {
                throw new Error(complaintsData.message);
            }

            // Monthly Revenue
            const revenueResponse = await fetch('../api/reports.php?action=monthly_revenue');
            const revenueData = await revenueResponse.json();
            if (revenueData.success) {
                document.getElementById('monthlyRevenue').textContent = 
                    '$' + revenueData.data.amount.toLocaleString();
            } else {
                throw new Error(revenueData.message);
            }            // Recent Complaints
            const recentComplaintsResponse = await fetch('../api/reports.php?action=recent_complaints');
            const recentComplaintsData = await recentComplaintsResponse.json();
            if (recentComplaintsData.success) {
                let html = '<div class="list-group">';
                if (recentComplaintsData.data.length === 0) {
                    html = '<p class="text-center">No recent complaints</p>';
                } else {
                    recentComplaintsData.data.forEach(complaint => {
                        const statusClass = complaint.status === 'Resolved' ? 'success' : 
                                          complaint.status === 'In Progress' ? 'warning' : 'secondary';
                        html += `
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">${complaint.title}</h6>
                                    <small class="text-${statusClass}">${complaint.status}</small>
                                </div>
                                <p class="mb-1">${complaint.description}</p>
                                <small class="text-muted">
                                    Submitted by: ${complaint.student_name} on 
                                    ${new Date(complaint.created_at).toLocaleDateString()}
                                </small>
                            </div>
                        `;
                    });
                }
                html += '</div>';
                document.getElementById('recentComplaints').innerHTML = html;
            } else {
                throw new Error(recentComplaintsData.message);
            }

            // Recent Room Applications
            const applicationsResponse = await fetch('../api/reports.php?action=recent_applications');
            const applicationsData = await applicationsResponse.json();
            if (applicationsData.success) {
                let html = '<div class="list-group">';
                if (applicationsData.data.length === 0) {
                    html = '<p class="text-center">No recent applications</p>';
                } else {
                    applicationsData.data.forEach(application => {
                        html += `
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Room ${application.room_number}</h6>
                                    <small class="text-primary">${application.status}</small>
                                </div>
                                <p class="mb-1">
                                    Student: ${application.student_name}<br>
                                    Registration: ${application.registration_number}
                                </p>
                                <small class="text-muted">
                                    Applied on: ${new Date(application.created_at).toLocaleDateString()}
                                </small>
                            </div>
                        `;
                    });
                }
                html += '</div>';
                document.getElementById('recentApplications').innerHTML = html;
            } else {
                throw new Error(applicationsData.message);
            }

            // Fee Collection Chart
            const chartResponse = await fetch('../api/reports.php?action=fee_collection_chart');
            const chartData = await chartResponse.json();
            if (chartData.success) {
                const ctx = document.getElementById('feeCollectionChart').getContext('2d');
                if (feeChart) {
                    feeChart.destroy();
                }
                feeChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: chartData.data.labels,
                        datasets: [{
                            label: 'Fee Collection',
                            data: chartData.data.values,
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return formatCurrency(value);
                                    }
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return formatCurrency(context.parsed.y);
                                    }
                                }
                            }
                        }
                    }
                });
            } else {
                throw new Error(chartData.message);
            }

        } catch (error) {
            handleApiError(error);
            // Update error state for all widgets
            const widgets = ['totalStudents', 'occupiedRooms', 'occupancyRate', 
                           'pendingComplaints', 'monthlyRevenue'];
            widgets.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.innerHTML = '<span class="text-danger">Error loading data</span>';
                }
            });

            // Show error for recent items
            ['recentComplaints', 'recentApplications'].forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.innerHTML = '<div class="alert alert-danger">Failed to load data</div>';
                }
            });

            // Show error for chart
            const chartElement = document.getElementById('feeCollectionChart');
            if (chartElement) {
                chartElement.parentElement.innerHTML = '<div class="alert alert-danger">Failed to load fee collection data</div>';
            }
        }
    }

    // Initial update
    updateDashboardStats();

    // Refresh every 5 minutes
    setInterval(updateDashboardStats, 300000);
});
</script>
SCRIPT;

require_once '../includes/template.php';
?>
