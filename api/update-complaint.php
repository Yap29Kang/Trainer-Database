<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['case_id'])) {
        throw new Exception('Invalid payload: case_id missing');
    }
    
    $pdo->beginTransaction();

    $sql = "UPDATE Complaint SET 
        date_of_complaint = ?, employee_name = ?, employee_id = ?, department = ?, learnops = ?, 
        training_provider_id = ?, complaint_category = ?, complaint_summary = ?, priority = ?, status = ?,
        ldcm_decision = ?, decision_date = ?, remarks = ?
        WHERE case_id = ?";
    
    $stmt = $pdo->prepare($sql);
    
    $decisionDate = !empty($data['decision_date']) ? $data['decision_date'] : null;

    $stmt->execute([
        $data['date_of_complaint'],
        $data['employee_name'],
        $data['employee_id'],
        $data['department'],
        $data['learnops'],
        $data['training_provider_id'],
        $data['complaint_category'],
        $data['complaint_summary'],
        $data['priority'],
        $data['status'],
        $data['ldcm_decision'] === '' ? null : $data['ldcm_decision'],
        $decisionDate,
        $data['remarks'] === '' ? null : $data['remarks'],
        $data['case_id']
    ]);
    
    // Handle side-effect for Blacklist
    if ($data['ldcm_decision'] === 'Blacklist') {
        // Insert a new status entry if it's not already blacklisted for this exact complaint
        // We can just blindly insert or check current status. Usually we just insert.
        // But to prevent duplicates on multiple saves, we can check the latest status.
        $checkSql = "SELECT TP_Status FROM TrainingProviderStatus WHERE TP_ID = ? ORDER BY TP_StatusStartDate DESC, TP_Status_ID DESC LIMIT 1";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$data['training_provider_id']]);
        $currentStatus = $checkStmt->fetchColumn();
        
        if ($currentStatus !== 'Blacklisted') {
            $reasoning = "Blacklisted due to complaint " . $data['case_id'];
            if (!empty($data['complaint_summary'])) {
                $reasoning .= ": " . $data['complaint_summary'];
            }
            if (!empty($data['remarks'])) {
                $reasoning .= " | Remarks: " . $data['remarks'];
            }
            
            $insertSql = "INSERT INTO TrainingProviderStatus (TP_ID, TP_Status, TP_StatusReasoning, TP_StatusStartDate) VALUES (?, 'Blacklisted', ?, CURRENT_DATE)";
            $insertStmt = $pdo->prepare($insertSql);
            $insertStmt->execute([$data['training_provider_id'], $reasoning]);
        }
    }

    $pdo->commit();

    // Insert audit log entry (outside the main transaction to avoid rollback losing the audit)
    try {
        $changedBy = $_SESSION['user_name'] ?? $_SESSION['username'] ?? $_SESSION['name'] ?? 'Admin';
        $auditSql = "INSERT INTO complaint_audit_log
            (case_id, changed_by, status, ldcm_decision, decision_date, remarks)
            VALUES (?, ?, ?, ?, ?, ?)";
        $auditStmt = $pdo->prepare($auditSql);
        $auditStmt->execute([
            $data['case_id'],
            $changedBy,
            $data['status'],
            $data['ldcm_decision'] === '' ? null : $data['ldcm_decision'],
            $decisionDate,
            $data['remarks'] === '' ? null : $data['remarks']
        ]);
    } catch (Exception $auditEx) {
        // Audit failure should not block the main save response
        error_log('Audit log insert failed: ' . $auditEx->getMessage());
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}