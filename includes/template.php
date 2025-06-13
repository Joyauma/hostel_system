<?php
require_once __DIR__ . '/alerts.php';

function getNavItems($role) {
    $navItems = [
        'admin' => [
            ['url' => 'dashboard.php', 'icon' => 'house', 'text' => 'Dashboard'],
            ['url' => 'students.php', 'icon' => 'people', 'text' => 'Students'],
            ['url' => 'rooms.php', 'icon' => 'building', 'text' => 'Rooms'],
            ['url' => 'fees.php', 'icon' => 'cash-stack', 'text' => 'Fees'],
            ['url' => 'complaints.php', 'icon' => 'exclamation-triangle', 'text' => 'Complaints'],
            ['url' => 'reports.php', 'icon' => 'graph-up', 'text' => 'Reports'],
            ['url' => 'notifications.php', 'icon' => 'bell', 'text' => 'Notifications'],
        ],
        'student' => [
            ['url' => 'dashboard.php', 'icon' => 'house', 'text' => 'Dashboard'],
            ['url' => 'profile.php', 'icon' => 'person', 'text' => 'Profile'],
            ['url' => 'room.php', 'icon' => 'building', 'text' => 'My Room'],
            ['url' => 'fees.php', 'icon' => 'cash-stack', 'text' => 'My Fees'],
            ['url' => 'complaints.php', 'icon' => 'exclamation-triangle', 'text' => 'Complaints'],
        ],
        'staff' => [
            ['url' => 'dashboard.php', 'icon' => 'house', 'text' => 'Dashboard'],
            ['url' => 'rooms.php', 'icon' => 'building', 'text' => 'Rooms'],
            ['url' => 'complaints.php', 'icon' => 'exclamation-triangle', 'text' => 'Complaints'],
            ['url' => 'notifications.php', 'icon' => 'bell', 'text' => 'Notifications'],
        ]
    ];
    return $navItems[$role];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Hostel Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">    <style>
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: #343a40;
            padding-top: 20px;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 10px 20px;
            margin: 5px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
        }
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.15);
        }
        .logout-container {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            padding: 0 20px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .content-wrapper {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .alert {
            margin-bottom: 20px;
        }
        .invalid-feedback {
            display: none;
            font-size: 0.875em;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="text-center mb-4">
            <h5 class="text-white">Hostel Management</h5>
        </div>
        <nav class="nav flex-column">
            <?php foreach(getNavItems($_SESSION['role']) as $item): ?>
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === $item['url'] ? 'active' : ''; ?>" 
               href="<?php echo $item['url']; ?>">
                <i class="bi bi-<?php echo $item['icon']; ?> me-2"></i>
                <?php echo $item['text']; ?>
            </a>
            <?php endforeach; ?>
            <a class="nav-link" href="../auth/logout.php">
                <i class="bi bi-box-arrow-right me-2"></i>
                Logout
            </a>
        </nav>
    </div>
    <div class="main-content">
        <div class="content-wrapper">
            <?php 
            // Display any error messages
            if (isset($_GET['error'])) {
                display_alert($_GET['error'], 'error');
            }
            // Display any success messages
            if (isset($_GET['success'])) {
                display_alert($_GET['success'], 'success');
            }
            echo $content; 
            ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if(isset($pageScript)) echo $pageScript; ?>
</body>
</html>
