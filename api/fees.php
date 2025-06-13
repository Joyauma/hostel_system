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

// Helper function to send notification
function sendNotification($conn, $userId, $message, $type) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $message, $type]);
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['action']) && $_GET['action'] === 'dashboard') {
            // Get dashboard statistics
            try {
                $stats = [
                    'total' => $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM fees")->fetch()['total'],
                    'collected' => $conn->query("SELECT COALESCE(SUM(amount), 0) as collected FROM fees WHERE status='Paid'")->fetch()['collected'],
                    'pending' => $conn->query("SELECT COALESCE(SUM(amount), 0) as pending FROM fees WHERE status='Unpaid'")->fetch()['pending']
                ];
                echo json_encode($stats);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        } else {
            // Get all fees with student details
            try {
                $stmt = $conn->query("
                    SELECT f.*, s.name as student_name, r.room_no 
                    FROM fees f 
                    JOIN students s ON f.student_id = s.id 
                    LEFT JOIN rooms r ON s.room_id = r.id 
                    ORDER BY f.due_date DESC
                ");
                $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($fees);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        }
        break;

    case 'POST':
        if (isset($_GET['action']) && $_GET['action'] === 'generate') {
            // Generate monthly fees for all students
            try {
                $month = $_POST['month'];
                $amount = $_POST['amount'];
                $due_date = $_POST['due_date'];

                $conn->beginTransaction();

                // Get all active students
                $students = $conn->query("SELECT id, user_id, name FROM students WHERE room_id IS NOT NULL")->fetchAll();

                foreach ($students as $student) {
                    // Insert fee record
                    $stmt = $conn->prepare("
                        INSERT INTO fees (student_id, amount, due_date, status) 
                        VALUES (?, ?, ?, 'Unpaid')
                    ");
                    $stmt->execute([$student['id'], $amount, $due_date]);

                    // Send notification
                    $message = "Monthly hostel fee for $month has been generated. Amount: ₹$amount, Due Date: $due_date";
                    sendNotification($conn, $student['user_id'], $message, 'fee_generated');
                }

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Monthly fees generated successfully']);
            } catch(PDOException $e) {
                $conn->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        } else {
            // Add individual fee
            try {
                $student_id = $_POST['student_id'];
                $amount = $_POST['amount'];
                $due_date = $_POST['due_date'];
                $description = $_POST['description'];

                $conn->beginTransaction();

                // Insert fee record
                $stmt = $conn->prepare("
                    INSERT INTO fees (student_id, amount, due_date, description, status) 
                    VALUES (?, ?, ?, ?, 'Unpaid')
                ");
                $stmt->execute([$student_id, $amount, $due_date, $description]);

                // Get student details for notification
                $stmt = $conn->prepare("SELECT user_id, name FROM students WHERE id = ?");
                $stmt->execute([$student_id]);
                $student = $stmt->fetch();

                // Send notification
                $message = "New fee has been added: $description. Amount: ₹$amount, Due Date: $due_date";
                sendNotification($conn, $student['user_id'], $message, 'fee_added');

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Fee added successfully']);
            } catch(PDOException $e) {
                $conn->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($data['action'] === 'mark_paid') {
            try {
                $conn->beginTransaction();

                // Update fee status
                $stmt = $conn->prepare("
                    UPDATE fees 
                    SET status = 'Paid', paid_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                $stmt->execute([$data['id']]);

                // Get fee details for notification
                $stmt = $conn->prepare("
                    SELECT f.amount, s.user_id, s.name 
                    FROM fees f 
                    JOIN students s ON f.student_id = s.id 
                    WHERE f.id = ?
                ");
                $stmt->execute([$data['id']]);
                $fee = $stmt->fetch();

                // Send notification
                $message = "Payment of ₹{$fee['amount']} has been recorded for {$fee['name']}.";
                sendNotification($conn, $fee['user_id'], $message, 'payment_recorded');

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Payment recorded successfully']);
            } catch(PDOException $e) {
                $conn->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        }
        break;
}
?>
