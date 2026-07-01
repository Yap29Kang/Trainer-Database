<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

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

$query  = trim($_GET['q']   ?? '');
$mode   = trim($_GET['mode'] ?? 'name'); // 'name' or 'id'

if ($query === '' || strlen($query) < 2) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

try {
    if ($mode === 'id') {
        // Search by User ID — exact prefix match
        $stmt = $pdo->prepare("
            SELECT Participant_User_ID, Participant_Name_Encrypted, Participant_Token
            FROM Participant
            WHERE Participant_User_ID ILIKE ?
            ORDER BY Participant_User_ID ASC
            LIMIT 10
        ");
        $stmt->execute([$query . '%']);
    } else {
        // Search by name — fetch all and filter after decryption
        // We can't SQL-filter encrypted names, so fetch a reasonable batch and match in PHP.
        $stmt = $pdo->query("
            SELECT Participant_User_ID, Participant_Name_Encrypted, Participant_Token
            FROM Participant
            WHERE Participant_User_ID IS NOT NULL AND Participant_User_ID <> ''
            LIMIT 2000
        ");
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $results = [];
    $queryLower = strtolower($query);

    foreach ($rows as $row) {
        $norm = array_change_key_case($row, CASE_UPPER);
        $decrypted = decryptParticipantName($norm['PARTICIPANT_NAME_ENCRYPTED'] ?? '');
        if (!$decrypted) continue;
        $userId = $norm['PARTICIPANT_USER_ID'] ?? '';

        if ($mode === 'id') {
            // Already filtered by SQL; include all
            $results[] = ['name' => $decrypted, 'user_id' => $userId];
        } else {
            // Filter by name match
            if (str_contains(strtolower($decrypted), $queryLower)) {
                $results[] = ['name' => $decrypted, 'user_id' => $userId];
            }
        }

        if (count($results) >= 10) break;
    }

    // Sort results: exact prefix matches first
    usort($results, function($a, $b) use ($queryLower, $mode) {
        $fieldA = strtolower($mode === 'id' ? $a['user_id'] : $a['name']);
        $fieldB = strtolower($mode === 'id' ? $b['user_id'] : $b['name']);
        $aPrefix = str_starts_with($fieldA, $queryLower) ? 0 : 1;
        $bPrefix = str_starts_with($fieldB, $queryLower) ? 0 : 1;
        if ($aPrefix !== $bPrefix) return $aPrefix - $bPrefix;
        return strcmp($fieldA, $fieldB);
    });

    echo json_encode(['success' => true, 'data' => array_values($results)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
