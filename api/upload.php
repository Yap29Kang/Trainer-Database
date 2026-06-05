<?php

function iniSizeToBytes($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return 0;
    }

    $unit = strtolower(substr($value, -1));
    $number = (float)$value;
    switch ($unit) {
        case 'g':
            $number *= 1024;
        case 'm':
            $number *= 1024;
        case 'k':
            $number *= 1024;
    }

    return (int)$number;
}

$postMaxBytes = iniSizeToBytes((string)ini_get('post_max_size'));
$contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
if ($postMaxBytes > 0 && $contentLength > $postMaxBytes) {
    http_response_code(413);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Uploaded file is too large. Maximum allowed size is ' . ini_get('post_max_size') . '.'
    ]);
    exit;
}

require_once '../config.php';
require_once '../includes/db.php';

@set_time_limit(0);
@ini_set('memory_limit', '512M');

// Keep API responses JSON-only even when PHP warnings/notices occur.
ini_set('display_errors', '0');
ini_set('html_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file provided']);
    exit;
}

$file = $_FILES['file'];
$tmp_file = $file['tmp_name'];
$file_name = $file['name'];
$file_error = $file['error'];

// Check for upload errors
if ($file_error !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File upload error: ' . $file_error]);
    exit;
}

// Get file extension
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
if (!in_array($file_ext, ['xlsx', 'xls', 'csv'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only .xlsx, .xls, .csv are allowed']);
    exit;
}

// Determine preview mode (sent as form field 'preview' = '1')
$isPreview = false;
if (isset($_POST['preview']) && ($_POST['preview'] === '1' || $_POST['preview'] === 'true' || $_POST['preview'] === 1)) {
    $isPreview = true;
}
if (isset($_GET['preview']) && ($_GET['preview'] === '1' || $_GET['preview'] === 'true' || $_GET['preview'] === 1)) {
    $isPreview = true;
}

try {
    if (!$isPreview) {
        // Begin transaction only for actual import
        if (!isset($pdo) || $pdo === null) {
            http_response_code(503);
            echo json_encode([
                'success' => false,
                'message' => 'Database connection is not available. Check DB_HOST, DB_NAME, DB_USER, DB_PASS, and DB_DRIVER on the backend host.'
            ]);
            exit;
        }

        $pdo->beginTransaction();
    }

    // Parse file based on extension
    if ($file_ext === 'csv') {
        $data = parseCSV($tmp_file);
    } else {
        $data = parseExcel($tmp_file);
    }

    // If preview mode, return a safe sample and summary without writing to DB
    if ($isPreview) {
        $sample = array_slice($data, 0, 10);

        // counts
        $uniqueProviders = [];
        $uniqueTrainers = [];
        $uniqueCourses = [];
        $rowsWithMissing = [];
        foreach ($data as $i => $row) {
            if (!empty($row['TP_Name'])) $uniqueProviders[$row['TP_Name']] = true;
            if (!empty($row['Trainer_Name'])) $uniqueTrainers[$row['Trainer_Name']] = true;
            if (!empty($row['Item_Name'])) $uniqueCourses[$row['Item_Name']] = true;
            if (empty($row['TP_Name']) || empty($row['Trainer_Name'])) {
                $rowsWithMissing[] = $i + 2; // +2 to account header and 1-based user view
            }
        }

        echo json_encode([
            'success' => true,
            'preview' => true,
            'sample_rows' => $sample,
            'counts' => [
                'unique_providers' => count($uniqueProviders),
                'unique_trainers' => count($uniqueTrainers),
                'unique_courses' => count($uniqueCourses),
                'total_rows' => count($data)
            ],
            'rows_with_missing_required' => $rowsWithMissing
        ]);
        exit;
    }

    // Process the data (actual import)
    $result = processUploadData($data);

    if (!$result['success']) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode($result);
        exit;
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Database updated successfully',
        'stats' => $result['stats']
    ]);

} catch (Throwable $e) {
    if (!$isPreview && isset($pdo)) $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Processing error: ' . $e->getMessage()
    ]);
}

/**
 * Parse CSV file
 */
