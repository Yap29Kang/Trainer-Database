<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['role'])) {
        $_SESSION['role'] = in_array($input['role'], ['user', 'admin']) ? $input['role'] : 'user';
        echo json_encode(['success' => true]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
