<?php
// install.php
// Database Setup Script for Logistics Platform

$host = '127.0.0.1';
$user = 'root';
$pass = ''; // Default XAMPP has no password

try {
    // Connect to MySQL server without database
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS logistics_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database 'logistics_db' created or already exists.\n";
    
    // Connect to the specific database
    $pdo->exec("USE logistics_db");

    // Table: branches
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS branches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            address TEXT NOT NULL,
            contact_number VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "Table 'branches' created.\n";

    // Table: users
    // roles: admin, manager, driver, customer
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'manager', 'driver', 'customer') NOT NULL DEFAULT 'customer',
            branch_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
        )
    ");
    echo "Table 'users' created.\n";

    // Table: vehicles
    // status: available, en_route, maintenance
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS vehicles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            license_plate VARCHAR(20) UNIQUE NOT NULL,
            type VARCHAR(50) NOT NULL,
            capacity DECIMAL(10,2) NOT NULL COMMENT 'Capacity in kg or cu meters',
            status ENUM('available', 'en_route', 'maintenance') DEFAULT 'available',
            branch_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
        )
    ");
    echo "Table 'vehicles' created.\n";

    // Table: shipments
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS shipments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tracking_number VARCHAR(50) UNIQUE NOT NULL,
            sender_name VARCHAR(100) NOT NULL,
            sender_phone VARCHAR(20) NOT NULL,
            sender_address TEXT NOT NULL,
            receiver_name VARCHAR(100) NOT NULL,
            receiver_phone VARCHAR(20) NOT NULL,
            receiver_address TEXT NOT NULL,
            weight DECIMAL(10,2) NOT NULL,
            cost DECIMAL(10,2) NOT NULL,
            current_status VARCHAR(50) DEFAULT 'Pending',
            branch_id INT NULL,
            driver_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
            FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    echo "Table 'shipments' created.\n";

    // Table: tracking_history
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tracking_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shipment_id INT NOT NULL,
            status VARCHAR(50) NOT NULL,
            location VARCHAR(100) NOT NULL,
            remarks TEXT NULL,
            updated_by INT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,
            FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    echo "Table 'tracking_history' created.\n";

    // Insert Default Admin (Password: admin123)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['admin@logistics.com']);
    if($stmt->rowCount() == 0) {
        $hashed = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->exec("INSERT INTO users (name, email, password, role) VALUES ('System Admin', 'admin@logistics.com', '$hashed', 'admin')");
        echo "Default admin user created (admin@logistics.com / admin123).\n";
    }

    echo "\n=== Database Installation Successfully Completed! ===\n";

} catch (PDOException $e) {
    die("DB ERROR: " . $e->getMessage());
}
?>
