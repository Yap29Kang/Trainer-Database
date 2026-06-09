<?php

$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$db   = getenv('DB_DATABASE');
$user = getenv('DB_USERNAME');
$pass = getenv('DB_PASSWORD');

$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "Connected successfully";

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>