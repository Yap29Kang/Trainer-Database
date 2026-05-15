<?php
/**
 * Database Configuration (env-driven)
 * Supported drivers: mysql, pgsql
 * Provide environment variables: DB_DRIVER, DB_HOST, DB_HOSTADDR, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_SSLMODE, DATABASE_URL, APP_ENCRYPTION_KEY
 */

$driver = getenv('DB_DRIVER') ?: 'mysql';
$databaseUrl = getenv('DATABASE_URL') ?: '';
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbHostAddr = getenv('DB_HOSTADDR') ?: '';
$dbPort = getenv('DB_PORT') ?: null;
$dbName = getenv('DB_NAME') ?: 'training_management';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbSslMode = getenv('DB_SSLMODE') ?: 'require';
$appEncryptionKey = getenv('APP_ENCRYPTION_KEY') ?: '';

define('APP_ENCRYPTION_KEY', $appEncryptionKey);

// Build DSN
if ($databaseUrl !== '') {
    $parts = parse_url($databaseUrl);
    if ($parts !== false) {
        $driver = $parts['scheme'] ?? $driver;
        $dbHost = $parts['host'] ?? $dbHost;
        $dbPort = $parts['port'] ?? $dbPort;
        $dbName = isset($parts['path']) ? ltrim($parts['path'], '/') : $dbName;
        $dbUser = $parts['user'] ?? $dbUser;
        $dbPass = $parts['pass'] ?? $dbPass;
    }
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
    $port = $dbPort ?: 5432;
    $dsnParts = [
        sprintf('host=%s', $dbHost),
        sprintf('port=%d', $port),
        sprintf('dbname=%s', $dbName),
        sprintf('sslmode=%s', $dbSslMode)
    ];

    $resolvedHostAddr = $dbHostAddr !== '' ? $dbHostAddr : resolveIpv4Address($dbHost);
    if ($resolvedHostAddr !== null && $resolvedHostAddr !== '') {
        $dsnParts[] = sprintf('hostaddr=%s', $resolvedHostAddr);
    }

    $dsn = 'pgsql:' . implode(';', $dsnParts);
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
