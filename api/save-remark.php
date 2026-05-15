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
$entity = strtolower(trim((string)($payload['entity'] ?? '')));
$id = $payload['id'] ?? null;
$remark = trim((string)($payload['remark'] ?? ''));

if (!$id || $remark === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing target or remark text']);
    exit;
}

try {
    if ($entity === 'provider') {
        $stmt = $pdo->prepare('INSERT INTO TrainingProviderRemark (TP_ID, Remark_Text) VALUES (?, ?)');
        $stmt->execute([$id, $remark]);
    } elseif ($entity === 'trainer') {
        $stmt = $pdo->prepare('INSERT INTO TrainerRemark (Trainer_ID, Remark_Text) VALUES (?, ?)');
        $stmt->execute([$id, $remark]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid remark target']);
        exit;
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}