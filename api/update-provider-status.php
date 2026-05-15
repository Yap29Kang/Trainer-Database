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
$status = isset($payload['status']) ? trim((string)$payload['status']) : null;

if (!$id || $status === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing provider ID or status']);
    exit;
}

$allowed = ['Active', '', 'Greylist', 'Blacklisted', 'In Consideration'];

if ($status === 'Approved') {
    $status = 'Active';
}

if ($status === '') {
    $status = 'Active';
}

if (!in_array($status, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Close latest open status window (if any) before writing a new one.
        $closeStmt = $pdo->prepare(
                "UPDATE TrainingProviderStatus
                 SET TP_StatusEndDate = CURRENT_DATE
                 WHERE TP_ID = ?
                     AND (TP_StatusEndDate IS NULL OR TP_StatusEndDate > CURRENT_DATE)"
        );
    $closeStmt->execute([$id]);

    $reason = isset($payload['reason']) ? trim((string)$payload['reason']) : null;
    if ($reason === '') {
        $reason = null;
    }

    $blacklistUntil = isset($payload['blacklist_until']) ? trim((string)$payload['blacklist_until']) : null;
    if ($blacklistUntil === '') {
        $blacklistUntil = null;
    }

    if ($status === 'In Consideration') {
        $status = 'Greylist';
    }

    $statusEndDate = null;
    if ($status === 'Blacklisted') {
        if ($blacklistUntil === null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing blacklist end date']);
            exit;
        }

        $dt = DateTime::createFromFormat('Y-m-d', $blacklistUntil);
        $isValidDate = $dt && $dt->format('Y-m-d') === $blacklistUntil;
        if (!$isValidDate) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid blacklist end date format']);
            exit;
        }

        $today = new DateTime('today');
        if ($dt < $today) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Blacklist end date cannot be earlier than today']);
            exit;
        }

        $statusEndDate = $blacklistUntil;
    }

    $insertStmt = $pdo->prepare(
        'INSERT INTO TrainingProviderStatus (TP_ID, TP_Status, TP_StatusReasoning, TP_StatusStartDate, TP_StatusEndDate) VALUES (?, ?, ?, CURRENT_DATE, ?)'
    );
    $insertStmt->execute([$id, $status, $reason, $statusEndDate]);

    $pdo->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}