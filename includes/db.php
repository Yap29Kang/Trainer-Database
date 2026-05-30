<?php
require_once __DIR__ . '/../config.php';

function getEncryptionKeyBytes() {
    $key = defined('APP_ENCRYPTION_KEY') ? (string)APP_ENCRYPTION_KEY : '';
    if ($key === '') {
        return null;
    }

    return hash('sha256', $key, true);
}

function encryptParticipantName($plainText) {
    $keyBytes = getEncryptionKeyBytes();
    if ($keyBytes === null) {
        throw new RuntimeException('APP_ENCRYPTION_KEY is not configured');
    }

    $iv = random_bytes(12);
    $tag = '';
    $cipherText = openssl_encrypt($plainText, 'aes-256-gcm', $keyBytes, OPENSSL_RAW_DATA, $iv, $tag);
    if ($cipherText === false) {
        throw new RuntimeException('Failed to encrypt participant name');
    }

    return base64_encode($iv . $tag . $cipherText);
}

function decryptParticipantName($payload) {
    $keyBytes = getEncryptionKeyBytes();
    if ($keyBytes === null) {
        return null;
    }

    $decoded = base64_decode((string)$payload, true);
    if ($decoded === false || strlen($decoded) < 29) {
        return null;
    }

    $iv = substr($decoded, 0, 12);
    $tag = substr($decoded, 12, 16);
    $cipherText = substr($decoded, 28);
    $plainText = openssl_decrypt($cipherText, 'aes-256-gcm', $keyBytes, OPENSSL_RAW_DATA, $iv, $tag);

    return $plainText === false ? null : $plainText;
}

function participantNameHash($plainText) {
    $keyBytes = getEncryptionKeyBytes();
    if ($keyBytes === null) {
        throw new RuntimeException('APP_ENCRYPTION_KEY is not configured');
    }

    $normalized = trim((string)$plainText);
    if (function_exists('mb_strtolower')) {
        $normalized = mb_strtolower($normalized, 'UTF-8');
    } else {
        $normalized = strtolower($normalized);
    }

    return hash_hmac('sha256', $normalized, $keyBytes);
}

function generateParticipantToken() {
    return 'ptk_' . bin2hex(random_bytes(16));
}

function normalizeAssocRow($row) {
    if (!is_array($row)) {
        return $row;
    }

    $normalized = array_change_key_case($row, CASE_UPPER);

    $aliases = [
        'TP_ID' => 'TP_ID',
        'TP_NAME' => 'TP_Name',
        'TP_FIRSTAOE' => 'TP_FirstAoE',
        'TP_SECONDAOE' => 'TP_SecondAoE',
        'TP_STATUS_ID' => 'TP_Status_ID',
        'TP_STATUS' => 'TP_Status',
        'TP_STATUSREASONING' => 'TP_StatusReasoning',
        'TP_STATUSSTARTDATE' => 'TP_StatusStartDate',
        'TP_STATUSENDDATE' => 'TP_StatusEndDate',
        'TP_REMARK_ID' => 'TP_Remark_ID',
        'TRAINER_ID' => 'Trainer_ID',
        'TRAINER_NAME' => 'Trainer_Name',
        'TRAINER_STATUS_ID' => 'Trainer_Status_ID',
        'TRAINER_STATUSREASONING' => 'Trainer_StatusReasoning',
        'TRAINER_STATUSSTARTDATE' => 'Trainer_StatusStartDate',
        'TRAINER_STATUSENDDATE' => 'Trainer_StatusEndDate',
        'TRAINER_REMARK_ID' => 'Trainer_Remark_ID',
        'TRAINER_NAMES' => 'trainer_names',
        'ITEM_ID' => 'Item_ID',
        'ITEM_NAME' => 'Item_Name',
        'ITEM_CATEGORY' => 'Item_Category',
        'ITEM_VENUE' => 'Item_Venue',
        'PARTICIPANT_ID' => 'Participant_ID',
        'PARTICIPANT_TOKEN' => 'Participant_Token',
        'PARTICIPANT_NAME_HASH' => 'Participant_Name_Hash',
        'PARTICIPANT_NAME_ENCRYPTED' => 'Participant_Name_Encrypted',
        'PARTICIPANT_DEPARTMENT' => 'Participant_Department',
        'COMPLETION_DATE' => 'Completion_Date',
        'COMPLETION_YEAR' => 'Completion_Year',
        'COURSE_NAME' => 'Course_Name',
        'REMARK_TEXT' => 'Remark_Text',
        'REMARK_DATE' => 'Remark_Date',
        'COUNT' => 'count',
        'ITEM_COUNT' => 'item_count',
        'PROVIDER_COUNT' => 'provider_count',
        'COURSE_COUNT' => 'course_count',
        'PARTICIPANT_COUNT' => 'participant_count',
        'TRAINER_COUNT' => 'trainer_count',
        'PAX_COUNT' => 'pax_count',
    ];

    foreach ($aliases as $sourceKey => $aliasKey) {
        if (array_key_exists($sourceKey, $normalized)) {
            $normalized[$aliasKey] = $normalized[$sourceKey];
        }
    }

    return $normalized;
}

