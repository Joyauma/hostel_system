<?php
require_once '../config/database.php';
require_once '../includes/alerts.php';

// Define the redirect paths
$registerPath = "/hostel_system/register.php";
$loginPath = "/hostel_system/index.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate fullname
    $fullname = trim($_POST['fullname']);
    if (empty($fullname)) {
        $errors[] = "Full name is required";
    } elseif (strlen($fullname) < 3) {
        $errors[] = "Full name must be at least 3 characters long";
    }

    // Validate roll number
    $rollno = trim($_POST['rollno']);
    if (empty($rollno)) {
        $errors[] = "Roll number is required";
    } elseif (!preg_match("/^[A-Za-z0-9]+$/", $rollno)) {
        $errors[] = "Roll number can only contain letters and numbers";
    }

    // Validate email
    $email = trim($_POST['email']);
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }

    // Validate phone
    $phone = trim($_POST['phone']);
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Please enter a valid 10-digit phone number";
    }

    // Validate password
    $password = $_POST['password'];
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }

    // If there are validation errors, redirect back with error messages
    if (!empty($errors)) {
        redirect_with_error($errors, $registerPath);
    }

    try {
        // Check existing records before starting transaction
        $stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE roll = ? OR email = ?");
        $stmt->execute([$rollno, $email]);
        if ($stmt->fetchColumn() > 0) {
            redirect_with_error("Roll number or email already registered", $registerPath);
        }

        // Check if admin exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        $stmt->execute();
        $adminExists = $stmt->fetch(PDO::FETCH_ASSOC);
        $adminId = $adminExists ? $adminExists['id'] : null;

        // Hash password before transaction
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Perform registration in transaction
        transaction($conn, function($conn) use ($rollno, $hashedPassword, $fullname, $email, $phone, $adminId) {
            // Insert into users table
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
            $stmt->execute([$rollno, $hashedPassword]);
            $userId = $conn->lastInsertId();

            // Insert into students table
            $stmt = $conn->prepare("INSERT INTO students (user_id, name, roll, email, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $fullname, $rollno, $email, $phone]);

            // Create notification for admin only if admin exists
            if ($adminId) {
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'registration')");
                $message = "New student registration: $fullname ($rollno)";
                $stmt->execute([$adminId, $message]);
            }

            return $userId;
        });

        // Send email notification (outside transaction)
        $to = $email;
        $subject = "Registration Confirmation - Hostel Management System";
        $message = "Dear $fullname,\n\nYour registration has been successful. Your login credentials:\nUsername: $rollno\n\nPlease login to complete your profile and room allocation process.\n\nBest regards,\nHostel Management Team";
        $headers = "From: hostel@example.com";

        mail($to, $subject, $message, $headers);

        redirect_with_success("Registration completed successfully! You can now login.", $loginPath);

    } catch(PDOException $e) {
        error_log("Registration Error: " . $e->getMessage());
        $errorMessage = "Registration failed: ";
        
        // Check specific error conditions
        if ($e->getCode() == '23000') {
            if (strpos($e->getMessage(), 'notifications_ibfk_1') !== false) {
                $errorMessage = "System configuration error. Please contact administrator.";
            } else {
                $errorMessage = "This roll number or email is already registered.";
            }
        } else {
            $errorMessage = "An unexpected error occurred. Please try again later.";
        }
        
        redirect_with_error($errorMessage, $registerPath);
    }
}
?>
