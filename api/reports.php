<?php
session_start();
require_once '../config/database.php';

// Check if user is authorized
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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
        case 'total_students':
            $stmt = $conn->query("SELECT COUNT(*) as total FROM students");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'success' => true,
                'data' => ['total' => (int)$result['total']]
            ]);
            break;

        case 'occupied_rooms':
            $stmt = $conn->query("
                SELECT 
                    (SELECT COUNT(DISTINCT room_id) FROM room_assignments WHERE status = 'active') as occupied,
                    COUNT(*) as total,
                    SUM(capacity) as total_capacity
                FROM rooms
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'success' => true,
                'data' => [
                    'occupied' => (int)$result['occupied'],
                    'total' => (int)$result['total'],
                    'capacity' => (int)$result['total_capacity']
                ]
            ]);
            break;

        case 'pending_complaints':
            $stmt = $conn->query("
                SELECT COUNT(*) as total 
                FROM complaints 
                WHERE status IN ('pending', 'in_progress')
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'success' => true,
                'data' => ['total' => (int)$result['total']]
            ]);
            break;

        case 'monthly_revenue':
            $stmt = $conn->query("
                SELECT COALESCE(SUM(amount), 0) as total
                FROM fee_payments
                WHERE YEAR(payment_date) = YEAR(CURRENT_DATE)
                AND MONTH(payment_date) = MONTH(CURRENT_DATE)
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode([
                'success' => true,
                'data' => ['amount' => (float)$result['total']]
            ]);
            break;

        case 'fee_collection':
            // Get last 6 months of fee collection
            $stmt = $conn->query("
                SELECT 
                    DATE_FORMAT(payment_date, '%b %Y') as month,
                    SUM(amount_paid) as amount
                FROM student_fees
                WHERE payment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
                ORDER BY payment_date ASC
            ");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $data = [
                'labels' => array_column($results, 'month'),
                'values' => array_column($results, 'amount')
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;

        case 'room_type_stats':
            $stmt = $conn->query("
                SELECT 
                    type,
                    COUNT(*) as total_rooms,
                    SUM(occupied) as occupied_rooms,
                    SUM(capacity) as total_capacity
                FROM rooms
                GROUP BY type
            ");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode([
                'success' => true,
                'data' => $results
            ]);
            break;

        case 'recent_activities':
            $stmt = $conn->query("
                SELECT 
                    al.action,
                    al.description,
                    al.created_at,
                    CONCAT(s.name, ' (', s.student_id, ')') as student_name
                FROM activity_logs al
                LEFT JOIN students s ON al.user_id = s.user_id
                ORDER BY al.created_at DESC
                LIMIT 10
            ");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode([
                'success' => true,
                'data' => $results
            ]);
            break;

        case 'recent_complaints':
            $stmt = $conn->query("
                SELECT c.*, s.first_name, s.last_name,
                       CONCAT(s.first_name, ' ', s.last_name) as student_name
                FROM complaints c
                JOIN students s ON c.student_id = s.id
                ORDER BY c.created_at DESC
                LIMIT 5
            ");
            echo json_encode([
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
            break;

        case 'recent_applications':
            $stmt = $conn->query("
                SELECT ra.*, r.room_number,
                       s.registration_number,
                       CONCAT(s.first_name, ' ', s.last_name) as student_name
                FROM room_assignments ra
                JOIN rooms r ON ra.room_id = r.id
                JOIN students s ON ra.student_id = s.id
                WHERE ra.status = 'pending'
                ORDER BY ra.created_at DESC
                LIMIT 5
            ");
            echo json_encode([
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
            break;

        case 'fee_collection_chart':
            // Get last 6 months of fee collection data
            $stmt = $conn->query("
                SELECT 
                    DATE_FORMAT(payment_date, '%Y-%m') as month,
                    SUM(amount) as total
                FROM fee_payments
                WHERE payment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
                ORDER BY month ASC
            ");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $labels = [];
            $values = [];
            foreach ($results as $row) {
                $date = new DateTime($row['month'] . '-01');
                $labels[] = $date->format('M Y');
                $values[] = (float)$row['total'];
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'labels' => $labels,
                    'values' => $values
                ]
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action specified'
            ]);
    }
} catch(PDOException $e) {
    error_log("Reports API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching the report'
    ]);
}
?>
