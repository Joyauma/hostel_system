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

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Fetch all rooms
        try {
            // Check if requesting occupants for a specific room
            if (isset($_GET['action']) && $_GET['action'] === 'occupants' && isset($_GET['id'])) {
                $stmt = $conn->prepare("
                    SELECT s.id as student_id, s.name, ra.allocation_date 
                    FROM students s 
                    JOIN room_allocations ra ON s.id = ra.student_id 
                    WHERE ra.room_id = ? AND (ra.vacate_date IS NULL OR ra.vacate_date > CURRENT_DATE)
                    ORDER BY ra.allocation_date DESC
                ");
                $stmt->execute([$_GET['id']]);
                $occupants = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($occupants);
                exit();
            }

            // Regular room listing
            $stmt = $conn->query("SELECT * FROM rooms ORDER BY room_no");
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($rooms);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        // Add new room
        try {
            $room_no = $_POST['room_no'];
            $type = $_POST['type'];
            $capacity = $_POST['capacity'];

            // Check if room number already exists
            $stmt = $conn->prepare("SELECT id FROM rooms WHERE room_no = ?");
            $stmt->execute([$room_no]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Room number already exists']);
                exit();
            }

            // Insert new room
            $stmt = $conn->prepare("INSERT INTO rooms (room_no, type, capacity) VALUES (?, ?, ?)");
            $stmt->execute([$room_no, $type, $capacity]);

            echo json_encode(['success' => true, 'message' => 'Room added successfully']);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;    case 'PUT':
        // Update room
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (!isset($data['id']) || !isset($data['room_no']) || !isset($data['type']) || !isset($data['capacity'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit();
            }

            // Check if room number exists (excluding current room)
            $stmt = $conn->prepare("SELECT id FROM rooms WHERE room_no = ? AND id != ?");
            $stmt->execute([$data['room_no'], $data['id']]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Room number already exists']);
                exit();
            }

            // Check if new capacity is less than current occupancy
            $stmt = $conn->prepare("SELECT occupied FROM rooms WHERE id = ?");
            $stmt->execute([$data['id']]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($room && $data['capacity'] < $room['occupied']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'New capacity cannot be less than current occupancy']);
                exit();
            }

            // Update room
            $stmt = $conn->prepare("UPDATE rooms SET room_no = ?, type = ?, capacity = ? WHERE id = ?");
            $stmt->execute([$data['room_no'], $data['type'], $data['capacity'], $data['id']]);

            echo json_encode(['success' => true, 'message' => 'Room updated successfully']);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;    case 'DELETE':
        // Delete room
        try {
            // Get ID from URL or request body
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            
            if (!$id) {
                // Try to get ID from DELETE request body
                $data = json_decode(file_get_contents('php://input'), true);
                $id = isset($data['id']) ? $data['id'] : null;
            }
            
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Room ID is required']);
                exit();
            }
            
            // Check if room exists
            $stmt = $conn->prepare("SELECT id, occupied FROM rooms WHERE id = ?");
            $stmt->execute([$id]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$room) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Room not found']);
                exit();
            }
            
            // Check if room is occupied
            if ($room['occupied'] > 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cannot delete room that is currently occupied']);
                exit();
            }

            // Delete room
            $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Room deleted successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to delete room']);
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
}
?>
