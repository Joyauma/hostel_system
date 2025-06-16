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
    // For admin, we can show all rooms with their current occupancy
    // For students, only show available rooms
    if ($_SESSION['role'] === 'admin') {
        $query = "
            SELECT 
                r.id,
                r.room_no,
                r.type,
                r.capacity,
                r.occupied,
                COUNT(DISTINCT ra.id) as current_occupants,
                GROUP_CONCAT(
                    DISTINCT CONCAT(s.name, ' (', COALESCE(s.roll, 'No Roll'), ')')
                    ORDER BY s.name
                    SEPARATOR ', '
                ) as occupant_details
            FROM rooms r
            LEFT JOIN room_allocations ra ON r.id = ra.room_id 
                AND (ra.vacate_date IS NULL OR ra.vacate_date > CURRENT_DATE)
            LEFT JOIN students s ON ra.student_id = s.id
            GROUP BY r.id, r.room_no, r.type, r.capacity, r.occupied
            ORDER BY r.room_no";
    } else {
        $query = "
            SELECT 
                r.id,
                r.room_no,
                r.type,
                r.capacity,
                r.occupied,
                COUNT(DISTINCT ra.id) as current_occupants
            FROM rooms r
            LEFT JOIN room_allocations ra ON r.id = ra.room_id 
                AND (ra.vacate_date IS NULL OR ra.vacate_date > CURRENT_DATE)
            GROUP BY r.id, r.room_no, r.type, r.capacity, r.occupied
            HAVING r.capacity > COUNT(DISTINCT ra.id)
            ORDER BY r.room_no";
    }

    $stmt = $conn->query($query);
    if (!$stmt) {
        throw new PDOException($conn->errorInfo()[2]);
    }
    
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
