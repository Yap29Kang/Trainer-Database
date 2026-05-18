<?php
require_once '../config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

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
            $assoc = array_combine($header, $row);
            if ($assoc !== false) {
                $data[] = canonicalizeRow($assoc);
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

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
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

        $assoc = canonicalizeRow($assoc);

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
    
    // Expected columns in CSV/Excel:
    // Provider: TP_Name
    // Trainer: Trainer_Name
    // Item/Course: Item_Name, Item_Category, TP_ID, Trainer_ID
    // Participant: Participant_Name, Item_ID
    
    // First pass: collect unique providers and trainers
    $providers_map = [];
    $trainers_map = [];
    
    foreach ($data as $idx => $row) {
        $row = array_map('trim', $row); // Trim whitespace
        
        // Check required fields
        if (empty($row['TP_Name']) || empty($row['Trainer_Name'])) {
            $errors[] = "Row " . ($idx + 2) . ": Missing TP_Name or Trainer_Name";
            continue;
        }
        
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
        $trainerStatus = $trainerStatus === '' ? null : $trainerStatus;
        if (!isset($trainers_map[$trainer_key])) {
            $trainers_map[$trainer_key] = [
                'name' => $row['Trainer_Name'],
                'status' => $trainerStatus
            ];
        } elseif ($trainers_map[$trainer_key]['status'] === null && $trainerStatus !== null) {
            $trainers_map[$trainer_key]['status'] = $trainerStatus;
        }
    }
    
    // Insert providers
    $provider_ids = [];
    foreach ($providers_map as $key => $provider) {
        try {
            // Insert provider and obtain ID (use RETURNING for Postgres)
            if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
                $sql = "INSERT INTO TrainingProvider (TP_Name) VALUES (?) RETURNING TP_ID";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$provider['name']]);
                $provider_ids[$key] = (int)$stmt->fetchColumn();
            } else {
                $sql = "INSERT INTO TrainingProvider (TP_Name) VALUES (?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$provider['name']]);
                $provider_ids[$key] = $pdo->lastInsertId();
            }
            $providers_added++;

            // New providers default to a blank status in history.
            $statusSql = "INSERT INTO TrainingProviderStatus (TP_ID, TP_Status, TP_StatusStartDate) VALUES (?, '', CURRENT_DATE)";
            $statusStmt = $pdo->prepare($statusSql);
            $statusStmt->execute([$provider_ids[$key]]);
        } catch (Exception $e) {
            // Fallback: provider may already exist.
            try {
                $sel = $pdo->prepare('SELECT TP_ID FROM TrainingProvider WHERE TP_Name = ? ORDER BY TP_ID ASC LIMIT 1');
                $sel->execute([$provider['name']]);
                $existing = normalizeAssocRow($sel->fetch(PDO::FETCH_ASSOC));
                $existingId = $existing['TP_ID'] ?? null;
                if ($existingId !== null) {
                    $provider_ids[$key] = (int)$existingId;
                } else {
                    $errors[] = "Provider '{$provider['name']}': " . $e->getMessage();
                }
            } catch (Exception $inner) {
                $errors[] = "Provider '{$provider['name']}': " . $e->getMessage();
            }
        }
    }
    
    // Insert trainers
    $trainer_ids = [];
    foreach ($trainers_map as $key => $trainer) {
        try {
            // Insert trainer and obtain ID
            if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
                $sql = "INSERT INTO Trainer (Trainer_Name, Trainer_Status) VALUES (?, ?) RETURNING Trainer_ID";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$trainer['name'], $trainer['status']]);
                $trainer_ids[$key] = (int)$stmt->fetchColumn();
            } else {
                $sql = "INSERT INTO Trainer (Trainer_Name, Trainer_Status) VALUES (?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$trainer['name'], $trainer['status']]);
                $trainer_ids[$key] = $pdo->lastInsertId();
            }
            $trainers_added++;
        } catch (Exception $e) {
                // Fallback: trainer may already exist. Resolve ID and update status if provided.
                try {
                    $sel = $pdo->prepare('SELECT Trainer_ID, Trainer_Status FROM Trainer WHERE Trainer_Name = ? ORDER BY Trainer_ID ASC LIMIT 1');
                    $sel->execute([$trainer['name']]);
                    $existing = normalizeAssocRow($sel->fetch(PDO::FETCH_ASSOC));
                    $existingId = $existing['TRAINER_ID'] ?? null;
                    if ($existingId !== null) {
                        $trainer_ids[$key] = (int)$existingId;

                        $existingStatus = trim((string)($existing['TRAINER_STATUS'] ?? ''));
                        $incomingStatus = trim((string)($trainer['status'] ?? ''));
                        if ($incomingStatus !== '' && $existingStatus === '') {
                            $upd = $pdo->prepare('UPDATE Trainer SET Trainer_Status = ? WHERE Trainer_ID = ?');
                            $upd->execute([$incomingStatus, $trainer_ids[$key]]);
                        }
                    } else {
                        $errors[] = "Trainer '{$trainer['name']}': " . $e->getMessage();
                    }
                } catch (Exception $inner) {
                    $errors[] = "Trainer '{$trainer['name']}': " . $e->getMessage();
                }
        }
    }
    
    // Second pass: insert assignments, courses, participants
    $item_map = []; // Map course names to IDs
    
    foreach ($data as $idx => $row) {
        $row = array_map('trim', $row);
        
        if (empty($row['TP_Name']) || empty($row['Trainer_Name'])) {
            continue;
        }
        
        try {
            $tp_id = $provider_ids[$row['TP_Name']] ?? null;
            $trainer_id = $trainer_ids[$row['Trainer_Name']] ?? null;
            
            if (!$tp_id || !$trainer_id) {
                continue;
            }
            
            // Insert assignment if not exists
                try {
                    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
                        $sql = "INSERT INTO Assignment (TP_ID, Trainer_ID) VALUES (?, ?) ON CONFLICT (TP_ID, Trainer_ID) DO NOTHING";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$tp_id, $trainer_id]);
                    } else {
                        $sql = "INSERT IGNORE INTO Assignment (TP_ID, Trainer_ID) VALUES (?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$tp_id, $trainer_id]);
                    }
                } catch (Exception $e) {
                    // Assignment might already exist, continue
                }
            
            // Insert item/course if provided
            if (!empty($row['Item_Name'])) {
                $item_key = $tp_id . '|' . $trainer_id . '|' . $row['Item_Name'];
                    if (!isset($item_map[$item_key])) {
                        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
                            $sql = "INSERT INTO Item (TP_ID, Trainer_ID, Item_Name, Item_Category, Item_Venue) VALUES (?, ?, ?, ?, ?) RETURNING Item_ID";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([
                                $tp_id,
                                $trainer_id,
                                $row['Item_Name'],
                                $row['Item_Category'] ?? null,
                                $row['Item_Venue'] ?? null
                            ]);
                            $item_map[$item_key] = (int)$stmt->fetchColumn();
                        } else {
                            $sql = "INSERT INTO Item (TP_ID, Trainer_ID, Item_Name, Item_Category, Item_Venue) VALUES (?, ?, ?, ?, ?)";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([
                                $tp_id,
                                $trainer_id,
                                $row['Item_Name'],
                                $row['Item_Category'] ?? null,
                                $row['Item_Venue'] ?? null
                            ]);
                            $item_map[$item_key] = $pdo->lastInsertId();
                        }
                        $courses_added++;
                    }
                
                // Insert participants if provided
                if (!empty($row['Participant_Name']) && isset($item_map[$item_key])) {
                    $item_id = $item_map[$item_key];
                    
                    // Check if participant exists
                    $participantHash = participantNameHash($row['Participant_Name']);
                    $sql = "SELECT Participant_ID FROM Participant WHERE Participant_Name_Hash = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$participantHash]);
                    $participant = normalizeAssocRow($stmt->fetch(PDO::FETCH_ASSOC));

                    $department = trim((string)($row['Participant_Department'] ?? ''));
                    $department = $department === '' ? null : $department;

                    if (!$participant) {
                        $participantToken = generateParticipantToken();
                        $participantEncrypted = encryptParticipantName($row['Participant_Name']);

                        // Create participant (use RETURNING on Postgres)
                        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
                            $sql = "INSERT INTO Participant (Participant_Token, Participant_Name_Hash, Participant_Name_Encrypted, Participant_Department) VALUES (?, ?, ?, ?) RETURNING Participant_ID";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$participantToken, $participantHash, $participantEncrypted, $department]);
                            $participant_id = (int)$stmt->fetchColumn();
                        } else {
                            $sql = "INSERT INTO Participant (Participant_Token, Participant_Name_Hash, Participant_Name_Encrypted, Participant_Department) VALUES (?, ?, ?, ?)";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$participantToken, $participantHash, $participantEncrypted, $department]);
                            $participant_id = $pdo->lastInsertId();
                        }
                        $participants_added++;
                    } else {
                        $participant_id = $participant['Participant_ID'] ?? null;

                        // Fill/update department whenever upload includes it.
                        if ($participant_id !== null && $department !== null) {
                            $upd = $pdo->prepare('UPDATE Participant SET Participant_Department = ? WHERE Participant_ID = ?');
                            $upd->execute([$department, $participant_id]);
                        }
                    }

                    if ($participant_id === null) {
                        $errors[] = "Row " . ($idx + 2) . ": Unable to resolve participant ID for enrollment";
                        continue;
                    }
                    
                    // Insert enrollment
                        try {
                            if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
                                $sql = "INSERT INTO Enrollment (Item_ID, Participant_ID, Completion_Date) VALUES (?, ?, ?) ON CONFLICT (Item_ID, Participant_ID) DO NOTHING";
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute([
                                    $item_id,
                                    $participant_id,
                                    normalizeDate($row['Completion_Date'] ?? null)
                                ]);
                            } else {
                                $sql = "INSERT IGNORE INTO Enrollment (Item_ID, Participant_ID, Completion_Date) VALUES (?, ?, ?)";
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute([
                                    $item_id,
                                    $participant_id,
                                    normalizeDate($row['Completion_Date'] ?? null)
                                ]);
                            }
                        } catch (Exception $e) {
                            // Enrollment might already exist
                        }
                }
            }
        } catch (Exception $e) {
            $errors[] = "Row " . ($idx + 2) . ": " . $e->getMessage();
        }
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
