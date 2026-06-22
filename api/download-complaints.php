<?php
require_once '../config.php';
require_once '../includes/db.php';

@set_time_limit(0);
@ini_set('memory_limit', '512M');

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

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

try {
    $search = trim((string)($_GET['search'] ?? ''));
    $params = [];

    $sql = "SELECT c.*, tp.TP_Name as tp_name
            FROM Complaint c
            LEFT JOIN TrainingProvider tp ON c.training_provider_id = tp.TP_ID ";

    if ($search !== '') {
        $sql .= " WHERE c.case_id ILIKE ? OR tp.TP_Name ILIKE ? OR c.employee_name ILIKE ? ";
        $term = "%{$search}%";
        $params = [$term, $term, $term];
    }

    $sql .= " ORDER BY c.case_id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Complaint Log');

    $headers = [
        'Case ID',
        'Date of Complaint',
        'LearnOps',
        'Training Provider',
        'Complaint Category',
        'Complaint Summary',
        'Priority',
        'Status',
        'LDCM Decision',
        'Decision Date',
        'Remarks'
    ];

    $sheet->fromArray([$headers], null, 'A1');
    $sheet->getStyle('A1:K1')->getFont()->setBold(true);
    $sheet->getStyle('A1:K1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D9EAF7');

    $rowNum = 2;
    foreach ($complaints as $c) {
        $sheet->setCellValueExplicit('A' . $rowNum, (string)($c['case_id'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B' . $rowNum, (string)($c['date_of_complaint'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C' . $rowNum, (string)($c['learnops'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D' . $rowNum, (string)($c['tp_name'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('E' . $rowNum, (string)($c['complaint_category'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('F' . $rowNum, (string)($c['complaint_summary'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('G' . $rowNum, (string)($c['priority'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('H' . $rowNum, (string)($c['status'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('I' . $rowNum, (string)($c['ldcm_decision'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('J' . $rowNum, (string)($c['decision_date'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('K' . $rowNum, (string)($c['remarks'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $rowNum++;
    }

    foreach (range('A', 'K') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $sheet->getStyle('F2:F' . max($rowNum - 1, 2))->getAlignment()->setWrapText(true);

    $sheet->getStyle('A1:K' . max($rowNum - 1, 1))->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $filename = 'complaint-log-' . date('Y-m-d-His') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    $writer->save('php://output');
    exit;
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
