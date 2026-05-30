<?php
require_once '../config.php';
require_once '../includes/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// If DB is not available, return an empty result so frontend still loads
if (!isset($pdo) || $pdo === null) {
    echo json_encode([]);
    exit;
}

$view = $_GET['view'] ?? 'prov';
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$sort = $_GET['sort'] ?? 'asc';

try {
    if ($view === 'prov') {
        $providers = getTrainingProviders();
        
        // Filter by search
        if (!empty($search)) {
            $search_lower = strtolower($search);
            $providers = array_filter($providers, function($p) use ($search_lower) {
                return strpos(strtolower($p['TP_Name']), $search_lower) !== false ||
                       strpos(strtolower($p['TP_FirstAoEDisplay'] ?? ''), $search_lower) !== false ||
                       strpos(strtolower($p['TP_SecondAoEDisplay'] ?? ''), $search_lower) !== false ||
                       strpos(strtolower($p['trainer_names'] ?? ''), $search_lower) !== false;
            });
        }
        
        // Filter by status
        if ($status !== 'all') {
            $status_map = [
                'active' => 'Active',
                'blank' => 'Active',
                'approved' => 'Active',
                'consideration' => 'Greylist',
                'greylist' => 'Greylist',
                'blacklisted' => 'Blacklisted'
            ];
            $status_value = $status_map[$status] ?? $status;
            $providers = array_filter($providers, function($p) use ($status_value) {
                return $p['TP_Status'] === $status_value;
            });
        }
        
        // Sort
        usort($providers, function($a, $b) use ($sort) {
            $cmp = strcasecmp($a['TP_Name'], $b['TP_Name']);
            return $sort === 'asc' ? $cmp : -$cmp;
        });
        
        echo json_encode(array_values($providers));
    } else {
        $trainers = getTrainers();

        // Optional trainer status filter for trainer view
        if ($status === 'redflag') {
            $trainers = array_filter($trainers, function($t) {
                return !empty($t['Trainer_StatusActive']);
            });
        }
        
        // Filter by search
        if (!empty($search)) {
            $search_lower = strtolower($search);
            $trainers = array_filter($trainers, function($t) use ($search_lower) {
                return strpos(strtolower($t['Trainer_Name']), $search_lower) !== false ||
                       strpos(strtolower($t['provider_name'] ?? ''), $search_lower) !== false ||
                       (isset($t['providers']) && is_array($t['providers']) && array_reduce($t['providers'], function($carry, $provider) use ($search_lower) {
                           return $carry || strpos(strtolower($provider['TP_Name'] ?? ''), $search_lower) !== false;
                       }, false));
            });
        }
        
        // Sort
        usort($trainers, function($a, $b) use ($sort) {
            $cmp = strcasecmp($a['Trainer_Name'], $b['Trainer_Name']);
            return $sort === 'asc' ? $cmp : -$cmp;
        });
        
        echo json_encode(array_values($trainers));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
