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
            $stmt = $conn->query("SELECT * FROM rooms ORDER BY room_no");
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($rooms);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
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
        break;

    case 'PUT':
        // Update room
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $conn->prepare("UPDATE rooms SET type = ?, capacity = ? WHERE id = ?");
            $stmt->execute([$data['type'], $data['capacity'], $data['id']]);

            echo json_encode(['success' => true, 'message' => 'Room updated successfully']);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;

    case 'DELETE':
        // Delete room
        try {
            $id = $_GET['id'];
            
            // Check if room is occupied
            $stmt = $conn->prepare("SELECT occupied FROM rooms WHERE id = ?");
            $stmt->execute([$id]);
            $room = $stmt->fetch();
            
            if ($room['occupied'] > 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cannot delete occupied room']);
                exit();
            }

            $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success' => true, 'message' => 'Room deleted successfully']);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;
}
?>
