<?php
/**
 * Temporary diagnostic endpoint to help debug DB connection issues on the host.
 * Returns masked environment values and the PDO connection test result.
 * Remove this file after debugging to avoid exposing runtime info.
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

function mask($s) {
    if ($s === null) return null;
    $s = (string)$s;
    if ($s === '') return '';
    $len = strlen($s);
    if ($len <= 4) return str_repeat('*', $len);
    return substr($s,0,2) . str_repeat('*', max(0, $len-4)) . substr($s,-2);
}

$env = [
    'DB_DRIVER' => getenv('DB_DRIVER'),
    'DB_HOST' => getenv('DB_HOST'),
    'DB_PORT' => getenv('DB_PORT'),
    'DB_NAME' => getenv('DB_NAME'),
    'DB_USER' => getenv('DB_USER'),
    'DB_PASS_masked' => mask(getenv('DB_PASS')),
    'DATABASE_URL' => getenv('DATABASE_URL') ? '(set)' : '(not set)',
    'DB_SSLMODE' => getenv('DB_SSLMODE') ?: '(not set)'
];

$result = [
    'ok' => false,
    'env' => $env,
    'pdo_exists' => isset($pdo) && $pdo !== null,
    'db_error' => isset($DB_CONN_ERROR) ? $DB_CONN_ERROR : null,
    'test_query' => null
];

if (isset($pdo) && $pdo !== null) {
    try {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $stmt = $pdo->query('SELECT 1');
            $result['test_query'] = $stmt ? $stmt->fetchColumn() : null;
        } else {
            $stmt = $pdo->query('SELECT 1');
            $result['test_query'] = $stmt ? $stmt->fetchColumn() : null;
        }
        $result['ok'] = true;
    } catch (Throwable $e) {
        $result['db_error'] = $e->getMessage();
    }
}

echo json_encode($result, JSON_PRETTY_PRINT);

?>
