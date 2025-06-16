<?php
$host = 'localhost';
$dbname = 'hostel_system';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
    ));
} catch(PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

/**
 * Executes a transaction safely
 * @param PDO $conn Database connection
 * @param callable $callback Function containing the transaction operations
 * @return mixed Result of the transaction
 */
function transaction($conn, $callback) {
    try {
        if (!$conn->inTransaction()) {
            $conn->beginTransaction();
        }
        $result = $callback($conn);
        $conn->commit();
        return $result;
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        throw $e;
    }
}
?>
