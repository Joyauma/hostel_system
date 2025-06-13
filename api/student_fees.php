<?php
session_start();
require_once '../config/database.php';

// Function to send JSON response
function send_json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Check if user is authorized
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    send_json_response([
        'success' => false,
        'message' => 'You are not authorized to access this resource'
    ], 403);
}

// Get student ID
try {
    $stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        send_json_response([
            'success' => false,
            'message' => 'Student record not found'
        ], 404);
    }
    
    $student_id = $student['id'];
} catch(PDOException $e) {
    send_json_response([
        'success' => false,
        'message' => 'Failed to retrieve student information'
    ], 500);
}

if (!isset($_GET['action'])) {
    send_json_response([
        'success' => false,
        'message' => 'No action specified'
    ], 400);
}

switch ($_GET['action']) {
    case 'summary':
        try {
            // Get total due amount
            $stmt = $conn->prepare("
                SELECT COALESCE(SUM(amount), 0) as total_due 
                FROM fees 
                WHERE student_id = ? AND status = 'Unpaid'
            ");
            $stmt->execute([$student_id]);
            $total_due = $stmt->fetch()['total_due'];

            // Get amount paid this month
            $stmt = $conn->prepare("
                SELECT COALESCE(SUM(amount), 0) as paid_this_month 
                FROM fees 
                WHERE student_id = ? 
                AND status = 'Paid' 
                AND MONTH(paid_at) = MONTH(CURRENT_DATE)
                AND YEAR(paid_at) = YEAR(CURRENT_DATE)
            ");
            $stmt->execute([$student_id]);
            $paid_this_month = $stmt->fetch()['paid_this_month'];

            // Get next due date
            $stmt = $conn->prepare("
                SELECT MIN(due_date) as next_due_date 
                FROM fees 
                WHERE student_id = ? 
                AND status = 'Unpaid'
                AND due_date >= CURRENT_DATE
            ");
            $stmt->execute([$student_id]);
            $next_due_date = $stmt->fetch()['next_due_date'];

            send_json_response([
                'success' => true,
                'data' => [
                    'total_due' => floatval($total_due),
                    'paid_this_month' => floatval($paid_this_month),
                    'next_due_date' => $next_due_date
                ]
            ]);
        } catch(PDOException $e) {
            send_json_response([
                'success' => false,
                'message' => 'Failed to retrieve fee summary'
            ], 500);
        }
        break;

    case 'history':
        try {
            // Get fee history
            $stmt = $conn->prepare("
                SELECT 
                    f.*, 
                    ft.name as fee_type_name,
                    ft.description as fee_type_description
                FROM fees f
                LEFT JOIN fee_types ft ON f.fee_type_id = ft.id
                WHERE f.student_id = ? 
                ORDER BY f.due_date DESC
            ");
            $stmt->execute([$student_id]);
            $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            send_json_response([
                'success' => true,
                'data' => $fees
            ]);
        } catch(PDOException $e) {
            send_json_response([
                'success' => false,
                'message' => 'Failed to retrieve fee history'
            ], 500);
        }
        break;

    default:
        send_json_response([
            'success' => false,
            'message' => 'Invalid action specified'
        ], 400);
}
?>
