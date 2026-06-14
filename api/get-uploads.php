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

try {
    // We retrieve uploads with status = 'active'
    $stmt = $pdo->query("SELECT Upload_ID, Filename, Upload_Date, Upload_Status, Record_Count FROM Upload WHERE Upload_Status = 'active' ORDER BY Upload_Date DESC");
    $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Normalize casing for cross-DB driver support
    $normalizedUploads = [];
    foreach ($uploads as $index => $upload) {
        $norm = array_change_key_case($upload, CASE_UPPER);
        $status = ($index === 0) ? 'Active' : 'Replaced';
        
        $normalizedUploads[] = [
            'Upload_ID' => (int)($norm['UPLOAD_ID'] ?? 0),
            'Filename' => $norm['FILENAME'] ?? '',
            'Upload_Date' => $norm['UPLOAD_DATE'] ?? '',
            'Upload_Status' => $norm['UPLOAD_STATUS'] ?? '',
            'Record_Count' => (int)($norm['RECORD_COUNT'] ?? 0),
            'UI_Status' => $status
        ];
    }
    
    echo json_encode(['success' => true, 'uploads' => $normalizedUploads]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
