<?php
/**
 * Database configuration that accepts either split DB_* env vars or a single
 * postgres connection URL from DATABASE_URL / DB_HOST.
 */

function normalizeEnvValue($value) {
    if ($value === false || $value === null) {
        return null;
    }

    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

function parsePostgresConnectionString($value) {
    $value = normalizeEnvValue($value);
    if ($value === null) {
        return [];
    }

    if (strpos($value, '://') === false) {
        return ['host' => $value];
    }

    $schemePos = strpos($value, '://');
    $remainder = substr($value, $schemePos + 3);

    $query = '';
    $queryPos = strpos($remainder, '?');
    if ($queryPos !== false) {
        $query = substr($remainder, $queryPos + 1);
        $remainder = substr($remainder, 0, $queryPos);
    }

    $path = '';
    $pathPos = strpos($remainder, '/');
    if ($pathPos !== false) {
        $path = substr($remainder, $pathPos + 1);
        $authority = substr($remainder, 0, $pathPos);
    } else {
        $authority = $remainder;
    }

    $user = null;
    $pass = null;
    $atPos = strrpos($authority, '@');
    if ($atPos !== false) {
        $userInfo = substr($authority, 0, $atPos);
        $authority = substr($authority, $atPos + 1);

        $colonPos = strpos($userInfo, ':');
        if ($colonPos !== false) {
            $user = rawurldecode(substr($userInfo, 0, $colonPos));
            $pass = rawurldecode(substr($userInfo, $colonPos + 1));
        } else {
            $user = rawurldecode($userInfo);
        }
    }

    $host = $authority;
    $port = null;
    if (strlen($authority) > 0 && $authority[0] === '[') {
        $endBracket = strpos($authority, ']');
        if ($endBracket !== false) {
            $host = substr($authority, 1, $endBracket - 1);
            $portPart = substr($authority, $endBracket + 1);
            if (strpos($portPart, ':') === 0) {
                $port = substr($portPart, 1);
            }
        }
    } else {
        $lastColon = strrpos($authority, ':');
        if ($lastColon !== false && ctype_digit(substr($authority, $lastColon + 1))) {
            $host = substr($authority, 0, $lastColon);
            $port = substr($authority, $lastColon + 1);
        }
    }

    $dbName = $path !== '' ? ltrim($path, '/') : null;
    $queryParams = [];
    if ($query !== '') {
        parse_str($query, $queryParams);
    }

    return [
        'host' => rawurldecode($host),
        'port' => $port,
        'dbname' => $dbName !== null ? rawurldecode($dbName) : null,
        'user' => $user,
        'pass' => $pass,
        'sslmode' => $queryParams['sslmode'] ?? null,
        'hostaddr' => $queryParams['hostaddr'] ?? null,
    ];
}

// Keep participant import working even if APP_ENCRYPTION_KEY is not explicitly set.
// This fallback is deterministic across restarts as long as DB credentials stay the same.
$appEncryptionKey = getenv('APP_ENCRYPTION_KEY') ?: '';
if (trim($appEncryptionKey) === '') {
    $fallbackParts = [
        normalizeEnvValue(getenv('DB_HOST')),
        normalizeEnvValue(getenv('DB_NAME')),
        normalizeEnvValue(getenv('DB_USER')),
        normalizeEnvValue(getenv('DB_PASS')),
        normalizeEnvValue(getenv('DATABASE_URL')),
    ];
    $fallbackSeed = implode('|', array_filter($fallbackParts, function ($v) {
        return $v !== null && $v !== '';
    }));
    if ($fallbackSeed !== '') {
        $appEncryptionKey = 'fallback:' . hash('sha256', $fallbackSeed);
    }
}
define('APP_ENCRYPTION_KEY', $appEncryptionKey);

$connection = [
    'host' => null,
    'hostaddr' => normalizeEnvValue(getenv('DB_HOSTADDR')),
    'port' => normalizeEnvValue(getenv('DB_PORT')),
    'dbname' => normalizeEnvValue(getenv('DB_NAME')),
    'user' => normalizeEnvValue(getenv('DB_USER')),
    'pass' => normalizeEnvValue(getenv('DB_PASS')),
    'sslmode' => normalizeEnvValue(getenv('DB_SSLMODE')) ?: 'require',
];

$databaseUrl = normalizeEnvValue(getenv('DATABASE_URL'));
if ($databaseUrl !== null) {
    $connection = array_merge($connection, parsePostgresConnectionString($databaseUrl));
}

$hostEnv = normalizeEnvValue(getenv('DB_HOST'));
if ($hostEnv !== null) {
    $connection = array_merge($connection, parsePostgresConnectionString($hostEnv));
}

// Explicit split env vars should override URL-derived values when both are present.
$explicitHostAddr = normalizeEnvValue(getenv('DB_HOSTADDR'));
if ($explicitHostAddr !== null) {
    $connection['hostaddr'] = $explicitHostAddr;
}

$explicitPort = normalizeEnvValue(getenv('DB_PORT'));
if ($explicitPort !== null) {
    $connection['port'] = $explicitPort;
}

$explicitDbName = normalizeEnvValue(getenv('DB_NAME'));
if ($explicitDbName !== null) {
    $connection['dbname'] = $explicitDbName;
}

$explicitUser = normalizeEnvValue(getenv('DB_USER'));
if ($explicitUser !== null) {
    $connection['user'] = $explicitUser;
}

$explicitPass = normalizeEnvValue(getenv('DB_PASS'));
if ($explicitPass !== null) {
    $connection['pass'] = $explicitPass;
}

$explicitSslMode = normalizeEnvValue(getenv('DB_SSLMODE'));
if ($explicitSslMode !== null) {
    $connection['sslmode'] = $explicitSslMode;
}

$hostName = $connection['host'];
$hostAddr = $connection['hostaddr'];
$port = $connection['port'];
$db = $connection['dbname'];
$user = $connection['user'];
$pass = $connection['pass'];
$ssl = $connection['sslmode'] ?: 'require';

// Build DSN preserving hostname (for SNI) and optionally specifying hostaddr (IPv4) to force IPv4 connect.
$dsn = 'pgsql:';
if ($hostName !== null) {
    $dsn .= 'host=' . $hostName . ';';
}
if ($hostAddr !== null) {
    $dsn .= 'hostaddr=' . $hostAddr . ';';
}
if ($port !== null) {
    $dsn .= 'port=' . $port . ';';
}
if ($db !== null) {
    $dsn .= 'dbname=' . $db . ';';
}
$dsn .= 'sslmode=' . $ssl;

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
