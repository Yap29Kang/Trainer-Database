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

    // ── Step 1: Delete Items for this upload ──────────────────────────────────
    // Enrollments auto-cascade via FK (Item → Enrollment ON DELETE CASCADE)
    $pdo->prepare("DELETE FROM Item WHERE Upload_ID = ?")
        ->execute([$uploadId]);

    // ── Step 1b: Legacy cleanup ───────────────────────────────────────────────
    // Items imported before Upload_ID tracking was added have Upload_ID = NULL.
    // If no other active uploads remain, purge those too.
    $stmtOtherActive = $pdo->prepare(
        "SELECT COUNT(*) FROM Upload WHERE Upload_Status = 'active' AND Upload_ID != ?"
    );
    $stmtOtherActive->execute([$uploadId]);
    if ((int)$stmtOtherActive->fetchColumn() === 0) {
        $pdo->exec("DELETE FROM Item WHERE Upload_ID IS NULL");
    }

    // ── Step 2: Delete orphaned Participants ──────────────────────────────────
    // Participants with no remaining Enrollments after the Items were deleted
    $pdo->exec(
        "DELETE FROM Participant
         WHERE NOT EXISTS (
             SELECT 1 FROM Enrollment
             WHERE Enrollment.Participant_ID = Participant.Participant_ID
         )"
    );

    // ── Step 3: Delete orphaned Assignments ───────────────────────────────────
    // Assignments where the TP+Trainer pair no longer has any Items
    $pdo->exec(
        "DELETE FROM Assignment
         WHERE NOT EXISTS (
             SELECT 1 FROM Item
             WHERE Item.TP_ID      = Assignment.TP_ID
               AND Item.Trainer_ID = Assignment.Trainer_ID
         )"
    );

    // ── Step 4: Delete orphaned Trainers ──────────────────────────────────────
    // Trainers no longer in any Assignment
    // Cascades: TrainerStatus, TrainerRemark
    $pdo->exec(
        "DELETE FROM Trainer
         WHERE NOT EXISTS (
             SELECT 1 FROM Assignment
             WHERE Assignment.Trainer_ID = Trainer.Trainer_ID
         )"
    );

    // ── Step 5: Delete orphaned Training Providers ────────────────────────────
    // Providers no longer in any Assignment
    // Cascades: TrainingProviderStatus, TrainingProviderRemark
    $pdo->exec(
        "DELETE FROM TrainingProvider
         WHERE NOT EXISTS (
             SELECT 1 FROM Assignment
             WHERE Assignment.TP_ID = TrainingProvider.TP_ID
         )"
    );

    // ── Step 6: Soft-delete the Upload record ─────────────────────────────────
    $pdo->prepare(
        "UPDATE Upload SET Upload_Status = 'removed', Record_Count = 0 WHERE Upload_ID = ?"
    )->execute([$uploadId]);

    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}