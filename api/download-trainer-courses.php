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
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'No trainer ID provided']);
    exit;
}

try {
    $trainer = getTrainerDetail((int)$id);
    if (!$trainer) {
        http_response_code(404);
        echo json_encode(['error' => 'Trainer not found']);
        exit;
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Courses Taught');

    $sheet->fromArray([['Course', 'Training Provider', 'Completion Date', 'Pax', 'Year']], null, 'A1');
    $sheet->getStyle('A1:E1')->getFont()->setBold(true);
    $sheet->getStyle('A1:E1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D9EAF7');

    $courses = is_array($trainer['courses'] ?? null) ? $trainer['courses'] : [];
    usort($courses, function ($a, $b) {
        $aDate = strtotime((string)($a['Completion_Date'] ?? '')) ?: 0;
        $bDate = strtotime((string)($b['Completion_Date'] ?? '')) ?: 0;
        if ($aDate === $bDate) {
            return strcasecmp((string)($a['Item_Name'] ?? ''), (string)($b['Item_Name'] ?? ''));
        }
        return $bDate <=> $aDate;
    });

    $rowNum = 2;
    foreach ($courses as $course) {
        $completionDate = trim((string)($course['Completion_Date'] ?? ''));
        $formattedDate = '';
        $year = '';
        if ($completionDate !== '') {
            $timestamp = strtotime($completionDate);
            if ($timestamp !== false) {
                $formattedDate = date('d M Y', $timestamp);
                $year = date('Y', $timestamp);
            } else {
                $formattedDate = $completionDate;
            }
        }

        $sheet->setCellValueExplicit('A' . $rowNum, (string)($course['Item_Name'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B' . $rowNum, (string)($course['TP_Name'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C' . $rowNum, $formattedDate, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D' . $rowNum, (string)($course['participant_count'] ?? 0), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('E' . $rowNum, $year, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $rowNum++;
    }

    foreach (range('A', 'E') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    $sheet->getStyle('A1:E' . max($rowNum - 1, 1))->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $filename = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string)($trainer['Trainer_Name'] ?? 'trainer')) . '-courses-taught-' . date('Y-m-d-His') . '.xlsx';

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
