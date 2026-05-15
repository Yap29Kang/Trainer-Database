<?php
require_once '../config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'No trainer ID provided']);
    exit;
}

if (!isset($pdo) || $pdo === null) {
    http_response_code(503);
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}

try {
    $trainer = getTrainerDetail($id);

    if (!$trainer) {
        http_response_code(404);
        echo json_encode(['error' => 'Trainer not found']);
        exit;
    }

    $trainer['provider_count'] = count($trainer['providers'] ?? []);
    $trainer['course_count'] = count($trainer['courses'] ?? []);
    $trainer['remark_count'] = count($trainer['remarks'] ?? []);

    echo json_encode($trainer);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
