<?php
require_once '../config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'No provider ID provided']);
    exit;
}

// If DB is not available, return an error message so frontend handles gracefully
if (!isset($pdo) || $pdo === null) {
    http_response_code(503);
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}

try {
    $provider = getTrainingProviderDetail($id);
    
    if (!$provider) {
        http_response_code(404);
        echo json_encode(['error' => 'Provider not found']);
        exit;
    }
    
    // Add counts
    $provider['trainer_count'] = count($provider['trainers'] ?? []);
    $provider['course_count'] = count($provider['courses'] ?? []);
    $provider['remark_count'] = count($provider['remarks'] ?? []);
    
    // Count participants
    global $pdo;
    $sql = "
        SELECT COUNT(DISTINCT e.Participant_ID) as count
        FROM Item i
        LEFT JOIN Enrollment e ON i.Item_ID = e.Item_ID
        WHERE i.TP_ID = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $provider['participant_count'] = $stmt->fetch()['count'] ?? 0;
    
    echo json_encode($provider);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