function normalizeAssocRows($rows) {
    if (!is_array($rows)) {
        return $rows;
    }

    return array_map('normalizeAssocRow', $rows);
}

function normalizeProviderStatusLabel($status) {
    $status = trim((string)$status);

    if ($status === '' || strcasecmp($status, 'Approved') === 0) {
        return 'Active';
    }

    if (strcasecmp($status, 'In Consideration') === 0 || strcasecmp($status, 'Greylist') === 0) {
        return 'Greylist';
    }

    if (strcasecmp($status, 'Blacklisted') === 0) {
        return 'Blacklisted';
    }

    return $status;
}

function isProviderBlacklistExpired($endDate) {
    if (empty($endDate)) {
        return false;
    }

    try {
        $today = new DateTimeImmutable('today');
        $end = new DateTimeImmutable($endDate);
        return $today > $end;
    } catch (Exception $e) {
        return false;
    }
}

function getEffectiveProviderStatus($rawStatus, $endDate = null) {
    $status = normalizeProviderStatusLabel($rawStatus);

    if ($status === 'Blacklisted' && isProviderBlacklistExpired($endDate)) {
        return 'Greylist';
    }

    return $status;
}

function getTrainingProviderStatusHistory($tp_id) {
    global $pdo;

    $sql = "
        SELECT
            TP_Status_ID,
            TP_Status,
            TP_StatusReasoning,
            TP_StatusStartDate,
            TP_StatusEndDate
        FROM TrainingProviderStatus
        WHERE TP_ID = ?
        ORDER BY COALESCE(TP_StatusStartDate, '1000-01-01') DESC, TP_Status_ID DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tp_id]);

    $rows = [];
    foreach (normalizeAssocRows($stmt->fetchAll(PDO::FETCH_ASSOC)) as $row) {
        $rawStatus = $row['TP_Status'] ?? '';
        $row['TP_StatusRaw'] = $rawStatus;
        $row['TP_StatusDisplay'] = normalizeProviderStatusLabel($rawStatus) ?: 'Active';
        $row['TP_StatusEffective'] = getEffectiveProviderStatus($rawStatus, $row['TP_StatusEndDate'] ?? null);
        $row['TP_StatusExpired'] = (
            normalizeProviderStatusLabel($rawStatus) === 'Blacklisted'
            && isProviderBlacklistExpired($row['TP_StatusEndDate'] ?? null)
        );
        $rows[] = $row;
    }

    return $rows;
}

function normalizeTrainerStatusLabel($status) {
    $status = trim((string)$status);

    if ($status === '' || strcasecmp($status, 'Red Flag') === 0 || strcasecmp($status, 'Red Flagged') === 0) {
        return $status === '' ? '' : 'Red Flag';
    }

    return $status;
}

function getTrainerStatusHistory($trainer_id) {
    global $pdo;

    $sql = "
        SELECT
            Trainer_Status_ID,
            Trainer_ID,
            Trainer_Status,
            Trainer_StatusReasoning,
            Trainer_StatusStartDate,
            Trainer_StatusEndDate
        FROM TrainerStatus
        WHERE Trainer_ID = ?
        ORDER BY COALESCE(Trainer_StatusStartDate, '1000-01-01') DESC, Trainer_Status_ID DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$trainer_id]);

    $rows = [];
    foreach (normalizeAssocRows($stmt->fetchAll(PDO::FETCH_ASSOC)) as $row) {
        $rawStatus = $row['Trainer_Status'] ?? '';
        $row['Trainer_StatusRaw'] = $rawStatus;
        $row['Trainer_StatusDisplay'] = normalizeTrainerStatusLabel($rawStatus) ?: 'Red Flag';
        $row['Trainer_StatusActive'] = empty($row['Trainer_StatusEndDate']);
        $rows[] = $row;
    }

    return $rows;
}

