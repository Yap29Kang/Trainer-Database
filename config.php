<?php
/**
 * Database Configuration (env-driven)
 * Supported drivers: mysql, pgsql
 * Provide environment variables: DB_DRIVER, DB_HOST, DB_HOSTADDR, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_SSLMODE, DATABASE_URL, APP_ENCRYPTION_KEY
 */

$databaseUrl = getenv('DATABASE_URL') ?: '';
$driver = getenv('DB_DRIVER') ?: '';
$dbHost = getenv('DB_HOST') ?: '';
$dbHostAddr = getenv('DB_HOSTADDR') ?: '';
$dbPort = getenv('DB_PORT') ?: '';
$dbName = getenv('DB_NAME') ?: '';
$dbUser = getenv('DB_USER') ?: '';
$dbPass = getenv('DB_PASS') ?: '';
$dbSslMode = getenv('DB_SSLMODE') ?: '';
$appEncryptionKey = getenv('APP_ENCRYPTION_KEY') ?: '';

define('APP_ENCRYPTION_KEY', $appEncryptionKey);

function normalizeEnvValue($value) {
    $value = trim((string)$value);
    if ($value === '' || preg_match('/^<[^>]+>$/', $value)) {
        return '';
    }

    return $value;
}

$driver = normalizeEnvValue($driver);
$dbHost = normalizeEnvValue($dbHost);
$dbHostAddr = normalizeEnvValue($dbHostAddr);
$dbPort = normalizeEnvValue($dbPort);
$dbName = normalizeEnvValue($dbName);
$dbUser = normalizeEnvValue($dbUser);
$dbPass = normalizeEnvValue($dbPass);
$dbSslMode = normalizeEnvValue($dbSslMode);
$databaseUrl = normalizeEnvValue($databaseUrl);

function parseDatabaseUrl($databaseUrl) {
    $parts = @parse_url($databaseUrl);
    if (!is_array($parts)) {
        return [];
    }

    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }

    return [
        'scheme' => $parts['scheme'] ?? '',
        'host' => $parts['host'] ?? '',
        'port' => isset($parts['port']) ? (string)$parts['port'] : '',
        'dbname' => isset($parts['path']) ? ltrim($parts['path'], '/') : '',
        'user' => $parts['user'] ?? '',
        'pass' => $parts['pass'] ?? '',
        'sslmode' => $query['sslmode'] ?? ''
    ];
}

$databaseUrlParts = $databaseUrl !== '' ? parseDatabaseUrl($databaseUrl) : [];

if ($driver === '' && !empty($databaseUrlParts['scheme'])) {
    if (in_array($databaseUrlParts['scheme'], ['postgres', 'postgresql'], true)) {
        $driver = 'pgsql';
    } elseif (in_array($databaseUrlParts['scheme'], ['mysql', 'mariadb'], true)) {
        $driver = 'mysql';
    }
}

if ($driver === '') {
    $driver = 'mysql';
}

if ($dbHost === '' && !empty($databaseUrlParts['host'])) {
    $dbHost = $databaseUrlParts['host'];
}

if ($dbPort === '' && !empty($databaseUrlParts['port'])) {
    $dbPort = $databaseUrlParts['port'];
}

if ($dbName === '' && !empty($databaseUrlParts['dbname'])) {
    $dbName = $databaseUrlParts['dbname'];
}

if ($dbUser === '' && !empty($databaseUrlParts['user'])) {
    $dbUser = $databaseUrlParts['user'];
}

if ($dbPass === '' && array_key_exists('pass', $databaseUrlParts) && $databaseUrlParts['pass'] !== '') {
    $dbPass = $databaseUrlParts['pass'];
}

if ($dbSslMode === '' && !empty($databaseUrlParts['sslmode'])) {
    $dbSslMode = $databaseUrlParts['sslmode'];
}

if ($dbPort === '') {
    $dbPort = $driver === 'pgsql' ? '5432' : '3306';
}

if ($dbName === '') {
    $dbName = $driver === 'pgsql' ? 'postgres' : 'training_management';
}

if ($dbUser === '') {
    $dbUser = $driver === 'pgsql' ? 'postgres' : 'root';
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
