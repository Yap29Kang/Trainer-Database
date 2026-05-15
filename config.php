<?php
/**
 * Database Configuration (env-driven)
 * Supported drivers: mysql, pgsql
 * Provide environment variables: DB_DRIVER, DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, APP_ENCRYPTION_KEY
 */

$driver = getenv('DB_DRIVER') ?: 'mysql';
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: null;
$dbName = getenv('DB_NAME') ?: 'training_management';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$appEncryptionKey = getenv('APP_ENCRYPTION_KEY') ?: '';

define('APP_ENCRYPTION_KEY', $appEncryptionKey);

// Build DSN
if ($driver === 'pgsql') {
    $port = $dbPort ?: 5432;
    $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $dbHost, $port, $dbName);
} else {
    $port = $dbPort ?: 3306;
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $dbHost, $port, $dbName);
}

// Database connection
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    // Allow the app to run without DB for local/dev; log the error for debugging
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