function getTrainingProviderRemarks($tp_id) {
    global $pdo;

    $sql = "
        SELECT
            TP_Remark_ID,
            TP_ID,
            Remark_Text,
            Remark_Date
        FROM TrainingProviderRemark
        WHERE TP_ID = ?
        ORDER BY Remark_Date DESC, TP_Remark_ID DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tp_id]);

    return normalizeAssocRows($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function getTrainerRemarks($trainer_id) {
    global $pdo;

    $sql = "
        SELECT
            Trainer_Remark_ID,
            Trainer_ID,
            Remark_Text,
            Remark_Date
        FROM TrainerRemark
        WHERE Trainer_ID = ?
        ORDER BY Remark_Date DESC, Trainer_Remark_ID DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$trainer_id]);

    return normalizeAssocRows($stmt->fetchAll(PDO::FETCH_ASSOC));
}

/**
 * Fetch latest provider status per provider.
 */
function getLatestProviderStatusMap() {
    global $pdo;

    $sql = "
        SELECT
            tp.TP_ID,
            tps.TP_Status,
            tps.TP_StatusReasoning,
            tps.TP_StatusStartDate,
            tps.TP_StatusEndDate
        FROM TrainingProvider tp
        LEFT JOIN TrainingProviderStatus tps
            ON tps.TP_Status_ID = (
                SELECT tps2.TP_Status_ID
                FROM TrainingProviderStatus tps2
                WHERE tps2.TP_ID = tp.TP_ID
                ORDER BY COALESCE(tps2.TP_StatusStartDate, '1000-01-01') DESC, tps2.TP_Status_ID DESC
                LIMIT 1
            )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $map = [];
    foreach (normalizeAssocRows($stmt->fetchAll(PDO::FETCH_ASSOC)) as $row) {
        $effectiveStatus = getEffectiveProviderStatus(
            $row['TP_Status'] ?? '',
            $row['TP_StatusEndDate'] ?? null
        );

        $map[$row['TP_ID']] = [
            'TP_Status' => $effectiveStatus,
            'TP_StatusRaw' => $row['TP_Status'] ?? '',
            'TP_StatusReasoning' => $row['TP_StatusReasoning'] ?? null,
            'TP_StatusStartDate' => $row['TP_StatusStartDate'] ?? null,
            'TP_StatusEndDate' => $row['TP_StatusEndDate'] ?? null,
            'TP_StatusExpired' => $effectiveStatus === 'Greylist' && normalizeProviderStatusLabel($row['TP_Status'] ?? '') === 'Blacklisted'
        ];
    }

    return $map;
}

/**
 * Fetch latest trainer status per trainer.
 */
function getLatestTrainerStatusMap() {
    global $pdo;

    $sql = "
        SELECT
            t.Trainer_ID,
            ts.Trainer_Status,
            ts.Trainer_StatusReasoning,
            ts.Trainer_StatusStartDate,
            ts.Trainer_StatusEndDate
        FROM Trainer t
        LEFT JOIN TrainerStatus ts
            ON ts.Trainer_Status_ID = (
                SELECT ts2.Trainer_Status_ID
                FROM TrainerStatus ts2
                WHERE ts2.Trainer_ID = t.Trainer_ID
                ORDER BY COALESCE(ts2.Trainer_StatusStartDate, '1000-01-01') DESC, ts2.Trainer_Status_ID DESC
                LIMIT 1
            )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $map = [];
    foreach (normalizeAssocRows($stmt->fetchAll(PDO::FETCH_ASSOC)) as $row) {
        $rawStatus = $row['Trainer_Status'] ?? '';
        $effectiveStatus = (!empty($rawStatus) && empty($row['Trainer_StatusEndDate'])) ? 'Red Flag' : '';

        $map[$row['Trainer_ID']] = [
            'Trainer_Status' => $effectiveStatus,
            'Trainer_StatusRaw' => $rawStatus,
            'Trainer_StatusReasoning' => $row['Trainer_StatusReasoning'] ?? null,
            'Trainer_StatusStartDate' => $row['Trainer_StatusStartDate'] ?? null,
            'Trainer_StatusEndDate' => $row['Trainer_StatusEndDate'] ?? null,
            'Trainer_StatusActive' => $effectiveStatus !== ''
        ];
    }

    return $map;
}

/**
 * Compute top 2 Areas of Expertise from item categories per provider.
 */
