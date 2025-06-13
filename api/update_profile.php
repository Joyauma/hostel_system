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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $conn->beginTransaction();

    // Update user email
    $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
    $stmt->execute([$_POST['email'], $_SESSION['user_id']]);

    // Update student details
    $stmt = $conn->prepare("
        UPDATE students 
        SET first_name = ?,
            last_name = ?,
            phone = ?,
            dob = ?,
            gender = ?,
            address = ?,
            course = ?,
            year_of_study = ?,
            guardian_name = ?,
            guardian_phone = ?,
            guardian_address = ?
        WHERE user_id = ?
    ");

    $stmt->execute([
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['phone'],
        $_POST['dob'],
        $_POST['gender'],
        $_POST['address'],
        $_POST['course'],
        $_POST['year_of_study'],
        $_POST['guardian_name'],
        $_POST['guardian_phone'],
        $_POST['guardian_address'],
        $_SESSION['user_id']
    ]);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);

} catch(PDOException $e) {
    $conn->rollBack();
    error_log("Error updating profile: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
