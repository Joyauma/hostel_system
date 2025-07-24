<?php
session_start();
require_once '../config/database.php';

// Check if user is authorized
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'student'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

try {
    $query = "";
    $params = [];
    
    // For admin, show all rooms with current occupancy
    if ($_SESSION['role'] === 'admin') {
        // Get current student gender if editing
        $studentGender = null;
        if (isset($_GET['student_id'])) {
            $stmt = $conn->prepare("SELECT gender FROM students WHERE id = ?");
            $stmt->execute([$_GET['student_id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $studentGender = $result ? $result['gender'] : null;
        }

        if (isset($_GET['gender'])) {
            $studentGender = $_GET['gender'];
        }

        $query = "
            SELECT 
                r.id,
                r.room_no,
                r.type,
                r.capacity,
                COALESCE(r.block, '') as block,
                COALESCE(r.floor, 0) as floor,
                r.occupied,
                COUNT(DISTINCT ra.id) as current_occupants,
                COALESCE(MIN(s_gender.gender), 'any') as room_gender,
                GROUP_CONCAT(
                    DISTINCT CONCAT(s.name, ' (', COALESCE(s.roll, 'No Roll'), ')')
                    ORDER BY s.name
                    SEPARATOR ', '
                ) as occupant_details
            FROM rooms r
            LEFT JOIN room_allocations ra ON r.id = ra.room_id 
                AND (ra.vacate_date IS NULL OR ra.vacate_date > CURRENT_DATE)
            LEFT JOIN students s ON ra.student_id = s.id
            LEFT JOIN (
                SELECT DISTINCT ra2.room_id, s2.gender
                FROM room_allocations ra2
                JOIN students s2 ON ra2.student_id = s2.id
                WHERE (ra2.vacate_date IS NULL OR ra2.vacate_date > CURRENT_DATE)
            ) s_gender ON r.id = s_gender.room_id
            GROUP BY r.id, r.room_no, r.type, r.capacity, r.block, r.floor, r.occupied
            HAVING (room_gender = 'any' OR room_gender IS NULL OR room_gender = ?";
            
        $params = [$studentGender];

        if (isset($_GET['student_id'])) {
            $query .= " OR r.id IN (
                SELECT room_id FROM room_allocations 
                WHERE student_id = ? AND (vacate_date IS NULL OR vacate_date > CURRENT_DATE)
            )";
            $params[] = $_GET['student_id'];
        }

        $query .= ") ORDER BY COALESCE(r.block, ''), r.floor, r.room_no";    } else {
        // For students, only show available rooms matching their gender
        $stmt = $conn->prepare("SELECT gender FROM students WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $studentGender = $stmt->fetchColumn();

        $query = "
            SELECT 
                r.id,
                r.room_no,
                r.type,
                r.capacity,
                COALESCE(r.block, '') as block,
                COALESCE(r.floor, 0) as floor,
                r.occupied,
                COUNT(DISTINCT ra.id) as current_occupants
            FROM rooms r
            LEFT JOIN room_allocations ra ON r.id = ra.room_id 
                AND (ra.vacate_date IS NULL OR ra.vacate_date > CURRENT_DATE)
            LEFT JOIN (
                SELECT DISTINCT ra2.room_id, s2.gender
                FROM room_allocations ra2
                JOIN students s2 ON ra2.student_id = s2.id
                WHERE (ra2.vacate_date IS NULL OR ra2.vacate_date > CURRENT_DATE)
            ) s_gender ON r.id = s_gender.room_id
            GROUP BY r.id, r.room_no, r.type, r.capacity, r.block, r.floor, r.occupied
            HAVING (r.capacity > COUNT(DISTINCT ra.id))
            AND (COALESCE(MIN(s_gender.gender), ?) = ?)
            ORDER BY COALESCE(r.block, ''), r.floor, r.room_no";
            
        $params = [$studentGender, $studentGender];
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate availability status for each room
    foreach ($rooms as &$room) {
        $room['available_beds'] = $room['capacity'] - $room['current_occupants'];
        $room['status'] = $room['available_beds'] > 0 ? 'Available' : 'Full';
    }
    unset($room); // Break the reference

    echo json_encode([
        'success' => true, 
        'data' => $rooms
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