function getProviderExpertiseMap() {
    global $pdo;

    $sql = "
        SELECT
            i.TP_ID,
            i.Item_Category,
            COUNT(*) AS item_count
        FROM Item i
        WHERE i.Item_Category IS NOT NULL
          AND TRIM(i.Item_Category) <> ''
        GROUP BY i.TP_ID, i.Item_Category
        ORDER BY i.TP_ID, item_count DESC, i.Item_Category ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = normalizeAssocRows($stmt->fetchAll(PDO::FETCH_ASSOC));

    $bucket = [];
    foreach ($rows as $row) {
        $tpId = (int)$row['TP_ID'];
        if (!isset($bucket[$tpId])) {
            $bucket[$tpId] = [];
        }
        $bucket[$tpId][] = [
            'category' => $row['ITEM_CATEGORY'],
            'count' => (int)$row['ITEM_COUNT']
        ];
    }

    $map = [];
    foreach ($bucket as $tpId => $categories) {
        $total = array_sum(array_map(function ($x) {
            return $x['count'];
        }, $categories));

        $first = null;
        $second = null;

        if ($total > 0 && isset($categories[0])) {
            $pct = (int)round(($categories[0]['count'] / $total) * 100);
            $first = [
                'name' => $categories[0]['category'],
                'percent' => $pct,
                'display' => $categories[0]['category']
            ];
        }

        if ($total > 0 && isset($categories[1])) {
            $pct = (int)round(($categories[1]['count'] / $total) * 100);
            $second = [
                'name' => $categories[1]['category'],
                'percent' => $pct,
                'display' => $categories[1]['category']
            ];
        }

        $map[$tpId] = [
            'first' => $first,
            'second' => $second
        ];
    }

    return $map;
}

/**
 * Persist computed expertise defaults into TrainingProvider.
 * Only fills empty TP_FirstAoE / TP_SecondAoE fields and never overwrites manual values.
 */
function syncProviderExpertiseDefaults($tpId = null) {
    global $pdo;

    $expertiseMap = getProviderExpertiseMap();
    if (empty($expertiseMap)) {
        return;
    }

    if ($tpId !== null) {
        $stmt = $pdo->prepare('SELECT TP_ID, TP_FirstAoE, TP_SecondAoE FROM TrainingProvider WHERE TP_ID = ?');
        $stmt->execute([(int)$tpId]);
        $row = normalizeAssocRow($stmt->fetch(PDO::FETCH_ASSOC));
        if (!$row) {
            return;
        }

        $calc = $expertiseMap[(int)$row['TP_ID']] ?? ['first' => null, 'second' => null];
        $firstCurrent = trim((string)($row['TP_FirstAoE'] ?? ''));
        $secondCurrent = trim((string)($row['TP_SecondAoE'] ?? ''));

        $firstNext = $firstCurrent !== '' ? $row['TP_FirstAoE'] : ($calc['first']['name'] ?? null);
        $secondNext = $secondCurrent !== '' ? $row['TP_SecondAoE'] : ($calc['second']['name'] ?? null);

        if ($firstNext !== $row['TP_FirstAoE'] || $secondNext !== $row['TP_SecondAoE']) {
            $upd = $pdo->prepare('UPDATE TrainingProvider SET TP_FirstAoE = ?, TP_SecondAoE = ? WHERE TP_ID = ?');
            $upd->execute([$firstNext, $secondNext, (int)$row['TP_ID']]);
        }

        return;
    }

    $stmt = $pdo->prepare('SELECT TP_ID, TP_FirstAoE, TP_SecondAoE FROM TrainingProvider');
    $stmt->execute();
    $rows = normalizeAssocRows($stmt->fetchAll(PDO::FETCH_ASSOC));

    $upd = $pdo->prepare('UPDATE TrainingProvider SET TP_FirstAoE = ?, TP_SecondAoE = ? WHERE TP_ID = ?');
    foreach ($rows as $row) {
        $providerId = (int)$row['TP_ID'];
        $calc = $expertiseMap[$providerId] ?? null;
        if (!$calc) {
            continue;
        }

        $firstCurrent = trim((string)($row['TP_FirstAoE'] ?? ''));
        $secondCurrent = trim((string)($row['TP_SecondAoE'] ?? ''));

        $firstNext = $firstCurrent !== '' ? $row['TP_FirstAoE'] : ($calc['first']['name'] ?? null);
        $secondNext = $secondCurrent !== '' ? $row['TP_SecondAoE'] : ($calc['second']['name'] ?? null);

        if ($firstNext !== $row['TP_FirstAoE'] || $secondNext !== $row['TP_SecondAoE']) {
            $upd->execute([$firstNext, $secondNext, $providerId]);
        }
    }
}

