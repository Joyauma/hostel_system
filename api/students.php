<?php
// Ensure no output before this point
error_reporting(E_ALL);
ini_set('display_errors', '0');

session_start();
require_once '../config/database.php';

// Set JSON header first
header('Content-Type: application/json');

// Check if user is authorized
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get the HTTP method
$method = $_SERVER['REQUEST_METHOD'];
if (isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

try {
    switch ($method) {
        case 'GET':
            // Get single student or list of students
            if (isset($_GET['id'])) {
                // Get single student
                $stmt = $conn->prepare("
                    SELECT s.*, u.email, r.room_no, r.id as room_id
                    FROM students s 
                    LEFT JOIN users u ON s.user_id = u.id 
                    LEFT JOIN room_allocations ra ON s.id = ra.student_id AND (ra.vacate_date IS NULL OR ra.vacate_date > CURRENT_DATE)
                    LEFT JOIN rooms r ON ra.room_id = r.id
                    WHERE s.id = ?
                ");
                $stmt->execute([$_GET['id']]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$student) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Student not found']);
                    exit();
                }

                echo json_encode(['success' => true, 'data' => $student]);
            } else {
                // Get all students with filters
                $query = "
                    SELECT s.*, u.email, r.room_no 
                    FROM students s 
                    LEFT JOIN users u ON s.user_id = u.id
                    LEFT JOIN room_allocations ra ON s.id = ra.student_id AND (ra.vacate_date IS NULL OR ra.vacate_date > CURRENT_DATE)
                    LEFT JOIN rooms r ON ra.room_id = r.id
                ";

                $params = [];
                $conditions = [];

                if (!empty($_GET['search'])) {
                    $search = '%' . $_GET['search'] . '%';
                    $conditions[] = "(s.name LIKE ? OR s.roll LIKE ? OR u.email LIKE ?)";
                    array_push($params, $search, $search, $search);
                }

                if (!empty($_GET['course'])) {
                    $conditions[] = "s.course = ?";
                    $params[] = $_GET['course'];
                }

                if (!empty($_GET['year'])) {
                    $conditions[] = "s.year_of_study = ?";
                    $params[] = $_GET['year'];
                }

                if (!empty($_GET['status'])) {
                    $conditions[] = "s.status = ?";
                    $params[] = $_GET['status'];
                }

                if (!empty($conditions)) {
                    $query .= " WHERE " . implode(" AND ", $conditions);
                }

                $query .= " ORDER BY s.name ASC";

                $stmt = $conn->prepare($query);
                $stmt->execute($params);
                $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'data' => $students]);
            }
            break;

        case 'DELETE':
            if (!isset($_POST['student_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Student ID is required']);
                exit();
            }

            try {
                $conn->beginTransaction();

                // Get student info to check if they have a room allocation
                $stmt = $conn->prepare("
                    SELECT id FROM room_allocations 
                    WHERE student_id = ? AND (vacate_date IS NULL OR vacate_date > CURRENT_DATE)
                ");
                $stmt->execute([$_POST['student_id']]);
                $allocation = $stmt->fetch();

                // If student has room allocation, update room occupancy
                if ($allocation) {
                    $stmt = $conn->prepare("
                        UPDATE rooms r 
                        JOIN room_allocations ra ON r.id = ra.room_id 
                        SET r.occupied = r.occupied - 1 
                        WHERE ra.student_id = ? AND (ra.vacate_date IS NULL OR ra.vacate_date > CURRENT_DATE)
                    ");
                    $stmt->execute([$_POST['student_id']]);
                }

                // Delete room allocations
                $stmt = $conn->prepare("DELETE FROM room_allocations WHERE student_id = ?");
                $stmt->execute([$_POST['student_id']]);

                // Delete student record
                $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
                $stmt->execute([$_POST['student_id']]);

                if ($stmt->rowCount() === 0) {
                    throw new Exception('Student not found');
                }

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);

            } catch (Exception $e) {
                $conn->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error deleting student: ' . $e->getMessage()]);
            }
            break;

        case 'PUT':
            if (!isset($_POST['student_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Student ID is required']);
                exit();
            }

            $conn->beginTransaction();

            try {
                // Update student details
                $stmt = $conn->prepare("
                    UPDATE students 
                    SET name = ?,
                        phone = ?,
                        course = ?,
                        year_of_study = ?,
                        gender = ?,
                        status = ?,
                        address = ?,
                        guardian_name = ?,
                        guardian_phone = ?,
                        guardian_address = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $_POST['name'],
                    $_POST['phone'],
                    $_POST['course'],
                    $_POST['year_of_study'],
                    $_POST['gender'],
                    $_POST['status'],
                    $_POST['address'],
                    $_POST['guardian_name'],
                    $_POST['guardian_phone'],
                    $_POST['guardian_address'],
                    $_POST['student_id']
                ]);

                // Handle room assignment if provided
                if (!empty($_POST['room_id'])) {
                    // Check if student already has a room
                    $stmt = $conn->prepare("
                        SELECT room_id 
                        FROM room_allocations 
                        WHERE student_id = ? 
                        AND (vacate_date IS NULL OR vacate_date > CURRENT_DATE)
                    ");
                    $stmt->execute([$_POST['student_id']]);
                    $currentRoom = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($currentRoom && $currentRoom['room_id'] != $_POST['room_id']) {
                        // Update existing allocation with vacate date
                        $stmt = $conn->prepare("
                            UPDATE room_allocations 
                            SET vacate_date = CURRENT_DATE
                            WHERE student_id = ? AND room_id = ?
                        ");
                        $stmt->execute([$_POST['student_id'], $currentRoom['room_id']]);

                        // Create new allocation
                        $stmt = $conn->prepare("
                            INSERT INTO room_allocations (student_id, room_id, allocation_date, status)
                            VALUES (?, ?, CURRENT_DATE, 'approved')
                        ");
                        $stmt->execute([$_POST['student_id'], $_POST['room_id']]);
                    } elseif (!$currentRoom) {
                        // Create new allocation
                        $stmt = $conn->prepare("
                            INSERT INTO room_allocations (student_id, room_id, allocation_date, status)
                            VALUES (?, ?, CURRENT_DATE, 'approved')
                        ");
                        $stmt->execute([$_POST['student_id'], $_POST['room_id']]);
                    }

                    // Update room occupancy
                    $stmt = $conn->prepare("
                        UPDATE rooms r
                        SET occupied = (
                            SELECT COUNT(DISTINCT ra.student_id)
                            FROM room_allocations ra
                            WHERE ra.room_id = r.id
                            AND (ra.vacate_date IS NULL OR ra.vacate_date > CURRENT_DATE)
                        )
                        WHERE r.id = ? OR r.id = ?
                    ");
                    $stmt->execute([$_POST['room_id'], $currentRoom['room_id'] ?? $_POST['room_id']]);
                }

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Student updated successfully']);
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
            break;

        case 'DELETE':
            if (!isset($_POST['id'])) {
                throw new Exception('Student ID is required');
            }

            $conn->beginTransaction();

            try {
                // Get user_id first
                $stmt = $conn->prepare("SELECT user_id FROM students WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$student) {
                    throw new Exception('Student not found');
                }

                // Delete student record
                $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
                $stmt->execute([$_POST['id']]);

                // Delete user account
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$student['user_id']]);

                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
            } catch (Exception $e) {
                $conn->rollBack();
                throw $e;
            }
            break;

        case 'POST':
            try {
                $conn->beginTransaction();

                // First create user account
                $stmt = $conn->prepare("
                    INSERT INTO users (email, role) 
                    VALUES (?, 'student')
                ");
                $stmt->execute([$_POST['email']]);
                $userId = $conn->lastInsertId();

                // Then create student record
                $stmt = $conn->prepare("
                    INSERT INTO students (
                        user_id, name, phone, dob, gender, address,
                        course, year_of_study, guardian_name,
                        guardian_phone, guardian_address, status
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?,
                        ?, ?, 'active'
                    )
                ");

                $stmt->execute([
                    $userId,
                    $_POST['name'],
                    $_POST['phone'],
                    $_POST['dob'],
                    $_POST['gender'] ?? 'not specified',
                    $_POST['address'],
                    $_POST['course'],
                    $_POST['year_of_study'],
                    $_POST['guardian_name'],
                    $_POST['guardian_phone'],
                    $_POST['guardian_address']
                ]);

                $studentId = $conn->lastInsertId();

                // If room is assigned, create room allocation
                if (!empty($_POST['room_id'])) {
                    // Check if room has space
                    $stmt = $conn->prepare("
                        SELECT capacity, occupied 
                        FROM rooms 
                        WHERE id = ? AND occupied < capacity
                    ");
                    $stmt->execute([$_POST['room_id']]);
                    $room = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$room) {
                        throw new Exception('Selected room is not available');
                    }

                    // Create room allocation
                    $stmt = $conn->prepare("
                        INSERT INTO room_allocations (
                            student_id, room_id, allocation_date
                        ) VALUES (?, ?, CURRENT_DATE)
                    ");
                    $stmt->execute([$studentId, $_POST['room_id']]);

                    // Update room occupancy
                    $stmt = $conn->prepare("
                        UPDATE rooms 
                        SET occupied = occupied + 1 
                        WHERE id = ?
                    ");
                    $stmt->execute([$_POST['room_id']]);
                }

                $conn->commit();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Student added successfully',
                    'data' => ['id' => $studentId]
                ]);

            } catch (Exception $e) {
                $conn->rollBack();
                http_response_code(500);
                echo json_encode([
                    'success' => false, 
                    'message' => 'Error adding student: ' . $e->getMessage()
                ]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
