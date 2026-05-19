<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['role'])) {
        // Legacy endpoint: only allow returning to user mode.
        $_SESSION['role'] = 'user';
        echo json_encode(['success' => true, 'role' => 'user']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