/**
 * Get all training providers with counts
 */
function getTrainingProviders() {
    global $pdo;
    // Ensure computed AoE defaults are persisted for providers with empty AoE fields.
    syncProviderExpertiseDefaults();

    $sql = "
        SELECT 
            tp.TP_ID,
            tp.TP_Name,
            tp.TP_FirstAoE,
            tp.TP_SecondAoE,
            COALESCE(trainer_names.trainer_names, '') AS trainer_names,
            COALESCE(trainer_counts.trainer_count, 0) AS trainer_count,
            COALESCE(course_counts.course_count, 0) AS course_count,
            COALESCE(participant_counts.participant_count, 0) AS participant_count
        FROM TrainingProvider tp
        LEFT JOIN (
            SELECT
                a.TP_ID,
                string_agg(DISTINCT t2.Trainer_Name, '||' ORDER BY t2.Trainer_Name) AS trainer_names
            FROM Assignment a
            INNER JOIN Trainer t2 ON t2.Trainer_ID = a.Trainer_ID
            GROUP BY a.TP_ID
        ) trainer_names ON trainer_names.TP_ID = tp.TP_ID
        LEFT JOIN (
            SELECT a.TP_ID, COUNT(DISTINCT a.Trainer_ID) AS trainer_count
            FROM Assignment a
            GROUP BY a.TP_ID
        ) trainer_counts ON trainer_counts.TP_ID = tp.TP_ID
        LEFT JOIN (
            SELECT i.TP_ID, COUNT(*) AS course_count
            FROM Item i
            GROUP BY i.TP_ID
        ) course_counts ON course_counts.TP_ID = tp.TP_ID
        LEFT JOIN (
            SELECT i.TP_ID, COUNT(DISTINCT e.Participant_ID) AS participant_count
            FROM Item i
            INNER JOIN Enrollment e ON e.Item_ID = i.Item_ID
            GROUP BY i.TP_ID
        ) participant_counts ON participant_counts.TP_ID = tp.TP_ID
        ORDER BY tp.TP_Name
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $providers = normalizeAssocRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    $statusMap = getLatestProviderStatusMap();
    $expertiseMap = getProviderExpertiseMap();

    foreach ($providers as &$provider) {
        $tpId = (int)$provider['TP_ID'];

        $status = $statusMap[$tpId] ?? ['TP_Status' => '', 'TP_StatusReasoning' => null];
        $provider['TP_Status'] = $status['TP_Status'];
        $provider['TP_StatusReasoning'] = $status['TP_StatusReasoning'];
        $provider['TP_StatusEndDate'] = $status['TP_StatusEndDate'] ?? null;
        $provider['blacklistReason'] = ($provider['TP_Status'] === 'Blacklisted') ? ($status['TP_StatusReasoning'] ?? null) : null;

        // Manual AoE values take priority over computed values from course categories.
        if (!empty($provider['TP_FirstAoE'])) {
            $first = [
                'name' => $provider['TP_FirstAoE'],
                'percent' => null,
                'display' => $provider['TP_FirstAoE']
            ];
        } else {
            $first = $expertiseMap[$tpId]['first'] ?? null;
        }

        if (!empty($provider['TP_SecondAoE'])) {
            $second = [
                'name' => $provider['TP_SecondAoE'],
                'percent' => null,
                'display' => $provider['TP_SecondAoE']
            ];
        } else {
            $second = $expertiseMap[$tpId]['second'] ?? null;
        }

        $provider['TP_FirstAoE'] = $first['name'] ?? null;
        $provider['TP_SecondAoE'] = $second['name'] ?? null;
        $provider['TP_FirstAoEPercent'] = $first['percent'] ?? null;
        $provider['TP_SecondAoEPercent'] = $second['percent'] ?? null;
        $provider['TP_FirstAoEDisplay'] = $first['display'] ?? null;
        $provider['TP_SecondAoEDisplay'] = $second['display'] ?? null;
    }
    unset($provider);

    return $providers;
}

/**
 * Get single training provider with all details
 */
