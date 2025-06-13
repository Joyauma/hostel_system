<?php
$host = 'localhost';
$dbname = 'hostel_system';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_AUTOCOMMIT => 0,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
    ));
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
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
