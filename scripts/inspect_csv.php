<?php
function normalizeHeaders($headers) {
    return array_map(function ($h) {
        $clean = trim((string)$h);
        return preg_replace('/^\xEF\xBB\xBF/', '', $clean);
    }, $headers);
}

function canonicalizeRow($row) {
    $aliases = [
        'TP_Name' => ['TP_Name', 'Training Provider'],
        'Trainer_Name' => ['Trainer_Name', 'Trainers/Speaker Name', 'Trainer', 'Speaker Name', 'Trainer/Speaker Name'],
        'Item_Name' => ['Item_Name', 'Item Title', 'Course Name', 'Training Title', 'Item Title'],
        'Item_Category' => ['Item_Category', 'Category'],
        'Participant_Name' => ['Participant_Name', 'Full Name', 'Participant'],
        'Participant_Department' => ['Participant_Department', 'Department'],
        'Completion_Date' => ['Completion_Date', 'Completion Date']
    ];

    $normalized = [];
    foreach ($aliases as $target => $candidates) {
        $normalized[$target] = '';
        foreach ($candidates as $candidate) {
            if (array_key_exists($candidate, $row) && trim((string)$row[$candidate]) !== '') {
                $normalized[$target] = trim((string)$row[$candidate]);
                break;
            }
        }
    }

    return $normalized;
}

function parseCSV($file_path) {
    $data = [];
    $file = fopen($file_path, 'r');
    $header = null;
    $line = 0;
    while (($row = fgetcsv($file)) !== false) {
        $line++;
        if ($header === null) {
            $header = normalizeHeaders($row);
            // echo "Header (line $line):\n";
            // print_r($header);
        } else {
            // ensure row length matches header
            if (count($row) < count($header)) {
                $row = array_pad($row, count($header), '');
            } elseif (count($row) > count($header)) {
                $row = array_slice($row, 0, count($header));
            }
            $assoc = array_combine($header, $row);
            if ($assoc !== false) {
                $data[] = canonicalizeRow($assoc);
            }
        }
        if ($line > 5) break;
    }
    fclose($file);
    return $data;
}

$path = __DIR__ . '/../Updated Training Data.csv';
if (!file_exists($path)) {
    echo "File not found: $path\n";
    exit(1);
}
$rows = parseCSV($path);
foreach ($rows as $i => $r) {
    echo "Row " . ($i+1) . " canonicalized:\n";
    print_r($r);
}

?>