function getTrainingProviderDetail($tp_id) {
    global $pdo;

    // Ensure this provider has persisted default AoE values if fields are still empty.
    syncProviderExpertiseDefaults((int)$tp_id);
    
    $sql = "SELECT TP_ID, TP_Name, TP_FirstAoE, TP_SecondAoE FROM TrainingProvider WHERE TP_ID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tp_id]);
    $provider = normalizeAssocRow($stmt->fetch(PDO::FETCH_ASSOC));
    
    if (!$provider) return null;
    
    // Get trainers assigned to this provider
    $sql = "
        SELECT t.* FROM Trainer t
        INNER JOIN Assignment a ON t.Trainer_ID = a.Trainer_ID
        WHERE a.TP_ID = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tp_id]);
    $provider['trainers'] = normalizeAssocRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    // Get courses
    $sql = "
        SELECT
            i.Item_ID,
            i.TP_ID,
            i.Trainer_ID,
            i.Item_Name,
            i.Item_Category,
            MAX(e.Completion_Date) as Completion_Date,
            t.Trainer_Name,
            COUNT(e.Participant_ID) as participant_count
        FROM Item i
        LEFT JOIN Trainer t ON i.Trainer_ID = t.Trainer_ID
        LEFT JOIN Enrollment e ON i.Item_ID = e.Item_ID
        WHERE i.TP_ID = ?
        GROUP BY
            i.Item_ID,
            i.TP_ID,
            i.Trainer_ID,
            i.Item_Name,
            i.Item_Category,
            t.Trainer_Name
        ORDER BY Completion_Date DESC, i.Item_Name ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tp_id]);
    $provider['courses'] = normalizeAssocRows($stmt->fetchAll(PDO::FETCH_ASSOC));

    $statusMap = getLatestProviderStatusMap();
    $expertiseMap = getProviderExpertiseMap();

    $status = $statusMap[(int)$tp_id] ?? ['TP_Status' => '', 'TP_StatusReasoning' => null];
    $provider['TP_Status'] = $status['TP_Status'];
    $provider['TP_StatusReasoning'] = $status['TP_StatusReasoning'];
    $provider['TP_StatusEndDate'] = $status['TP_StatusEndDate'] ?? null;
    $provider['blacklistReason'] = ($provider['TP_Status'] === 'Blacklisted') ? ($status['TP_StatusReasoning'] ?? null) : null;
    $provider['status_history'] = getTrainingProviderStatusHistory($tp_id);
    $provider['remarks'] = getTrainingProviderRemarks($tp_id);

    // Prioritize manually set expertise over calculated expertise from courses
    $first = null;
    $second = null;

    // If manually set, use that; otherwise use calculated from courses
    if (!empty($provider['TP_FirstAoE'])) {
        $first = [
            'name' => $provider['TP_FirstAoE'],
            'percent' => null,
            'display' => $provider['TP_FirstAoE']
        ];
    } else {
        $first = $expertiseMap[(int)$tp_id]['first'] ?? null;
    }

    if (!empty($provider['TP_SecondAoE'])) {
        $second = [
            'name' => $provider['TP_SecondAoE'],
            'percent' => null,
            'display' => $provider['TP_SecondAoE']
        ];
    } else {
        $second = $expertiseMap[(int)$tp_id]['second'] ?? null;
    }

    $provider['TP_FirstAoE'] = $first['name'] ?? null;
    $provider['TP_SecondAoE'] = $second['name'] ?? null;
    $provider['TP_FirstAoEPercent'] = $first['percent'] ?? null;
    $provider['TP_SecondAoEPercent'] = $second['percent'] ?? null;
    $provider['TP_FirstAoEDisplay'] = $first['display'] ?? null;
    $provider['TP_SecondAoEDisplay'] = $second['display'] ?? null;
    
    return $provider;
}

/**
 * Get all trainers
 */