function parseCSV($file_path) {
    $data = [];
    $file = fopen($file_path, 'r');
    $header = null;
    
    while (($row = fgetcsv($file)) !== false) {
        if ($header === null) {
            $header = normalizeHeaders($row);
        } else {
            // Keep row width aligned with header count to avoid array_combine warnings
            // when CSV rows have missing/trailing columns.
            if (count($row) < count($header)) {
                $row = array_pad($row, count($header), '');
            } elseif (count($row) > count($header)) {
                $row = array_slice($row, 0, count($header));
            }

            $assoc = array_combine($header, $row);
            if ($assoc !== false) {
                $assoc = normalizeImportedRow(canonicalizeRow($assoc));

                // Skip completely empty rows.
                $hasValue = false;
                foreach ($assoc as $value) {
                    if (trim((string)$value) !== '') {
                        $hasValue = true;
                        break;
                    }
                }
                if (!$hasValue) {
                    continue;
                }

                $data[] = $assoc;
            }
        }
    }
    fclose($file);
    return $data;
}

/**
 * Parse Excel file using simple method (requires no external lib for basic xlsx)
 * For production, use PhpSpreadsheet library
 */
function parseExcel($file_path) {
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        throw new Exception('Missing Composer autoload. Run: composer install');
    }

    require_once $autoloadPath;

    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file_path);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($file_path);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray('', true, true, false);

    if (empty($rows)) {
        return [];
    }

    $header = normalizeHeaders($rows[0]);

    $data = [];
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];

        // Keep row width aligned with header count
        if (count($row) < count($header)) {
            $row = array_pad($row, count($header), '');
        } elseif (count($row) > count($header)) {
            $row = array_slice($row, 0, count($header));
        }

        $assoc = array_combine($header, $row);
        if ($assoc === false) {
            continue;
        }

        $assoc = normalizeImportedRow(canonicalizeRow($assoc));

        // Skip completely empty rows
        $hasValue = false;
        foreach ($assoc as $value) {
            if (trim((string)$value) !== '') {
                $hasValue = true;
                break;
            }
        }
        if (!$hasValue) {
            continue;
        }

        $data[] = $assoc;
    }

    return $data;
}

/**
 * Normalize header labels by trimming and removing UTF-8 BOM.
 */
function normalizeHeaders($headers) {
    return array_map(function ($h) {
        $clean = trim((string)$h);
        return preg_replace('/^\xEF\xBB\xBF/', '', $clean);
    }, $headers);
}

/**
 * Map alternate incoming column names to canonical keys used by the importer.
 */
function canonicalizeRow($row) {
    $normalizeKey = function ($value) {
        return strtolower(preg_replace('/[^a-z0-9]/', '', (string)$value));
    };

    $aliases = [
        'TP_Name' => ['TP_Name', 'Training Provider'],
        'Trainer_Name' => ['Trainer_Name', 'Trainers/Speaker Name', 'Trainer/Speaker Name', 'Trainer', 'Speaker Name'],
        'Trainer_Status' => ['Trainer_Status', 'Trainer Status', 'Status', 'Red Flag Status', 'Red Flag', 'Trainer Red Flag Status', 'Trainer Red Flag', 'Trainer_Status/Red Flag'],
        'Item_Name' => ['Item_Name', 'Item Title', 'Course Name', 'Training Title'],
        'Item_Category' => ['Item_Category', 'Category'],
        'Item_Venue' => ['Item_Venue', 'Item Venue', 'Venue', 'Course Venue', 'Training Venue', 'Venue Name'],
        'Participant_Name' => ['Participant_Name', 'Participant Name', 'Full Name', 'Participant'],
        'Participant_User_ID' => ['User ID', 'User_ID', 'UserID'],
        'Participant_Department' => ['Participant_Department', 'Department', 'Participant Department'],
        'Completion_Date' => ['Completion_Date', 'Completion Date', 'Course Completion Date', 'Date Completed']
    ];

    $lookup = [];
    foreach ($row as $key => $value) {
        $lookup[$normalizeKey($key)] = trim((string)$value);
    }

    $normalized = [];
    foreach ($aliases as $target => $candidates) {
        $normalized[$target] = '';
        foreach ($candidates as $candidate) {
            $candidateKey = $normalizeKey($candidate);
            if (array_key_exists($candidateKey, $lookup) && $lookup[$candidateKey] !== '') {
                $normalized[$target] = $lookup[$candidateKey];
                break;
            }
        }
    }

    return $normalized;
}

