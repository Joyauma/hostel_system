<?php
session_start();
require_once '../config/database.php';

// Check if user is authorized
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

// Get student ID
$stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
$student_id = $student['id'];

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Get specific complaint details
            try {
                $stmt = $conn->prepare("
                    SELECT * FROM complaints 
                    WHERE id = ? AND student_id = ?
                ");
                $stmt->execute([$_GET['id'], $student_id]);
                $complaint = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($complaint) {
                    echo json_encode($complaint);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Complaint not found']);
                }
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        } else {
            // Get all complaints for the student
            try {
                $stmt = $conn->prepare("
                    SELECT * FROM complaints 
                    WHERE student_id = ? 
                    ORDER BY submitted_at DESC
                ");
                $stmt->execute([$student_id]);
                $complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode($complaints);
            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        }
        break;

    case 'POST':
        // Submit new complaint
        try {
            $category = $_POST['category'];
            $priority = $_POST['priority'];
            $description = $_POST['description'];

            $stmt = $conn->prepare("
                INSERT INTO complaints (student_id, category, priority, description) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$student_id, $category, $priority, $description]);

            // Send notification to staff/admin
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, message, type) 
                SELECT u.id, ?, 'complaint'
                FROM users u 
                WHERE u.role IN ('admin', 'staff')
            ");
            $message = "New complaint submitted: $category (Priority: $priority)";
            $stmt->execute([$message]);

            echo json_encode(['success' => true, 'message' => 'Complaint submitted successfully']);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;
}
?>