function getTrainers() {
    global $pdo;

    // Base trainer data + aggregate counts.
    $sql = "
        SELECT 
            t.Trainer_ID,
            t.Trainer_Name,
            t.Trainer_Status,
            COALESCE(provider_counts.provider_count, 0) AS provider_count,
            COALESCE(course_counts.course_count, 0) AS course_count
        FROM Trainer t
        LEFT JOIN (
            SELECT a.Trainer_ID, COUNT(DISTINCT a.TP_ID) AS provider_count
            FROM Assignment a
            GROUP BY a.Trainer_ID
        ) provider_counts ON provider_counts.Trainer_ID = t.Trainer_ID
        LEFT JOIN (
            SELECT i.Trainer_ID, COUNT(*) AS course_count
            FROM Item i
            GROUP BY i.Trainer_ID
        ) course_counts ON course_counts.Trainer_ID = t.Trainer_ID
        ORDER BY t.Trainer_Name
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $trainers = normalizeAssocRows($stmt->fetchAll(PDO::FETCH_ASSOC));

    $trainerMap = [];
    foreach ($trainers as $trainer) {
        $trainer['providers'] = [];
        $trainerMap[$trainer['Trainer_ID']] = $trainer;
    }

    $trainerStatusMap = getLatestTrainerStatusMap();
    foreach ($trainerMap as $trainerId => $trainer) {
        $status = $trainerStatusMap[$trainerId] ?? null;
        $trainerMap[$trainerId]['Trainer_Status'] = $status['Trainer_Status'] ?? trim((string)($trainerMap[$trainerId]['Trainer_Status'] ?? ''));
        $trainerMap[$trainerId]['Trainer_StatusReasoning'] = $status['Trainer_StatusReasoning'] ?? null;
        $trainerMap[$trainerId]['Trainer_StatusStartDate'] = $status['Trainer_StatusStartDate'] ?? null;
        $trainerMap[$trainerId]['Trainer_StatusEndDate'] = $status['Trainer_StatusEndDate'] ?? null;
        $trainerMap[$trainerId]['Trainer_StatusActive'] = !empty($status) && !empty($status['Trainer_StatusActive']);
    }

    if (!empty($trainerMap)) {
        $statusMap = getLatestProviderStatusMap();

        $providerSql = "
            SELECT 
                a.Trainer_ID,
                tp.TP_ID,
                tp.TP_Name
            FROM Assignment a
            INNER JOIN TrainingProvider tp ON a.TP_ID = tp.TP_ID
            ORDER BY a.Trainer_ID, tp.TP_Name
        ";
        $providerStmt = $pdo->prepare($providerSql);
        $providerStmt->execute();
        $providerRows = normalizeAssocRows($providerStmt->fetchAll(PDO::FETCH_ASSOC));

        foreach ($providerRows as $row) {
            $trainerId = $row['Trainer_ID'];
            if (!isset($trainerMap[$trainerId])) {
                continue;
            }
            $trainerMap[$trainerId]['providers'][] = [
                'TP_ID' => $row['TP_ID'],
                'TP_Name' => $row['TP_Name'],
                'TP_Status' => $statusMap[$row['TP_ID']]['TP_Status'] ?? ''
            ];
        }
    }

    return array_values($trainerMap);
}

/**
 * Get single trainer with taught courses and associated providers.
 */
function getTrainerDetail($trainer_id) {
    global $pdo;

    $sql = "SELECT * FROM Trainer WHERE Trainer_ID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$trainer_id]);
    $trainer = normalizeAssocRow($stmt->fetch(PDO::FETCH_ASSOC));

    if (!$trainer) {
        return null;
    }

    $statusMap = getLatestProviderStatusMap();
    $trainerStatusMap = getLatestTrainerStatusMap();

    $providerSql = "
        SELECT
            tp.TP_ID,
            tp.TP_Name
        FROM Assignment a
        INNER JOIN TrainingProvider tp ON a.TP_ID = tp.TP_ID
        WHERE a.Trainer_ID = ?
        ORDER BY tp.TP_Name
    ";
    $stmt = $pdo->prepare($providerSql);
    $stmt->execute([$trainer_id]);
    $trainer['providers'] = array_map(function ($row) use ($statusMap) {
        $row['TP_Status'] = $statusMap[$row['TP_ID']]['TP_Status'] ?? '';
        return $row;
    }, normalizeAssocRows($stmt->fetchAll(PDO::FETCH_ASSOC)));

    $courseSql = "
        SELECT
            i.Item_ID,
            i.Item_Name,
            i.Item_Category,
            MAX(e.Completion_Date) as Completion_Date,
            i.TP_ID,
            tp.TP_Name,
            COUNT(DISTINCT e.Participant_ID) as participant_count
        FROM Item i
        INNER JOIN TrainingProvider tp ON i.TP_ID = tp.TP_ID
        LEFT JOIN Enrollment e ON i.Item_ID = e.Item_ID
        WHERE i.Trainer_ID = ?
        GROUP BY
            i.Item_ID,
            i.Item_Name,
            i.Item_Category,
            i.TP_ID,
            tp.TP_Name
        ORDER BY Completion_Date DESC, i.Item_Name ASC
    ";
    $stmt = $pdo->prepare($courseSql);
    $stmt->execute([$trainer_id]);
    $trainer['courses'] = array_map(function ($row) use ($statusMap) {
        $row['TP_Status'] = $statusMap[$row['TP_ID']]['TP_Status'] ?? '';
        return $row;
    }, normalizeAssocRows($stmt->fetchAll(PDO::FETCH_ASSOC)));

    $trainerStatus = $trainerStatusMap[(int)$trainer_id] ?? null;
    $trainer['Trainer_Status'] = $trainerStatus['Trainer_Status'] ?? trim((string)($trainer['Trainer_Status'] ?? ''));
    $trainer['Trainer_StatusReasoning'] = $trainerStatus['Trainer_StatusReasoning'] ?? null;
    $trainer['Trainer_StatusStartDate'] = $trainerStatus['Trainer_StatusStartDate'] ?? null;
    $trainer['Trainer_StatusEndDate'] = $trainerStatus['Trainer_StatusEndDate'] ?? null;
    $trainer['Trainer_StatusActive'] = !empty($trainerStatus) && !empty($trainerStatus['Trainer_StatusActive']);
    $trainer['status_history'] = getTrainerStatusHistory($trainer_id);
    $trainer['remarks'] = getTrainerRemarks($trainer_id);

    return $trainer;
}

