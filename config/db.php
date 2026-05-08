<?php
// config/db.php
$host = '127.0.0.1';
$useName = 'root';
$passCode = '';
$dbName = 'logistics_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $useName, $passCode);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Fetch in Object mode by default for cleaner syntax
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
