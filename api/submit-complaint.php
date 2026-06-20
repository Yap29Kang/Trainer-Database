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

    if (!$data || !isset($data['date_of_complaint']) || !isset($data['training_provider_id'])) {
        throw new Exception('Invalid payload');
    }

    $sql = "INSERT INTO Complaint (
        date_of_complaint, employee_name, employee_id, department, learnops, 
        training_provider_id, complaint_category, complaint_summary, priority, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
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
        $data['status'] ?? 'Open'
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