/**
 * Get statistics
 */
function getStatistics() {
    global $pdo;
    
    $stats = [];
    
    // Total training providers
    $sql = "SELECT COUNT(*) as count FROM TrainingProvider";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $stats['total_providers'] = normalizeAssocRow($stmt->fetch())['COUNT'] ?? 0;
    
    // Providers by latest status
    $statusMap = getLatestProviderStatusMap();
    $stats['providers_active'] = 0;
    $stats['providers_greylist'] = 0;
    $stats['providers_blacklisted'] = 0;

    foreach ($statusMap as $status) {
        $label = $status['TP_Status'] ?? '';
        if ($label === 'Active') {
            $stats['providers_active']++;
        } elseif ($label === 'Greylist') {
            $stats['providers_greylist']++;
        } elseif ($label === 'Blacklisted') {
            $stats['providers_blacklisted']++;
        }
    }

    $stats['providers_in_consideration'] = $stats['providers_greylist'];
    
    // Total trainers
    $sql = "SELECT COUNT(*) as count FROM Trainer";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $stats['total_trainers'] = normalizeAssocRow($stmt->fetch())['COUNT'] ?? 0;
    
    // Total courses
    $sql = "SELECT COUNT(*) as count FROM Item";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $stats['total_courses'] = normalizeAssocRow($stmt->fetch())['COUNT'] ?? 0;
    
    // Total participants
    $sql = "SELECT COUNT(DISTINCT Participant_ID) as count FROM Enrollment";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $stats['total_participants'] = normalizeAssocRow($stmt->fetch())['COUNT'] ?? 0;
    
    return $stats;
}

/**
 * Update training provider status
 */
function updateProviderStatus($tp_id, $status, $reason = null) {
    global $pdo;

    $status = normalizeProviderStatusLabel($status);

    $sql = "
        INSERT INTO TrainingProviderStatus
            (TP_ID, TP_Status, TP_StatusReasoning, TP_StatusStartDate)
        VALUES
            (?, ?, ?, CURRENT_DATE)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tp_id, $status, $reason]);
    
    return $stmt->rowCount() > 0;
}

/**
 * Update trainer red flag status
 */
function updateTrainerRedFlag($trainer_id, $is_red_flag, $reason = null) {
    global $pdo;

    $pdo->beginTransaction();

    try {
        if ($is_red_flag) {
            $closeStmt = $pdo->prepare(
                "UPDATE TrainerStatus
                 SET Trainer_StatusEndDate = CURRENT_DATE
                 WHERE Trainer_ID = ?
                   AND Trainer_StatusEndDate IS NULL"
            );
            $closeStmt->execute([$trainer_id]);

            $insertStmt = $pdo->prepare(
                'INSERT INTO TrainerStatus (Trainer_ID, Trainer_Status, Trainer_StatusReasoning, Trainer_StatusStartDate, Trainer_StatusEndDate) VALUES (?, ?, ?, CURRENT_DATE, NULL)'
            );
            $insertStmt->execute([$trainer_id, 'Red Flag', $reason]);
        } else {
            $closeStmt = $pdo->prepare(
                "UPDATE TrainerStatus
                 SET Trainer_StatusEndDate = CURRENT_DATE
                 WHERE Trainer_ID = ?
                   AND Trainer_StatusEndDate IS NULL"
            );
            $closeStmt->execute([$trainer_id]);
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
?>
