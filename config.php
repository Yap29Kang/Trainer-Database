<?php
/**
 * Database Configuration
 * Update these values with your actual database credentials
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'training_management');

// Database connection
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
} catch (PDOException $e) {
    // Do not terminate execution — allow the app to run without DB for local/dev.
    error_log('Database connection failed: ' . $e->getMessage());
    $pdo = null;
    $DB_CONN_ERROR = $e->getMessage();
}

// Set default timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Session settings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define roles
define('ROLE_USER', 'user');
define('ROLE_ADMIN', 'admin');

// Get current role (default to user)
$_SESSION['role'] = $_SESSION['role'] ?? ROLE_USER;
?>
