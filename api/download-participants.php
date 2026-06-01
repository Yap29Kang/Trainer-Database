<?php
require_once '../config.php';
require_once '../includes/db.php';

@set_time_limit(0);
@ini_set('memory_limit', '512M');

if (!isset($pdo) || $pdo === null) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection is not available.'
    ]);
    exit;
}

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Missing Composer autoload. Run: composer install'
    ]);
    exit;
}

require_once $autoloadPath;

$id = $_GET['id'] ?? null;
$item_id = isset($_GET['item_id']) && $_GET['item_id'] !== '' ? intval($_GET['item_id']) : null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'No provider ID provided']);
    exit;
}

try {
    if ($item_id) {
        $sql = "
            SELECT
                p.Participant_ID,
                p.Participant_Token,
                p.Participant_Name_Encrypted,
                p.Participant_Department,
                i.Item_Name AS Course_Name,
                EXTRACT(YEAR FROM e.Completion_Date) AS Completion_Year
            FROM Enrollment e
            INNER JOIN Participant p ON e.Participant_ID = p.Participant_ID
            INNER JOIN Item i ON e.Item_ID = i.Item_ID
            WHERE i.TP_ID = ? AND i.Item_ID = ?
            ORDER BY p.Participant_ID ASC, i.Item_Name ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $item_id]);
    } else {
        $sql = "
            SELECT
                p.Participant_ID,
                p.Participant_Token,
                p.Participant_Name_Encrypted,
                p.Participant_Department,
                i.Item_Name AS Course_Name,
                EXTRACT(YEAR FROM e.Completion_Date) AS Completion_Year
            FROM Enrollment e
            INNER JOIN Participant p ON e.Participant_ID = p.Participant_ID
            INNER JOIN Item i ON e.Item_ID = i.Item_ID
            WHERE i.TP_ID = ?
            ORDER BY p.Participant_ID ASC, i.Item_Name ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
    }

    $rows = normalizeAssocRows($stmt->fetchAll(PDO::FETCH_ASSOC));

    // decrypt participant names
    foreach ($rows as &$r) {
        $r['Participant_Name'] = decryptParticipantName($r['Participant_Name_Encrypted'] ?? '') ?: ($r['Participant_Token'] ?? 'Participant');
        unset($r['Participant_Name_Encrypted']);
    }

    // Create spreadsheet
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Participants');

    // Header
    $sheet->fromArray([['No.', 'Name', 'Department', 'Course', 'Completion Year']], null, 'A1');
    $sheet->getStyle('A1:E1')->getFont()->setBold(true);
    $sheet->getStyle('A1:E1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D9EAF7');

    $rowNum = 2;
    $counter = 1;
    foreach ($rows as $r) {
        $sheet->setCellValueExplicit('A' . $rowNum, $counter, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('B' . $rowNum, (string)($r['Participant_Name'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C' . $rowNum, (string)($r['Participant_Department'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D' . $rowNum, (string)($r['Course_Name'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('E' . $rowNum, (string)($r['Completion_Year'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $rowNum++;
        $counter++;
    }

    foreach (range('A', 'E') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $filename = 'participants-' . date('Y-m-d-His') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    $writer->save('php://output');
    exit;
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