/**
 * Normalize date strings to MySQL DATE (Y-m-d). Accepts dd/mm/yyyy, dd-mm-yyyy,
 * yyyy-mm-dd and Excel serial dates.
 */
function normalizeDate($value) {
    if ($value === null) {
        return null;
    }

    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    if (is_numeric($value)) {
        $serial = (float)$value;
        if ($serial > 0) {
            $base = new DateTime('1899-12-30');
            $base->modify('+' . (int)$serial . ' days');
            return $base->format('Y-m-d');
        }
        return null;
    }

    $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'm-d-Y', 'd.m.Y'];
    foreach ($formats as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $value);
        if ($dt && $dt->format($fmt) === $value) {
            return $dt->format('Y-m-d');
        }
    }

    $ts = strtotime($value);
    return $ts ? date('Y-m-d', $ts) : null;
}

function normalizeImportName($value, $default) {
    $value = trim((string)$value);
    if ($value === '' || strcasecmp($value, 'NIL') === 0) {
        return $default;
    }

    return $value;
}

function normalizeImportedRow(array $row) {
    $row['TP_Name'] = normalizeImportName($row['TP_Name'] ?? '', 'Unknown Training Provider');
    $row['Trainer_Name'] = normalizeImportName($row['Trainer_Name'] ?? '', 'Unknown Trainer');
    $row['Trainer_Status'] = normalizeImportName($row['Trainer_Status'] ?? '', 'Active');
    return $row;
}

function participantIdentityValue(array $row) {
    $userId = trim((string)($row['Participant_User_ID'] ?? ''));
    if ($userId !== '' && strcasecmp($userId, 'NIL') !== 0) {
        return $userId;
    }

    return trim((string)($row['Participant_Name'] ?? ''));
}

function chunkRows($rows, $size = 200) {
    if (empty($rows)) {
        return [];
    }

    return array_chunk($rows, $size);
}

function bulkInsertRows(PDO $pdo, $table, array $columns, array $rows, $ignoreConflicts = false) {
    if (empty($rows)) {
        return 0;
    }

    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $columnList = implode(', ', $columns);
    $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
    $insertPrefix = $ignoreConflicts && $driver === 'mysql' ? 'INSERT IGNORE INTO' : 'INSERT INTO';
    $conflictSuffix = $ignoreConflicts && $driver === 'pgsql' ? ' ON CONFLICT DO NOTHING' : '';
    $inserted = 0;

    foreach (chunkRows($rows, 200) as $chunk) {
        $valuesSql = implode(', ', array_fill(0, count($chunk), $placeholders));
        $sql = sprintf('%s %s (%s) VALUES %s%s', $insertPrefix, $table, $columnList, $valuesSql, $conflictSuffix);
        $params = [];
        foreach ($chunk as $row) {
            foreach ($row as $value) {
                $params[] = $value;
            }
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $inserted += count($chunk);
    }

    return $inserted;
}

function fetchInsertedItemMap(PDO $pdo, array $itemRows) {
    if (empty($itemRows)) {
        return [];
    }

    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $map = [];

    foreach (chunkRows($itemRows, 200) as $chunk) {
        $placeholders = '(' . implode(', ', array_fill(0, 5, '?')) . ')';
        $valuesSql = implode(', ', array_fill(0, count($chunk), $placeholders));
        $chunkKeys = [];
        foreach ($chunk as $row) {
            $chunkKeys[] = $row[0];
        }

        if ($driver === 'pgsql') {
            $sql = 'INSERT INTO Item (TP_ID, Trainer_ID, Item_Name, Item_Category, Item_Venue) VALUES ' . $valuesSql . ' RETURNING Item_ID, TP_ID, Trainer_ID, Item_Name';
            $stmt = $pdo->prepare($sql);
            $params = [];
            foreach ($chunk as $row) {
                foreach (array_slice($row, 1) as $value) {
                    $params[] = $value;
                }
            }
            $stmt->execute($params);

            $index = 0;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $normalized = normalizeAssocRow($row);
                $originalKey = $chunkKeys[$index] ?? null;
                if ($originalKey !== null) {
                    $map[$originalKey] = (int)$normalized['ITEM_ID'];
                }
                $index++;
            }
        } else {
            $sql = 'INSERT INTO Item (TP_ID, Trainer_ID, Item_Name, Item_Category, Item_Venue) VALUES ' . $valuesSql;
            $stmt = $pdo->prepare($sql);
            $params = [];
            foreach ($chunk as $row) {
                foreach (array_slice($row, 1) as $value) {
                    $params[] = $value;
                }
            }
            $stmt->execute($params);

            $firstId = (int)$pdo->lastInsertId();
            foreach ($chunk as $index => $row) {
                $map[$row[0]] = $firstId + $index;
            }
        }
    }

    return $map;
}

