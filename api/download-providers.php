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

$providers = getTrainingProviders();

if ($search !== '') {
    $searchLower = strtolower($search);
    $providers = array_filter($providers, function ($provider) use ($searchLower) {
        return strpos(strtolower((string)($provider['TP_Name'] ?? '')), $searchLower) !== false
            || strpos(strtolower((string)($provider['TP_FirstAoEDisplay'] ?? '')), $searchLower) !== false
            || strpos(strtolower((string)($provider['TP_SecondAoEDisplay'] ?? '')), $searchLower) !== false
            || strpos(strtolower((string)($provider['trainer_names'] ?? '')), $searchLower) !== false;
    });
}

if ($status !== '' && $status !== 'all') {
    $statusMap = [
        'active' => 'Active',
        'blank' => 'Active',
        'approved' => 'Active',
        'consideration' => 'Greylist',
        'greylist' => 'Greylist',
        'blacklisted' => 'Blacklisted'
    ];
    $statusValue = $statusMap[$status] ?? $status;
    $providers = array_filter($providers, function ($provider) use ($statusValue) {
        return ($provider['TP_Status'] ?? '') === $statusValue;
    });
}

usort($providers, function ($a, $b) use ($sort) {
    $cmp = strcasecmp((string)($a['TP_Name'] ?? ''), (string)($b['TP_Name'] ?? ''));
    return $sort === 'desc' ? -$cmp : $cmp;
});

$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$summarySheet = $spreadsheet->getActiveSheet();
$summarySheet->setTitle('Summary');

$detailSheet = $spreadsheet->createSheet();
$detailSheet->setTitle('Provider Details');

$summarySheet->setCellValue('A1', 'Summary Totals');
$summarySheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

$overallProviderCount = count($providers);
$overallTrainerCount = 0;
$overallCourseCount = 0;
$overallParticipantCount = 0;

foreach ($providers as $provider) {
    $overallTrainerCount += (int)($provider['trainer_count'] ?? 0);
    $overallCourseCount += (int)($provider['course_count'] ?? 0);
    $overallParticipantCount += (int)($provider['participant_count'] ?? 0);
}

$summarySheet->fromArray([
    ['Metric', 'Total'],
    ['Training Providers', $overallProviderCount],
    ['Trainers / Speakers', $overallTrainerCount],
    ['Courses', $overallCourseCount],
    ['Participants', $overallParticipantCount],
], null, 'A3');

$summaryHeadersRow = 10;
$summarySheet->fromArray([
    ['Training Provider', 'Training Provider Status', 'Area of Expertise', 'Trainers / Speakers', 'Trainer Count', 'Course Count', 'Participant Count']
], null, 'A' . $summaryHeadersRow);

$summaryRow = $summaryHeadersRow + 1;
foreach ($providers as $provider) {
    $aoeParts = [];
    $aoeFirst = trim((string)($provider['TP_FirstAoEDisplay'] ?? ''));
    $aoeSecond = trim((string)($provider['TP_SecondAoEDisplay'] ?? ''));
    if ($aoeFirst !== '') {
        $aoeParts[] = $aoeFirst;
    }
    if ($aoeSecond !== '' && strcasecmp($aoeSecond, $aoeFirst) !== 0) {
        $aoeParts[] = $aoeSecond;
    }

    $summarySheet->fromArray([
        (string)($provider['TP_Name'] ?? ''),
        (string)($provider['TP_Status'] ?? ''),
        implode(' | ', $aoeParts),
        (string)($provider['trainer_names'] ?? ''),
        (int)($provider['trainer_count'] ?? 0),
        (int)($provider['course_count'] ?? 0),
        (int)($provider['participant_count'] ?? 0),
    ], null, 'A' . $summaryRow);
    $summaryRow++;
}

$summarySheet->getStyle('A3:B7')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
$summarySheet->getStyle('A10:G10')->getFont()->setBold(true);
$summarySheet->getStyle('A10:G10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D9EAF7');

foreach (range('A', 'G') as $column) {
    $summarySheet->getColumnDimension($column)->setAutoSize(true);
}

$detailSheet->fromArray([
    ['Training Provider', 'Training Provider Status', 'Area of Expertise', 'Trainers / Speakers']
], null, 'A1');

$rowNumber = 2;
foreach ($providers as $provider) {
    $providerId = (int)($provider['TP_ID'] ?? 0);
    $detail = getTrainingProviderDetail($providerId);
    $trainerRows = [];

    if ($detail && !empty($detail['trainers']) && is_array($detail['trainers'])) {
        foreach ($detail['trainers'] as $trainer) {
            $trainerName = trim((string)($trainer['Trainer_Name'] ?? ''));
            if ($trainerName !== '') {
                $trainerRows[] = $trainerName;
            }
        }
    }

    if (empty($trainerRows)) {
        $trainerRows[] = '';
    }

    $aoeParts = [];
    $aoeFirst = trim((string)($provider['TP_FirstAoEDisplay'] ?? ''));
    $aoeSecond = trim((string)($provider['TP_SecondAoEDisplay'] ?? ''));
    if ($aoeFirst !== '') {
        $aoeParts[] = $aoeFirst;
    }
    if ($aoeSecond !== '' && strcasecmp($aoeSecond, $aoeFirst) !== 0) {
        $aoeParts[] = $aoeSecond;
    }
    $aoeText = implode(' | ', $aoeParts);

    $statusText = (string)($provider['TP_Status'] ?? '');
    $providerName = (string)($provider['TP_Name'] ?? '');

    foreach ($trainerRows as $trainerName) {
        $detailSheet->setCellValueExplicit('A' . $rowNumber, $providerName, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $detailSheet->setCellValueExplicit('B' . $rowNumber, $statusText, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $detailSheet->setCellValueExplicit('C' . $rowNumber, $aoeText, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $detailSheet->setCellValueExplicit('D' . $rowNumber, $trainerName, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $rowNumber++;
    }
}

$detailSheet->getStyle('A1:D1')->getFont()->setBold(true);
$detailSheet->getStyle('A1:D1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D9EAF7');
$detailSheet->getStyle('A1:D' . max($rowNumber - 1, 1))->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

foreach (range('A', 'D') as $column) {
    $detailSheet->getColumnDimension($column)->setAutoSize(true);
}

$spreadsheet->setActiveSheetIndex(0);

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

$filename = 'training-providers-export-' . date('Y-m-d-His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

$writer->save('php://output');
exit;
