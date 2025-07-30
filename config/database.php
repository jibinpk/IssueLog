<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root'); // Default MAMP password
define('DB_NAME', 'support_logs');
define('DB_CHARSET', 'utf8mb4');

// Create database connection
function getDatabase() {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('Connection failed: ' . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Initialize database tables
function initializeDatabase() {
    $pdo = getDatabase();
    
    // Create support_logs table
    $sql = "CREATE TABLE IF NOT EXISTS support_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date_submitted DATETIME DEFAULT CURRENT_TIMESTAMP,
        plugin_name VARCHAR(255) NOT NULL,
        issue_type ENUM('Technical', 'Pre-sale', 'Account/Billing') NOT NULL,
        concern_area VARCHAR(255),
        query_title VARCHAR(500) NOT NULL,
        description TEXT,
        steps_reproduce TEXT,
        error_logs TEXT,
        wp_version VARCHAR(100),
        wc_version VARCHAR(100),
        plugin_version VARCHAR(100),
        assigned_agent VARCHAR(255),
        time_spent INT DEFAULT 0,
        recurring_issue ENUM('Yes', 'No') DEFAULT 'No',
        escalated_to_dev ENUM('Yes', 'No') DEFAULT 'No',
        status ENUM('Open', 'Resolved', 'Escalated', 'Closed') DEFAULT 'Open',
        resolution_notes TEXT
    )";
    
    $pdo->exec($sql);
    
    // Create sessions table for session management
    $sql = "CREATE TABLE IF NOT EXISTS sessions (
        id VARCHAR(128) PRIMARY KEY,
        access INT UNSIGNED,
        data TEXT
    )";
    
    $pdo->exec($sql);
}

// Check if database exists, create if not
function checkAndCreateDatabase() {
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ];
    
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
?>
