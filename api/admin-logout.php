<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$_SESSION['role'] = 'user';
unset($_SESSION['admin_logged_in']);

echo json_encode(['success' => true, 'role' => 'user']);
?>