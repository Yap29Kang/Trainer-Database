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
    $sql = "SELECT DISTINCT Participant_Department FROM Participant WHERE Participant_Department IS NOT NULL AND Participant_Department <> '' ORDER BY Participant_Department";
    $stmt = $pdo->query($sql);
    $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['success' => true, 'data' => $departments]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
