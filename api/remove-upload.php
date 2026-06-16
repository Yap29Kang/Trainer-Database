<?php
require_once '../config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!isset($pdo) || $pdo === null) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'Database unavailable']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$uploadId = $payload['upload_id'] ?? null;

if (!$uploadId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing upload ID']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Delete all Items linked to this upload — Enrollments auto-cascade via FK
    $stmtDel = $pdo->prepare("DELETE FROM Item WHERE Upload_ID = ?");
    $stmtDel->execute([$uploadId]);

    // Soft-delete the Upload record and zero the count
    $stmt2 = $pdo->prepare("UPDATE Upload SET Upload_Status = 'removed', Record_Count = 0 WHERE Upload_ID = ?");
    $stmt2->execute([$uploadId]);

    // If no active uploads remain, also purge any Items that predate Upload_ID tracking (Upload_ID IS NULL)
    $stmtActive = $pdo->query("SELECT COUNT(*) FROM Upload WHERE Upload_Status = 'active'");
    if ((int)$stmtActive->fetchColumn() === 0) {
        $pdo->exec("DELETE FROM Item WHERE Upload_ID IS NULL");
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