<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Helper function to send JSON response
function send_json_response($success, $message, $data = null, $status = 200) {
    http_response_code($status);
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// Check if user is authorized
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    send_json_response(false, 'Unauthorized', null, 403);
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $roomId = !empty($_POST['room_id']) ? (int)$_POST['room_id'] : null;

    // Validate required fields
    if (empty($name) || empty($email) || empty($phone)) {
        send_json_response(false, 'Name, email and phone are required fields', null, 400);
    }

    try {
        $conn->beginTransaction();

        // Check if user exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $userId = $user['id'];
        } else {
            // Generate username from email
            $username = strtolower(explode('@', $email)[0]);
            $baseUsername = $username;
            $counter = 1;

            do {
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if (!$stmt->fetch()) break;
                $username = $baseUsername . $counter++;
            } while (true);

            // Create new user
            $defaultPassword = password_hash('student123', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'student')");
            $stmt->execute([$username, $email, $defaultPassword]);
            $userId = $conn->lastInsertId();
        }

        // Create student record
        $stmt = $conn->prepare("INSERT INTO students (name, phone, user_id) VALUES (?, ?, ?)");
        $stmt->execute([$name, $phone, $userId]);
        $studentId = $conn->lastInsertId();

        // Handle room assignment if specified
        if ($roomId) {
            // Get room details and current occupancy
            $stmt = $conn->prepare("
                SELECT 
                    r.id,
                    r.room_no,
                    r.capacity,
                    COUNT(DISTINCT CASE 
                        WHEN ra.vacate_date IS NULL OR ra.vacate_date > CURRENT_DATE 
                        THEN ra.student_id 
                    END) as current_occupants
                FROM rooms r
                LEFT JOIN room_allocations ra ON r.id = ra.room_id
                WHERE r.id = ?
                GROUP BY r.id, r.room_no, r.capacity
            ");
            $stmt->execute([$roomId]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$room) {
                throw new Exception("Invalid room selected");
            }

            if ($room['current_occupants'] >= $room['capacity']) {
                throw new Exception(sprintf(
                    "Room %s is at full capacity (Current: %d/%d)",
                    $room['room_no'],
                    $room['current_occupants'],
                    $room['capacity']
                ));
            }

            // Assign room
            $stmt = $conn->prepare("
                INSERT INTO room_allocations (student_id, room_id, allocation_date) 
                VALUES (?, ?, CURRENT_DATE)
            ");
            $stmt->execute([$studentId, $roomId]);
        }

        $conn->commit();
        send_json_response(true, 'Student added successfully');

    } catch (Exception $e) {
        $conn->rollBack();
        send_json_response(false, $e->getMessage(), null, 500);
    }
}

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                s.*, u.email, r.room_no, r.id as room_id
            FROM students s
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN room_allocations ra ON s.id = ra.student_id 
                AND (ra.vacate_date IS NULL OR ra.vacate_date > CURRENT_DATE)
            LEFT JOIN rooms r ON ra.room_id = r.id
            WHERE s.id = ?
        ");
        $stmt->execute([$_GET['id']]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            send_json_response(false, 'Student not found', null, 404);
        }

        send_json_response(true, 'Student details retrieved successfully', $student);
    } catch (Exception $e) {
        send_json_response(false, $e->getMessage(), null, 500);
    }
}
?>
