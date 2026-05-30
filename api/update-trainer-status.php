<?php
require_once '../config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($pdo) || $pdo === null) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Database unavailable']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$trainerId = $payload['trainer_id'] ?? $payload['id'] ?? null;
$action = strtolower(trim((string)($payload['action'] ?? '')));
$reason = isset($payload['reason']) ? trim((string)$payload['reason']) : '';

if (!$trainerId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing trainer ID']);
    exit;
}

if ($action === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing action']);
    exit;
}

try {
    if ($action === 'flag') {
        $allowedReasons = [
            'Unprofessional conduct',
            'Poor training quality',
            'Compliance or legal concern',
            'Reliability issues'
        ];

        if ($reason === '' || !in_array($reason, $allowedReasons, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid or missing red flag reason']);
            exit;
        }

        updateTrainerRedFlag($trainerId, true, $reason);
    } elseif ($action === 'remove') {
        updateTrainerRedFlag($trainerId, false, null);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit;
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
