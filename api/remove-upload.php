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
    
    // Delete the enrollments corresponding to this upload
    $stmt1 = $pdo->prepare("DELETE FROM Enrollment WHERE Upload_ID = ?");
    $stmt1->execute([$uploadId]);
    
    // Now update the Upload status to 'removed' and Record_Count to 0
    $stmt2 = $pdo->prepare("UPDATE Upload SET Upload_Status = 'removed', Record_Count = 0 WHERE Upload_ID = ?");
    $stmt2->execute([$uploadId]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
