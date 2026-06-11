<?php
function normalizeHeaders($headers) {
    return array_map(function ($h) {
        $clean = trim((string)$h);
        return preg_replace('/^\xEF\xBB\xBF/', '', $clean);
    }, $headers);
}

function canonicalizeRow($row) {
    $normalizeKey = function ($value) {
        return strtolower(preg_replace('/[^a-z0-9]/', '', (string)$value));
    };

    $aliases = [
        'TP_Name' => ['TP_Name', 'Training Provider'],
        'Trainer_Name' => ['Trainer_Name', 'Trainers/Speaker Name', 'Trainer', 'Speaker Name', 'Trainer/Speaker Name'],
        'Item_Name' => ['Item_Name', 'Item Title', 'Course Name', 'Training Title'],
        'Item_Category' => ['Item_Category', 'Category', 'Category Description'],
        'Participant_Name' => ['Participant_Name', 'Full Name', 'Participant'],
        'Participant_Department' => ['Participant_Department', 'Department', 'Organisation Description'],
        'Completion_Date' => ['Completion_Date', 'Completion Date']
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

    if ($normalized['Participant_Name'] === '') {
        $firstName = $lookup[$normalizeKey('First Name')] ?? '';
        $lastName = $lookup[$normalizeKey('Last Name')] ?? '';
        $combined = trim($firstName . ' ' . $lastName);
        if ($combined !== '') {
            $normalized['Participant_Name'] = $combined;
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