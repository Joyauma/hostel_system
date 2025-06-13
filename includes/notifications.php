<?php
function sendNotification($conn, $data) {
    // Insert notification into database
    if (isset($data['user_ids'])) {
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, message, type) 
            VALUES (?, ?, ?)
        ");
        foreach ($data['user_ids'] as $user_id) {
            $stmt->execute([$user_id, $data['message'], $data['type']]);
        }
    } else if (isset($data['role'])) {
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, message, type)
            SELECT id, ?, ?
            FROM users
            WHERE role = ?
        ");
        $stmt->execute([$data['message'], $data['type'], $data['role']]);
    }

    // Send email notification if email is enabled
    if (isset($data['send_email']) && $data['send_email']) {
        $emails = [];
        if (isset($data['user_ids'])) {
            $ids = implode(',', $data['user_ids']);
            $stmt = $conn->prepare("
                SELECT email 
                FROM students s
                JOIN users u ON s.user_id = u.id
                WHERE u.id IN ($ids)
            ");
            $stmt->execute();
            $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else if (isset($data['role'])) {
            $stmt = $conn->prepare("
                SELECT s.email 
                FROM students s
                JOIN users u ON s.user_id = u.id
                WHERE u.role = ?
            ");
            $stmt->execute([$data['role']]);
            $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        foreach ($emails as $email) {
            mail(
                $email,
                $data['email_subject'] ?? 'Hostel Management System Notification',
                $data['message'],
                'From: hostel@example.com'
            );
        }
    }

    // Send SMS notification if SMS is enabled and phone numbers are provided
    if (isset($data['send_sms']) && $data['send_sms']) {
        $phones = [];
        if (isset($data['user_ids'])) {
            $ids = implode(',', $data['user_ids']);
            $stmt = $conn->prepare("
                SELECT phone 
                FROM students s
                JOIN users u ON s.user_id = u.id
                WHERE u.id IN ($ids)
            ");
            $stmt->execute();
            $phones = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else if (isset($data['role'])) {
            $stmt = $conn->prepare("
                SELECT s.phone 
                FROM students s
                JOIN users u ON s.user_id = u.id
                WHERE u.role = ?
            ");
            $stmt->execute([$data['role']]);
            $phones = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        // Implement SMS sending logic here
        // You can integrate with services like Twilio, MessageBird, etc.
        // Example with Twilio:
        /*
        require_once 'vendor/autoload.php';
        $client = new Twilio\Rest\Client($account_sid, $auth_token);
        foreach ($phones as $phone) {
            try {
                $client->messages->create(
                    $phone,
                    [
                        'from' => $twilio_number,
                        'body' => $data['message']
                    ]
                );
            } catch (Exception $e) {
                // Log error
            }
        }
        */
    }
}

// Example usage:
/*
sendNotification($conn, [
    'user_ids' => [1, 2, 3],
    'message' => 'Your hostel fee is due',
    'type' => 'fee_reminder',
    'send_email' => true,
    'email_subject' => 'Fee Payment Reminder',
    'send_sms' => true
]);

sendNotification($conn, [
    'role' => 'student',
    'message' => 'Hostel will be closed for maintenance',
    'type' => 'announcement',
    'send_email' => true
]);
*/
?>
