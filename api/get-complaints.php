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
    $search = $_GET['search'] ?? '';
    $params = [];
    
    $sql = "SELECT c.*, tp.TP_Name as tp_name 
            FROM Complaint c 
            LEFT JOIN TrainingProvider tp ON c.training_provider_id = tp.TP_ID ";
            
    if ($search !== '') {
        $sql .= " WHERE c.case_id ILIKE ? OR tp.TP_Name ILIKE ? OR c.employee_name ILIKE ? ";
        $term = "%{$search}%";
        $params = [$term, $term, $term];
    }
    
    $sql .= " ORDER BY c.case_id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $complaints]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
