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
    $year = $_GET['year'] ?? '';
    $status = $_GET['status'] ?? '';
    $params = [];
    
    $sql = "SELECT c.*, tp.TP_Name as tp_name 
            FROM Complaint c 
            LEFT JOIN TrainingProvider tp ON c.training_provider_id = tp.TP_ID ";
    $whereClauses = [];
    
    if ($search !== '') {
        $whereClauses[] = "(c.case_id ILIKE ? OR tp.TP_Name ILIKE ? OR c.employee_name ILIKE ?)";
        $term = "%{$search}%";
        $params = [$term, $term, $term];
    }
    
    if ($year !== '') {
        $whereClauses[] = "EXTRACT(YEAR FROM c.date_of_complaint) = ?";
        $params[] = $year;
    }
    
    if ($status !== '') {
        $whereClauses[] = "c.status = ?";
        $params[] = $status;
    }
    
    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(" AND ", $whereClauses);
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
