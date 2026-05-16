<?php

$host = getenv("DB_HOST");
$hostAddr = getenv("DB_HOSTADDR");
$port = getenv("DB_PORT");
$db   = getenv("DB_NAME");
$user = getenv("DB_USER");
$pass = getenv("DB_PASS");
$ssl  = getenv("DB_SSLMODE") ?: "require";

$host = $hostAddr ?: $host;
$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=$ssl";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => true,
    ]);

    echo json_encode([
        "ok" => true
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "ok" => false,
        "error" => $e->getMessage()
    ]);
}
