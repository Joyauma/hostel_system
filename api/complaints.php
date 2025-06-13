<?php
session_start();
require_once '../config/database.php';

// Check if user is authorized
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
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
            if ($_SESSION['role'] === 'admin') {
                // Get recent complaints for admin
                $stmt = $conn->query("
                    SELECT 
                        c.*,
                        s.name as student_name,
                        r.room_no,
                        CONCAT(b.name, ' - ', r.room_no) as location
                    FROM complaints c
                    JOIN students s ON c.student_id = s.id
                    LEFT JOIN room_allocations ra ON s.id = ra.student_id AND ra.status = 'approved'
                    LEFT JOIN rooms r ON ra.room_id = r.id
                    LEFT JOIN blocks b ON r.block_id = b.id
                    WHERE c.status != 'resolved'
                    ORDER BY 
                        CASE c.priority
                            WHEN 'high' THEN 1
                            WHEN 'medium' THEN 2
                            WHEN 'low' THEN 3
                        END,
                        c.created_at DESC
                    LIMIT 5
                ");
            } else {
                // Get recent complaints for student
                $stmt = $conn->prepare("
                    SELECT c.*
                    FROM complaints c
                    JOIN students s ON c.student_id = s.id
                    WHERE s.user_id = ?
                    ORDER BY c.created_at DESC
                    LIMIT 5
                ");
                $stmt->execute([$_SESSION['user_id']]);
            }
            $complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $complaints
            ]);
            break;

        case 'all':
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = ($page - 1) * $limit;

            if ($_SESSION['role'] === 'admin') {
                // Get all complaints for admin with pagination
                $query = "
                    SELECT 
                        c.*,
                        s.name as student_name,
                        r.room_no,
                        CONCAT(b.name, ' - ', r.room_no) as location
                    FROM complaints c
                    JOIN students s ON c.student_id = s.id
                    LEFT JOIN room_allocations ra ON s.id = ra.student_id AND ra.status = 'approved'
                    LEFT JOIN rooms r ON ra.room_id = r.id
                    LEFT JOIN blocks b ON r.block_id = b.id
                    ORDER BY c.created_at DESC
                    LIMIT ? OFFSET ?
                ";
                $stmt = $conn->prepare($query);
                $stmt->execute([$limit, $offset]);
            } else {
                // Get all complaints for student with pagination
                $query = "
                    SELECT c.*
                    FROM complaints c
                    JOIN students s ON c.student_id = s.id
                    WHERE s.user_id = ?
                    ORDER BY c.created_at DESC
                    LIMIT ? OFFSET ?
                ";
                $stmt = $conn->prepare($query);
                $stmt->execute([$_SESSION['user_id'], $limit, $offset]);
            }
            $complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count for pagination
            if ($_SESSION['role'] === 'admin') {
                $countStmt = $conn->query("SELECT COUNT(*) as total FROM complaints");
            } else {
                $countStmt = $conn->prepare("
                    SELECT COUNT(*) as total
                    FROM complaints c
                    JOIN students s ON c.student_id = s.id
                    WHERE s.user_id = ?
                ");
                $countStmt->execute([$_SESSION['user_id']]);
            }
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            echo json_encode([
                'success' => true,
                'data' => [
                    'complaints' => $complaints,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => ceil($total / $limit),
                        'total_items' => (int)$total
                    ]
                ]
            ]);
            break;

        case 'submit':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit();
            }

            if ($_SESSION['role'] !== 'student') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only students can submit complaints']);
                exit();
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Get student ID
            $stmt = $conn->prepare("SELECT id FROM students WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            // Start transaction
            $conn->beginTransaction();

            try {
                // Insert complaint
                $stmt = $conn->prepare("
                    INSERT INTO complaints (
                        student_id, category, subject, description, 
                        priority, status
                    ) VALUES (?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([
                    $student['id'],
                    $data['category'],
                    $data['subject'],
                    $data['description'],
                    $data['priority'] ?? 'medium'
                ]);
                $complaintId = $conn->lastInsertId();

                // Create notification for admin
                $stmt = $conn->prepare("
                    INSERT INTO notifications (user_id, title, message, type)
                    SELECT id, 'New Complaint', ?, 'complaint'
                    FROM users WHERE role = 'admin'
                ");
                $stmt->execute(["New complaint submitted: " . $data['subject']]);

                $conn->commit();
                echo json_encode([
                    'success' => true,
                    'message' => 'Complaint submitted successfully',
                    'data' => ['complaint_id' => $complaintId]
                ]);
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
            break;

        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                exit();
            }

            if ($_SESSION['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only admin can update complaints']);
                exit();
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Start transaction
            $conn->beginTransaction();

            try {
                // Update complaint
                $stmt = $conn->prepare("
                    UPDATE complaints 
                    SET 
                        status = ?,
                        assigned_to = ?,
                        resolution = ?,
                        resolved_at = CASE WHEN ? = 'resolved' THEN CURRENT_TIMESTAMP ELSE NULL END
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['status'],
                    $data['assigned_to'] ?? null,
                    $data['resolution'] ?? null,
                    $data['status'],
                    $data['complaint_id']
                ]);

                // Get complaint details for notification
                $stmt = $conn->prepare("
                    SELECT c.*, s.user_id, s.name 
                    FROM complaints c 
                    JOIN students s ON c.student_id = s.id 
                    WHERE c.id = ?
                ");
                $stmt->execute([$data['complaint_id']]);
                $complaint = $stmt->fetch(PDO::FETCH_ASSOC);

                // Create notification for student
                $stmt = $conn->prepare("
                    INSERT INTO notifications (user_id, title, message, type)
                    VALUES (?, 'Complaint Update', ?, 'complaint')
                ");
                $message = "Your complaint has been {$data['status']}. ";
                if ($data['resolution']) {
                    $message .= "Resolution: {$data['resolution']}";
                }
                $stmt->execute([$complaint['user_id'], $message]);

                $conn->commit();
                echo json_encode([
                    'success' => true,
                    'message' => 'Complaint updated successfully'
                ]);
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch(PDOException $e) {
    error_log("Complaints API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing the complaint'
    ]);
}
?>
