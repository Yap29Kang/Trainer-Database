<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$case_id = trim($_GET['case_id'] ?? '');
if ($case_id === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing case_id']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT audit_id, changed_at, changed_by, status, ldcm_decision, decision_date, remarks
        FROM complaint_audit_log
        WHERE case_id = ?
        ORDER BY changed_at DESC
    ");
    $stmt->execute([$case_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
