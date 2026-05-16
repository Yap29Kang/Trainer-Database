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
    $databaseUrl = trim((string)$databaseUrl);
    if ($databaseUrl === '') {
        return [];
    }

    $parts = [
        'scheme' => '',
        'host' => '',
        'port' => '',
        'dbname' => '',
        'user' => '',
        'pass' => '',
        'sslmode' => ''
    ];

    $schemeSeparator = strpos($databaseUrl, '://');
    if ($schemeSeparator === false) {
        return [];
    }

    $parts['scheme'] = strtolower(substr($databaseUrl, 0, $schemeSeparator));
    $remainder = substr($databaseUrl, $schemeSeparator + 3);

    $querySeparator = strpos($remainder, '?');
    if ($querySeparator !== false) {
        $queryString = substr($remainder, $querySeparator + 1);
        parse_str($queryString, $query);
        $parts['sslmode'] = $query['sslmode'] ?? '';
        $remainder = substr($remainder, 0, $querySeparator);
    }

    $pathSeparator = strpos($remainder, '/');
    if ($pathSeparator !== false) {
        $parts['dbname'] = ltrim(substr($remainder, $pathSeparator), '/');
        $authority = substr($remainder, 0, $pathSeparator);
    } else {
        $authority = $remainder;
    }

    $atSeparator = strrpos($authority, '@');
    if ($atSeparator !== false) {
        $userInfo = substr($authority, 0, $atSeparator);
        $authority = substr($authority, $atSeparator + 1);

        $colonSeparator = strpos($userInfo, ':');
        if ($colonSeparator !== false) {
            $parts['user'] = rawurldecode(substr($userInfo, 0, $colonSeparator));
            $parts['pass'] = rawurldecode(substr($userInfo, $colonSeparator + 1));
        } else {
            $parts['user'] = rawurldecode($userInfo);
        }
    }

    $colonSeparator = strrpos($authority, ':');
    if ($colonSeparator !== false) {
        $parts['host'] = substr($authority, 0, $colonSeparator);
        $parts['port'] = substr($authority, $colonSeparator + 1);
    } else {
        $parts['host'] = $authority;
    }

    $parts['host'] = trim($parts['host']);
    $parts['port'] = trim($parts['port']);
    $parts['dbname'] = trim($parts['dbname']);

    if ($parts['scheme'] === '' || $parts['host'] === '') {
        return [];
    }

    return $parts;
}

function looksLikeDatabaseUrl($value) {
    return is_string($value) && preg_match('#^[a-z][a-z0-9+.-]*://#i', trim($value)) === 1;
}

$databaseUrlParts = $databaseUrl !== '' ? parseDatabaseUrl($databaseUrl) : [];
$dbHostParts = looksLikeDatabaseUrl($dbHost) ? parseDatabaseUrl($dbHost) : [];

if ($driver === '' && !empty($dbHostParts['scheme'])) {
    if (in_array($dbHostParts['scheme'], ['postgres', 'postgresql'], true)) {
        $driver = 'pgsql';
    } elseif (in_array($dbHostParts['scheme'], ['mysql', 'mariadb'], true)) {
        $driver = 'mysql';
    }
}

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

if (!empty($dbHostParts['host'])) {
    $dbHost = $dbHostParts['host'];
}

if ($dbPort === '' && !empty($databaseUrlParts['port'])) {
    $dbPort = $databaseUrlParts['port'];
}

if (!empty($dbHostParts['port'])) {
    $dbPort = $dbHostParts['port'];
}

if ($dbName === '' && !empty($databaseUrlParts['dbname'])) {
    $dbName = $databaseUrlParts['dbname'];
}

if (!empty($dbHostParts['dbname'])) {
    $dbName = $dbHostParts['dbname'];
}

if ($dbUser === '' && !empty($databaseUrlParts['user'])) {
    $dbUser = $databaseUrlParts['user'];
}

if (!empty($dbHostParts['user'])) {
    $dbUser = $dbHostParts['user'];
}

if ($dbPass === '' && array_key_exists('pass', $databaseUrlParts) && $databaseUrlParts['pass'] !== '') {
    $dbPass = $databaseUrlParts['pass'];
}

if (array_key_exists('pass', $dbHostParts) && $dbHostParts['pass'] !== '') {
    $dbPass = $dbHostParts['pass'];
}

if ($dbSslMode === '' && !empty($databaseUrlParts['sslmode'])) {
    $dbSslMode = $databaseUrlParts['sslmode'];
}

if ($dbSslMode === '' && !empty($dbHostParts['sslmode'])) {
    $dbSslMode = $dbHostParts['sslmode'];
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
