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
$id = $payload['id'] ?? null;
$isRedFlag = filter_var($payload['is_red_flag'] ?? null, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

if (!$id || $isRedFlag === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing trainer ID or red-flag state']);
    exit;
}

$reason = isset($payload['reason']) ? trim((string)$payload['reason']) : null;
if ($reason === '') {
    $reason = null;
}

$allowedReasons = [
    'Unprofessional conduct',
    'Poor training quality',
    'Compliance or legal concern',
    'Reliability issues',
];

if ($isRedFlag) {
    if ($reason === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing red-flag reason']);
        exit;
    }

    if (!in_array($reason, $allowedReasons, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid red-flag reason']);
        exit;
    }
}

try {
    $pdo->beginTransaction();
    $ok = updateTrainerRedFlag($id, $isRedFlag, $reason);

    if (!$ok) {
        throw new RuntimeException('Could not update trainer red flag');
    }

    $pdo->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
