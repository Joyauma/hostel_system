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

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['action']) && $_GET['action'] === 'courses') {
            // Get unique courses list
            try {
                $stmt = $conn->query("SELECT DISTINCT course FROM students ORDER BY course");
                $courses = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo json_encode(['success' => true, 'data' => $courses]);
            } catch(PDOException $e) {
                error_log("Error fetching courses: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
            exit();
        }

        if (isset($_GET['id'])) {
            // Get single student details
            try {
                $includeExtra = isset($_GET['include']) ? explode(',', $_GET['include']) : [];
                
                $query = "SELECT s.*, u.email, u.username 
                         FROM students s 
                         JOIN users u ON s.user_id = u.id 
                         WHERE s.id = ?";
                
                $stmt = $conn->prepare($query);
                $stmt->execute([$_GET['id']]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$student) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Student not found']);
                    exit();
                }

                // Include room information if requested
                if (in_array('room', $includeExtra)) {
                    $stmt = $conn->prepare("
                        SELECT r.* FROM rooms r 
                        JOIN room_assignments ra ON r.id = ra.room_id 
                        WHERE ra.student_id = ? AND ra.status = 'active'
                    ");
                    $stmt->execute([$_GET['id']]);
                    $student['room'] = $stmt->fetch(PDO::FETCH_ASSOC);
                }

                // Include fees information if requested
                if (in_array('fees', $includeExtra)) {
                    // Get total due
                    $stmt = $conn->prepare("
                        SELECT 
                            COALESCE(SUM(amount), 0) as total_due 
                        FROM fees 
                        WHERE student_id = ? AND status = 'unpaid'
                    ");
                    $stmt->execute([$_GET['id']]);
                    $fees = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Get last payment
                    $stmt = $conn->prepare("
                        SELECT amount, payment_date as date 
                        FROM fee_payments 
                        WHERE student_id = ? 
                        ORDER BY payment_date DESC 
                        LIMIT 1
                    ");
                    $stmt->execute([$_GET['id']]);
                    $lastPayment = $stmt->fetch(PDO::FETCH_ASSOC);

                    $student['fees'] = [
                        'total_due' => (float)$fees['total_due'],
                        'last_payment' => $lastPayment
                    ];
                }

                echo json_encode(['success' => true, 'data' => $student]);

            } catch(PDOException $e) {
                error_log("Error fetching student: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        } else {
            // Get all students list
            try {
                $query = "
                    SELECT s.*, u.email,
                           r.room_number
                    FROM students s
                    JOIN users u ON s.user_id = u.id
                    LEFT JOIN room_assignments ra ON s.id = ra.student_id AND ra.status = 'active'
                    LEFT JOIN rooms r ON ra.room_id = r.id
                    ORDER BY s.roll
                ";
                $stmt = $conn->query($query);
                $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $students]);
            } catch(PDOException $e) {
                error_log("Error fetching students: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        }
        break;

    case 'POST':
        // Add new student
        try {
            $conn->beginTransaction();            // Generate roll number
            $year = date('Y');
            $stmt = $conn->query("SELECT MAX(CAST(SUBSTRING(roll, 6) AS UNSIGNED)) as max_num 
                                FROM students 
                                WHERE roll LIKE '$year%'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextNum = ($result['max_num'] ?? 0) + 1;
            $roll = $year . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

            // Create user account            $username = strtolower(str_replace(' ', '', $_POST['first_name'])) . $nextNum;
            $defaultPassword = hash('sha256', $roll); // Initial password is roll number
            $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO users (username, email, password, role) 
                VALUES (?, ?, ?, 'student')
            ");
            $stmt->execute([$username, $_POST['email'], $hashedPassword]);
            $userId = $conn->lastInsertId();            // Create student record
            $stmt = $conn->prepare("
                INSERT INTO students (
                    user_id, roll, first_name, last_name,
                    phone, dob, gender, address, course, year_of_study,
                    guardian_name, guardian_phone, guardian_address, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $userId, $roll, $_POST['first_name'], $_POST['last_name'],
                $_POST['phone'], $_POST['dob'], $_POST['gender'], $_POST['address'],
                $_POST['course'], $_POST['year_of_study'], $_POST['guardian_name'],
                $_POST['guardian_phone'], $_POST['guardian_address']
            ]);            $conn->commit();
            echo json_encode([
                'success' => true, 
                'message' => 'Student added successfully',
                'data' => ['roll' => $roll]
            ]);

        } catch(PDOException $e) {
            $conn->rollBack();
            error_log("Error adding student: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;

    case 'PUT':
        // Update student
        parse_str(file_get_contents("php://input"), $_PUT);
        
        try {
            $conn->beginTransaction();

            // Update student record
            $stmt = $conn->prepare("
                UPDATE students 
                SET first_name = ?, last_name = ?, phone = ?,
                    course = ?, year_of_study = ?, guardian_name = ?,
                    guardian_phone = ?, guardian_address = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_PUT['first_name'], $_PUT['last_name'], $_PUT['phone'],
                $_PUT['course'], $_PUT['year_of_study'], $_PUT['guardian_name'],
                $_PUT['guardian_phone'], $_PUT['guardian_address'], $_PUT['status'],
                $_PUT['student_id']
            ]);

            // Update user email
            $stmt = $conn->prepare("
                UPDATE users 
                SET email = ?
                WHERE id = (SELECT user_id FROM students WHERE id = ?)
            ");
            $stmt->execute([$_PUT['email'], $_PUT['student_id']]);

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Student updated successfully']);

        } catch(PDOException $e) {
            $conn->rollBack();
            error_log("Error updating student: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