function fetchIdMap(PDO $pdo, $table, $idColumn, $valueColumn, array $values) {
    $values = array_values(array_unique(array_filter($values, function ($value) {
        return $value !== null && $value !== '';
    })));

    if (empty($values)) {
        return [];
    }

    $map = [];
    foreach (chunkRows($values, 500) as $chunk) {
        $placeholders = implode(', ', array_fill(0, count($chunk), '?'));
        $sql = sprintf('SELECT %s, %s FROM %s WHERE %s IN (%s)', $idColumn, $valueColumn, $table, $valueColumn, $placeholders);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($chunk);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $normalized = normalizeAssocRow($row);
            $value = trim((string)($normalized[$valueColumn] ?? ''));
            if ($value !== '' && !isset($map[$value])) {
                $map[$value] = (int)($normalized[$idColumn] ?? 0);
            }
        }
    }

    return $map;
}

/**
 * Process uploaded data and insert into database
 */
function processUploadData($data) {
    global $pdo;
    
    if (empty($data)) {
        return ['success' => false, 'message' => 'File is empty'];
    }
    
    $providers_added = 0;
    $trainers_added = 0;
    $courses_added = 0;
    $participants_added = 0;
    $errors = [];
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    
    $validRows = [];
    $providers_map = [];
    $trainers_map = [];
    $items_map = [];
    $participant_inputs = [];
    $participant_departments = [];
    $assignment_pairs = [];
    
    foreach ($data as $idx => $row) {
        $row = array_map('trim', $row); // Trim whitespace
        $row = normalizeImportedRow($row);
        
        $validRows[] = $row;
        
        // Provider
        $provider_key = $row['TP_Name'];
        if (!isset($providers_map[$provider_key])) {
            $providers_map[$provider_key] = [
                'name' => $row['TP_Name']
            ];
        }
        
        // Trainer
        $trainer_key = $row['Trainer_Name'];
        $trainerStatus = trim((string)($row['Trainer_Status'] ?? ''));
        $trainerStatus = $trainerStatus === '' ? 'Active' : $trainerStatus;
        if (!isset($trainers_map[$trainer_key])) {
            $trainers_map[$trainer_key] = [
                'name' => $row['Trainer_Name'],
                'status' => $trainerStatus
            ];
        } elseif (trim((string)($trainers_map[$trainer_key]['status'] ?? '')) === '' && $trainerStatus !== '') {
            $trainers_map[$trainer_key]['status'] = $trainerStatus;
        }

        $assignment_pairs[$provider_key . '|' . $trainer_key] = [$provider_key, $trainer_key];

        if (!empty($row['Item_Name'])) {
            $item_key = $provider_key . '|' . $trainer_key . '|' . $row['Item_Name'];
            if (!isset($items_map[$item_key])) {
                $items_map[$item_key] = [
                    'provider_name' => $provider_key,
                    'trainer_name' => $trainer_key,
                    'item_name' => $row['Item_Name'],
                    'item_category' => $row['Item_Category'] ?? null,
                    'item_venue' => $row['Item_Venue'] ?? null,
                ];
            }
        }

        $participantIdentity = participantIdentityValue($row);
        if ($participantIdentity !== '' && !empty($row['Item_Name'])) {
            $participantHash = participantNameHash($participantIdentity);
            $participant_inputs[$participantHash] = [
                'name' => $row['Participant_Name'],
                'identity' => $participantIdentity,
                'hash' => $participantHash,
                'token' => generateParticipantToken(),
                'encrypted' => encryptParticipantName($row['Participant_Name']),
            ];

            $department = trim((string)($row['Participant_Department'] ?? ''));
            if ($department !== '') {
                $participant_departments[$participantHash] = $department;
            }
        }
    }

    if (empty($validRows)) {
        return [
            'success' => false,
            'message' => !empty($errors)
                ? ('No records were imported. First error: ' . $errors[0])
                : 'No records were imported. Please verify your file columns and values.'
        ];
    }

    // Resolve existing providers/trainers in bulk, then insert only the missing rows.
    $providerNames = array_keys($providers_map);
    $trainerNames = array_keys($trainers_map);
    $provider_ids = fetchIdMap($pdo, 'TrainingProvider', 'TP_ID', 'TP_Name', $providerNames);
    $trainer_ids = fetchIdMap($pdo, 'Trainer', 'Trainer_ID', 'Trainer_Name', $trainerNames);

    $missingProviders = [];
    foreach ($providers_map as $name => $provider) {
        if (!isset($provider_ids[$name])) {
            $missingProviders[] = [$provider['name']];
        }
    }
    if (!empty($missingProviders)) {
        bulkInsertRows($pdo, 'TrainingProvider', ['TP_Name'], $missingProviders);
        $providers_added = count($missingProviders);
    }

    $missingTrainers = [];
    foreach ($trainers_map as $name => $trainer) {
        if (!isset($trainer_ids[$name])) {
            // Only insert Trainer_Name into Trainer table; status is now stored in TrainerStatus history table.
            $missingTrainers[] = [$trainer['name']];
        }
    }
    if (!empty($missingTrainers)) {
        bulkInsertRows($pdo, 'Trainer', ['Trainer_Name'], $missingTrainers);
        $trainers_added = count($missingTrainers);
    }

    $provider_ids = fetchIdMap($pdo, 'TrainingProvider', 'TP_ID', 'TP_Name', $providerNames);
    $trainer_ids = fetchIdMap($pdo, 'Trainer', 'Trainer_ID', 'Trainer_Name', $trainerNames);

    $providerStatusRows = [];
    foreach ($missingProviders as $row) {
        $providerId = $provider_ids[$row[0]] ?? null;
        if ($providerId !== null) {
            $providerStatusRows[] = [$providerId, 'Active', date('Y-m-d')];
        }
    }
    if (!empty($providerStatusRows)) {
        bulkInsertRows($pdo, 'TrainingProviderStatus', ['TP_ID', 'TP_Status', 'TP_StatusStartDate'], $providerStatusRows);
    }

    // If the import provided trainer status values (non-Active), persist them into TrainerStatus history table.
    $trainerStatusRows = [];
    foreach ($trainers_map as $name => $trainer) {
        $status = trim((string)($trainer['status'] ?? ''));
        if ($status !== '' && strcasecmp($status, 'Active') !== 0) {
            $trainerId = $trainer_ids[$name] ?? null;
            if ($trainerId !== null) {
                // Store start date as today; reasoning is not provided in import.
                $trainerStatusRows[] = [$trainerId, $status, date('Y-m-d')];
            }
        }
    }
    if (!empty($trainerStatusRows)) {
        // Columns: Trainer_ID, Trainer_Status, Trainer_StatusReasoning, Trainer_StatusStartDate, Trainer_StatusEndDate
        $trainerStatusRows = array_map(function ($row) {
            return [$row[0], $row[1], null, $row[2], null];
        }, $trainerStatusRows);
        bulkInsertRows($pdo, 'TrainerStatus', ['Trainer_ID', 'Trainer_Status', 'Trainer_StatusReasoning', 'Trainer_StatusStartDate', 'Trainer_StatusEndDate'], $trainerStatusRows);
    }

    // Insert assignments in bulk so the item foreign key can rely on existing pairs.
    $assignmentRows = [];
    foreach ($assignment_pairs as $pair) {
        $tpId = $provider_ids[$pair[0]] ?? null;
        $trainerId = $trainer_ids[$pair[1]] ?? null;
        if ($tpId !== null && $trainerId !== null) {
            $assignmentRows[] = [$tpId, $trainerId];
        }
    }
    if (!empty($assignmentRows)) {
        bulkInsertRows($pdo, 'Assignment', ['TP_ID', 'Trainer_ID'], $assignmentRows, true);
    }

    // Insert items in batches and map generated IDs from each batch.
    $itemInsertRows = [];
    foreach ($items_map as $itemKey => $item) {
        $tpId = $provider_ids[$item['provider_name']] ?? null;
        $trainerId = $trainer_ids[$item['trainer_name']] ?? null;
        if ($tpId === null || $trainerId === null) {
            continue;
        }

        $itemInsertRows[$itemKey] = [
            $itemKey,
            $tpId,
            $trainerId,
            $item['item_name'],
            $item['item_category'],
            $item['item_venue'],
        ];
    }

    $itemMap = fetchInsertedItemMap($pdo, array_values($itemInsertRows));
    $courses_added = count($itemInsertRows);

    // Participants are unique by hash, so they can also be resolved and inserted in bulk.
    $participantHashes = array_keys($participant_inputs);
    $participant_ids = fetchIdMap($pdo, 'Participant', 'Participant_ID', 'Participant_Name_Hash', $participantHashes);

    $missingParticipants = [];
    foreach ($participant_inputs as $hash => $participant) {
        if (!isset($participant_ids[$hash])) {
            $missingParticipants[] = [
                $participant['token'],
                $participant['hash'],
                $participant['encrypted'],
                $participant_departments[$hash] ?? null,
            ];
        }
    }
    if (!empty($missingParticipants)) {
        bulkInsertRows($pdo, 'Participant', ['Participant_Token', 'Participant_Name_Hash', 'Participant_Name_Encrypted', 'Participant_Department'], $missingParticipants);
        $participants_added = count($missingParticipants);
    }

    $participant_ids = fetchIdMap($pdo, 'Participant', 'Participant_ID', 'Participant_Name_Hash', $participantHashes);

    // Apply department updates once per participant instead of on every row.
    if (!empty($participant_departments)) {
        $deptStmt = $pdo->prepare('UPDATE Participant SET Participant_Department = ? WHERE Participant_ID = ?');
        foreach ($participant_departments as $hash => $department) {
            $participantId = $participant_ids[$hash] ?? null;
            if ($participantId !== null) {
                $deptStmt->execute([$department, $participantId]);
            }
        }
    }

    // Build enrollments after item and participant IDs are available.
    $enrollmentRows = [];
    foreach ($validRows as $row) {
        if (empty($row['Item_Name']) || empty($row['Participant_Name'])) {
            continue;
        }

        $itemKey = $row['TP_Name'] . '|' . $row['Trainer_Name'] . '|' . $row['Item_Name'];
        $itemId = $itemMap[$itemKey] ?? null;
        $participantIdentity = participantIdentityValue($row);
        $participantHash = participantNameHash($participantIdentity);
        $participantId = $participant_ids[$participantHash] ?? null;

        if ($itemId === null || $participantId === null) {
            continue;
        }

        $enrollmentRows[] = [
            $itemId,
            $participantId,
            normalizeDate($row['Completion_Date'] ?? null),
        ];
    }

    if (!empty($enrollmentRows)) {
        bulkInsertRows($pdo, 'Enrollment', ['Item_ID', 'Participant_ID', 'Completion_Date'], $enrollmentRows, true);
    }
    
    $hasInserts = ($providers_added + $trainers_added + $courses_added + $participants_added) > 0;
    if (!$hasInserts) {
        return [
            'success' => false,
            'message' => !empty($errors)
                ? ('No records were imported. First error: ' . $errors[0])
                : 'No records were imported. Please verify your file columns and values.'
        ];
    }
    
    // Persist weighted AoE defaults into TP_FirstAoE / TP_SecondAoE for empty fields.
    syncProviderExpertiseDefaults();

    return [
        'success' => true,
        'message' => 'Database updated successfully',
        'stats' => [
            'providers_added' => $providers_added,
            'trainers_added' => $trainers_added,
            'courses_added' => $courses_added,
            'participants_added' => $participants_added,
            'errors' => $errors
        ]
    ];
}
?>
