<?php
require_once '../config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Check admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$id = $_POST['id'] ?? null;
$which = $_POST['which'] ?? null;  // 1 or 2 for first or second AoE
$category = $_POST['category'] ?? null;

if (!$id || !$which) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters: id, which']);
    exit;
}

if (!isset($pdo) || $pdo === null) {
    http_response_code(503);
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}

try {
    $which = intval($which);
    if ($which !== 1 && $which !== 2) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid which parameter (must be 1 or 2)']);
        exit;
    }
    
    $id = intval($id);
    $field = $which === 2 ? 'TP_SecondAoE' : 'TP_FirstAoE';
    $categoryValue = ($category === null || $category === '') ? null : trim((string)$category);
    
    // Verify provider exists and get both expertise fields for validation.
    $checkStmt = $pdo->prepare('SELECT TP_ID, TP_FirstAoE, TP_SecondAoE FROM TrainingProvider WHERE TP_ID = ?');
    $checkStmt->execute([$id]);
    $providerRow = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if (!$providerRow) {
        http_response_code(404);
        echo json_encode(['error' => 'Provider not found']);
        exit;
    }

    // Prevent duplicate categories across first and second AoE.
    if ($categoryValue !== null) {
        $otherValue = $which === 2 ? ($providerRow['TP_FirstAoE'] ?? null) : ($providerRow['TP_SecondAoE'] ?? null);
        if ($otherValue !== null && strcasecmp(trim((string)$otherValue), trim((string)$categoryValue)) === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'First and Second Area of Expertise must be different']);
            exit;
        }
    }
    
    $sql = "UPDATE TrainingProvider SET $field = ? WHERE TP_ID = ?";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$categoryValue, $id]);
    
    if ($success && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Area of Expertise updated', 'field' => $field, 'value' => $categoryValue]);
    } else {
        // rowCount() is 0 if the value didn't actually change, but that's still a success
        echo json_encode(['success' => true, 'message' => 'Area of Expertise set', 'field' => $field, 'value' => $categoryValue]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
