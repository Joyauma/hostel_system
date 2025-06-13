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
        // Get current room allocation
        try {
            $stmt = $conn->prepare("
                SELECT r.* 
                FROM rooms r 
                JOIN students s ON s.room_id = r.id 
                WHERE s.id = ?
            ");
            $stmt->execute([$student_id]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'room' => $room]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;

    case 'POST':
        // Request room allocation
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $room_id = $data['room_id'];

            // Check if student already has a room
            $stmt = $conn->prepare("SELECT room_id FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($current['room_id']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'You already have a room allocated']);
                exit();
            }

            // Check if room has space
            $stmt = $conn->prepare("SELECT capacity, occupied FROM rooms WHERE id = ?");
            $stmt->execute([$room_id]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($room['occupied'] >= $room['capacity']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Room is full']);
                exit();
            }

            // Start transaction
            $conn->beginTransaction();

            // Update student's room
            $stmt = $conn->prepare("UPDATE students SET room_id = ? WHERE id = ?");
            $stmt->execute([$room_id, $student_id]);

            // Update room occupancy
            $stmt = $conn->prepare("UPDATE rooms SET occupied = occupied + 1 WHERE id = ?");
            $stmt->execute([$room_id]);

            // Add to logs
            $stmt = $conn->prepare("INSERT INTO logs (student_id, action) VALUES (?, 'admission')");
            $stmt->execute([$student_id]);

            // Create notification for admin
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'room_allocation')");
            $adminId = 1; // Assuming admin has ID 1
            $message = "New room allocation request from student ID: $student_id";
            $stmt->execute([$adminId, $message]);

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Room allocated successfully']);
        } catch(PDOException $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;

    case 'PUT':
        // Request room change
        try {
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, message, type) 
                VALUES (?, ?, 'room_change')
            ");
            $adminId = 1; // Assuming admin has ID 1
            $message = "Room change request from student ID: $student_id";
            $stmt->execute([$adminId, $message]);

            echo json_encode(['success' => true, 'message' => 'Room change request submitted']);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;
}
?>
