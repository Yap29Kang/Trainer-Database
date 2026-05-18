<?php
require_once '../config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$id = $_GET['id'] ?? null;
$item_id = isset($_GET['item_id']) && $_GET['item_id'] !== '' ? intval($_GET['item_id']) : null;
$course_name = null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'No provider ID provided']);
    exit;
}

if (!isset($pdo) || $pdo === null) {
    http_response_code(503);
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}

try {
    $providerStmt = $pdo->prepare('SELECT TP_Name FROM TrainingProvider WHERE TP_ID = ?');
    $providerStmt->execute([$id]);
    $provider = $providerStmt->fetch(PDO::FETCH_ASSOC);

    if (!$provider) {
        http_response_code(404);
        echo json_encode(['error' => 'Provider not found']);
        exit;
    }

    if ($item_id) {
           // fetch item name for display
           $itemStmt = $pdo->prepare('SELECT Item_Name FROM Item WHERE Item_ID = ? AND TP_ID = ?');
           $itemStmt->execute([$item_id, $id]);
        $itemRow = normalizeAssocRow($itemStmt->fetch(PDO::FETCH_ASSOC));
           $course_name = $itemRow['Item_Name'] ?? null;
        $countStmt = $pdo->prepare('SELECT COUNT(DISTINCT e.Participant_ID) AS count FROM Enrollment e INNER JOIN Item i ON e.Item_ID = i.Item_ID WHERE i.TP_ID = ? AND i.Item_ID = ?');
        $countStmt->execute([$id, $item_id]);
        $participantCount = (int)(normalizeAssocRow($countStmt->fetch(PDO::FETCH_ASSOC))['COUNT'] ?? 0);

        $sql = "
            SELECT
                p.Participant_ID,
                p.Participant_Token,
                p.Participant_Name_Encrypted,
                p.Participant_Department,
                i.Item_Name AS Course_Name,
                e.Completion_Date AS Completion_Date,
                EXTRACT(YEAR FROM e.Completion_Date) AS Completion_Year
            FROM Enrollment e
            INNER JOIN Participant p ON e.Participant_ID = p.Participant_ID
            INNER JOIN Item i ON e.Item_ID = i.Item_ID
            WHERE i.TP_ID = ? AND i.Item_ID = ?
            ORDER BY Completion_Year DESC, p.Participant_ID ASC, i.Item_Name ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $item_id]);
    } else {
        $countStmt = $pdo->prepare('SELECT COUNT(DISTINCT e.Participant_ID) AS count FROM Item i LEFT JOIN Enrollment e ON i.Item_ID = e.Item_ID WHERE i.TP_ID = ?');
        $countStmt->execute([$id]);
        $participantCount = (int)(normalizeAssocRow($countStmt->fetch(PDO::FETCH_ASSOC))['COUNT'] ?? 0);

        $sql = "
            SELECT
                p.Participant_ID,
                p.Participant_Token,
                p.Participant_Name_Encrypted,
                p.Participant_Department,
                i.Item_Name AS Course_Name,
                e.Completion_Date AS Completion_Date,
                EXTRACT(YEAR FROM e.Completion_Date) AS Completion_Year
            FROM Enrollment e
            INNER JOIN Participant p ON e.Participant_ID = p.Participant_ID
            INNER JOIN Item i ON e.Item_ID = i.Item_ID
            WHERE i.TP_ID = ?
            ORDER BY Completion_Year DESC, p.Participant_ID ASC, i.Item_Name ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
    }

    $participants = [];
    foreach (normalizeAssocRows($stmt->fetchAll(PDO::FETCH_ASSOC)) as $row) {
        $decryptedName = decryptParticipantName($row['Participant_Name_Encrypted'] ?? '');
        $row['Participant_Name'] = $decryptedName ?: ($row['Participant_Token'] ?? 'Participant');
        unset($row['Participant_Name_Encrypted']);
        $participants[] = $row;
    }

    usort($participants, function ($a, $b) {
        return strcasecmp($a['Participant_Name'] ?? '', $b['Participant_Name'] ?? '');
    });

    $result = [
        'provider_name' => $provider['TP_Name'],
        'participant_count' => $participantCount,
        'participants' => $participants
    ];
    if (!empty($course_name)) $result['course_name'] = $course_name;
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}