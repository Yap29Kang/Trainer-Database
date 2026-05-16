<?php
/**
 * Simple Database Configuration using only explicit DB_* env vars.
 * This file intentionally does NOT use DATABASE_URL.
 */

$appEncryptionKey = getenv('APP_ENCRYPTION_KEY') ?: '';
define('APP_ENCRYPTION_KEY', $appEncryptionKey);

// Use only the explicit environment variables requested.
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$ssl  = getenv('DB_SSLMODE') ?: 'require';

$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=$ssl";

// Database connection
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
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

if ($driver === 'pgsql') {
    $host = $dbHostAddr !== '' ? $dbHostAddr : (resolveIpv4Address($dbHost) ?: $dbHost);
    $port = (int)$dbPort;
    $db = $dbName;
    $user = $dbUser;
    $pass = $dbPass;

    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    if ($dbSslMode !== '') {
        $dsn .= ';sslmode=' . $dbSslMode;
    }
} else {
    $port = (int)$dbPort;
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
