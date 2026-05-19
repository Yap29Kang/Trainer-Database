<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (ADMIN_PASSWORD === '') {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Admin login is not configured']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$password = trim((string)($input['password'] ?? ''));

if ($password === '' || !hash_equals(ADMIN_PASSWORD, $password)) {
    $_SESSION['role'] = 'user';
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid admin password']);
    exit;
}

$_SESSION['role'] = 'admin';
$_SESSION['admin_logged_in'] = true;

echo json_encode(['success' => true, 'role' => 'admin']);
?>