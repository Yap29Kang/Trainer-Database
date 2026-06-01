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

$search = trim((string)($_GET['search'] ?? ''));
$status = trim((string)($_GET['status'] ?? 'all'));
$sort = strtolower(trim((string)($_GET['sort'] ?? 'asc')));

$trainers = getTrainers();

if ($search !== '') {
    $searchLower = strtolower($search);
    $trainers = array_filter($trainers, function ($t) use ($searchLower) {
        return strpos(strtolower((string)($t['Trainer_Name'] ?? '')), $searchLower) !== false
            || (isset($t['providers']) && is_array($t['providers']) && array_reduce($t['providers'], function ($carry, $p) use ($searchLower) {
                return $carry || strpos(strtolower((string)($p['TP_Name'] ?? '')), $searchLower) !== false;
            }, false));
    });
}

// Trainer status filter (redflag semantics)
if ($status === 'redflag') {
    $trainers = array_filter($trainers, function ($t) {
        return !empty($t['Trainer_StatusActive']);
    });
}

usort($trainers, function ($a, $b) use ($sort) {
    $cmp = strcasecmp((string)($a['Trainer_Name'] ?? ''), (string)($b['Trainer_Name'] ?? ''));
    return $sort === 'desc' ? -$cmp : $cmp;
});

$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$summarySheet = $spreadsheet->getActiveSheet();
$summarySheet->setTitle('Summary');

$detailSheet = $spreadsheet->createSheet();
$detailSheet->setTitle('Trainer Details');

$summarySheet->setCellValue('A1', 'Trainers Summary Totals');
$summarySheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

$overallTrainerCount = count($trainers);
$overallProviderCount = 0;
$overallCourseCount = 0; // optional: approximate

foreach ($trainers as $t) {
    if (!empty($t['providers']) && is_array($t['providers'])) {
        $overallProviderCount += count($t['providers']);
    }
}

$summarySheet->fromArray([
    ['Metric', 'Total'],
    ['Trainers / Speakers', $overallTrainerCount],
    ['Training Provider Relationships', $overallProviderCount],
], null, 'A3');

$summaryHeadersRow = 8;
$summarySheet->fromArray([
    ['Trainer / Speaker', 'Trainer Status', 'Training Provider', 'Training Provider Status']
], null, 'A' . $summaryHeadersRow);

$summaryRow = $summaryHeadersRow + 1;
foreach ($trainers as $trainer) {
    $trainerName = (string)($trainer['Trainer_Name'] ?? '');
    $trainerStatus = (string)($trainer['Trainer_StatusDisplay'] ?? $trainer['Trainer_Status'] ?? '');
    $providerNames = [];
    $providerStatuses = [];
    if (!empty($trainer['providers']) && is_array($trainer['providers'])) {
        foreach ($trainer['providers'] as $p) {
            $providerNames[] = (string)($p['TP_Name'] ?? '');
            $providerStatuses[] = (string)($p['TP_Status'] ?? '');
        }
    }
    $summarySheet->fromArray([
        $trainerName,
        $trainerStatus,
        implode(' | ', $providerNames),
        implode(' | ', $providerStatuses)
    ], null, 'A' . $summaryRow);
    $summaryRow++;
}

$summarySheet->getStyle('A3:B6')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
$summarySheet->getStyle('A' . $summaryHeadersRow . ':D' . $summaryHeadersRow)->getFont()->setBold(true);
$summarySheet->getStyle('A' . $summaryHeadersRow . ':D' . $summaryHeadersRow)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D9EAF7');
foreach (range('A', 'D') as $column) {
    $summarySheet->getColumnDimension($column)->setAutoSize(true);
}

$detailSheet->fromArray([
    ['Trainer / Speaker', 'Trainer Status', 'Training Provider', 'Training Provider Status']
], null, 'A1');

$rowNumber = 2;
foreach ($trainers as $trainer) {
    $trainerName = (string)($trainer['Trainer_Name'] ?? '');
    $trainerStatus = (string)($trainer['Trainer_StatusDisplay'] ?? $trainer['Trainer_Status'] ?? '');
    if (!empty($trainer['providers']) && is_array($trainer['providers'])) {
        foreach ($trainer['providers'] as $p) {
            $providerName = (string)($p['TP_Name'] ?? '');
            $providerStatus = (string)($p['TP_Status'] ?? '');
            $detailSheet->setCellValueExplicit('A' . $rowNumber, $trainerName, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $detailSheet->setCellValueExplicit('B' . $rowNumber, $trainerStatus, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $detailSheet->setCellValueExplicit('C' . $rowNumber, $providerName, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $detailSheet->setCellValueExplicit('D' . $rowNumber, $providerStatus, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $rowNumber++;
        }
    } else {
        $detailSheet->setCellValueExplicit('A' . $rowNumber, $trainerName, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $detailSheet->setCellValueExplicit('B' . $rowNumber, $trainerStatus, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $detailSheet->setCellValueExplicit('C' . $rowNumber, '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $detailSheet->setCellValueExplicit('D' . $rowNumber, '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $rowNumber++;
    }
}

$detailSheet->getStyle('A1:D1')->getFont()->setBold(true);
$detailSheet->getStyle('A1:D1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D9EAF7');
foreach (range('A', 'D') as $column) {
    $detailSheet->getColumnDimension($column)->setAutoSize(true);
}

$spreadsheet->setActiveSheetIndex(0);

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

$filename = 'trainers-export-' . date('Y-m-d-His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

$writer->save('php://output');
exit;
