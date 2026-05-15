<?php
require_once '../config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

try {
    // If DB is not available, return zeroed stats so frontend still loads
    if (!isset($pdo) || $pdo === null) {
        echo json_encode([
            'total_providers' => 0,
            'providers_active' => 0,
                'providers_greylist' => 0,
                'providers_in_consideration' => 0,
            'providers_blacklisted' => 0,
            'total_trainers' => 0,
            'total_courses' => 0,
            'total_participants' => 0
        ]);
        exit;
    }
    $stats = getStatistics();
    echo json_encode($stats);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
