<?php
require_once '../config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!isset($pdo) || $pdo === null) {
    http_response_code(503);
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}

try {
    $sql = "SELECT DISTINCT Item_Category FROM Item WHERE Item_Category IS NOT NULL AND Item_Category != '' ORDER BY Item_Category ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode(['categories' => $categories ?: []]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
