<?php
/**
 * Simple Database Configuration using only explicit DB_* env vars.
 * This file intentionally does NOT use DATABASE_URL.
 */

$appEncryptionKey = getenv('APP_ENCRYPTION_KEY') ?: '';
define('APP_ENCRYPTION_KEY', $appEncryptionKey);

// Use only the explicit environment variables requested.
$host = getenv('DB_HOST');
$hostAddr = getenv('DB_HOSTADDR');
$port = getenv('DB_PORT');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$ssl  = getenv('DB_SSLMODE') ?: 'require';

$host = $hostAddr ?: $host;
$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=$ssl";

// Database connection
try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => true,
    ]);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    $pdo = null;
    $DB_CONN_ERROR = $e->getMessage();
}

function resolveIpv4Address($host) {
    if (!function_exists('dns_get_record')) {
        return null;
    }

    $records = @dns_get_record($host, DNS_A);
    if (!is_array($records)) {
        return null;
    }

    foreach ($records as $record) {
        if (!empty($record['ip'])) {
            return $record['ip'];
        }
    }

    return null;
}
// (Legacy URL-parsing code removed) The app now uses the explicit DB_* env vars.

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
