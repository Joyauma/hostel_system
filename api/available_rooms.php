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

try {
    // Get rooms that have available space
    $stmt = $conn->query("
        SELECT * 
        FROM rooms 
        WHERE occupied < capacity 
        ORDER BY room_no
    ");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($rooms);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
