<?php
session_start();
require_once '../config/database.php';
require_once '../includes/alerts.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate input
    $username = trim($_POST['username']);
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    $password = $_POST['password'];
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    $role = $_POST['role'];
    if (!in_array($role, ['admin', 'student', 'staff'])) {
        $errors[] = "Invalid role selected";
    }

    if (!empty($errors)) {
        redirect_with_error($errors, '../index.php');
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role = ?");
        $stmt->execute([$username, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];

            // Get user's name based on role
            if ($role === 'student') {
                $stmt = $conn->prepare("SELECT name FROM students WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $student = $stmt->fetch();
                $_SESSION['name'] = $student['name'];
            }

            $redirectPath = "../{$role}/dashboard.php";
            redirect_with_success("Welcome back!", $redirectPath);
        } else {
            redirect_with_error("Invalid username or password", '../index.php');
        }
    } catch(PDOException $e) {
        redirect_with_error("A system error occurred. Please try again later.", '../index.php');
    }
}

 // For testing purposes, remove in production
?>
