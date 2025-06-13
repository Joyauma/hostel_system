<?php
session_start();
require_once '../config/database.php';

// Check if user is authorized
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

if (!isset($_GET['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action not specified']);
    exit();
}

try {
    switch ($_GET['action']) {
        case 'recent':
            // Get recent notifications
            $stmt = $conn->prepare("
                SELECT 
                    n.*,
                    CASE 
                        WHEN n.type = 'registration' THEN 'bg-primary'
                        WHEN n.type = 'complaint' THEN 'bg-warning'
                        WHEN n.type = 'fee' THEN 'bg-success'
                        ELSE 'bg-info'
                    END as badge_class
                FROM notifications n
                WHERE n.user_id = ?
                ORDER BY n.created_at DESC
                LIMIT 5
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $notifications
            ]);
            break;

        case 'unread_count':
            // Get count of unread notifications
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count
                FROM notifications
                WHERE user_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => ['count' => (int)$result['count']]
            ]);
            break;

        case 'mark_read':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit();
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            if (isset($data['notification_id'])) {
                // Mark single notification as read
                $stmt = $conn->prepare("
                    UPDATE notifications 
                    SET is_read = TRUE 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$data['notification_id'], $_SESSION['user_id']]);
            } else {
                // Mark all notifications as read
                $stmt = $conn->prepare("
                    UPDATE notifications 
                    SET is_read = TRUE 
                    WHERE user_id = ?
                ");
                $stmt->execute([$_SESSION['user_id']]);
            }
            
            echo json_encode(['success' => true]);
            break;

        case 'all':
            // Get all notifications with pagination
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = ($page - 1) * $limit;

            $stmt = $conn->prepare("
                SELECT 
                    n.*,
                    CASE 
                        WHEN n.type = 'registration' THEN 'bg-primary'
                        WHEN n.type = 'complaint' THEN 'bg-warning'
                        WHEN n.type = 'fee' THEN 'bg-success'
                        ELSE 'bg-info'
                    END as badge_class
                FROM notifications n
                WHERE n.user_id = ?
                ORDER BY n.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$_SESSION['user_id'], $limit, $offset]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count for pagination
            $stmt = $conn->prepare("
                SELECT COUNT(*) as total
                FROM notifications
                WHERE user_id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'notifications' => $notifications,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => ceil($total / $limit),
                        'total_items' => (int)$total
                    ]
                ]
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch(PDOException $e) {
    error_log("Notifications API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing notifications'
    ]);
}
?>
