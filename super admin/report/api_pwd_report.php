<?php
declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/db.php';

require_login(['super admin']);
header('Content-Type: application/json; charset=utf-8');

try {
    $connection = get_database_connection();
    
    // Fetch all PWD-2 records
    $query = "SELECT key_id, control_number, first_name, middle_name, last_name, suffix, sex, age, barangay, disability_types_primary, date_issued, birth_date, created_at FROM pwd_table2";
    $result = $connection->query($query);
    
    $allRecords = [];
    $barangays = [];
    $years = [];
    
    while ($row = $result->fetch_assoc()) {
        $row['full_name'] = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'] . ' ' . $row['suffix']);
        $row['disability_types_primary'] = $row['disability_types_primary'] ?: 'Unspecified';
        
        $brgy = trim((string)$row['barangay']);
        if ($brgy !== '' && !in_array($brgy, $barangays, true)) {
            $barangays[] = $brgy;
        }
        
        $year = $row['date_issued'] ? substr($row['date_issued'], 0, 4) : substr($row['created_at'], 0, 4);
        if ($year && !in_array($year, $years, true)) {
            $years[] = $year;
        }
        
        $allRecords[] = $row;
    }
    
    sort($barangays);
    rsort($years);

    $filters = [
        'disability' => trim((string) ($_GET['disability'] ?? '')),
        'barangay' => trim((string) ($_GET['barangay'] ?? '')),
        'sex' => trim((string) ($_GET['sex'] ?? '')),
        'year' => trim((string) ($_GET['year'] ?? '')),
        'month' => trim((string) ($_GET['month'] ?? '')),
        'date_from' => trim((string) ($_GET['date_from'] ?? '')),
        'date_to' => trim((string) ($_GET['date_to'] ?? '')),
    ];

    $filtered = array_values(array_filter($allRecords, static function ($row) use ($filters) {
        if ($filters['disability'] !== '' && stripos((string)$row['disability_types_primary'], $filters['disability']) === false) {
            return false;
        }
        if ($filters['barangay'] !== '' && strcasecmp((string)$row['barangay'], $filters['barangay']) !== 0) {
            return false;
        }
        if ($filters['sex'] !== '' && strcasecmp((string)$row['sex'], $filters['sex']) !== 0) {
            return false;
        }
        
        $recordDate = $row['date_issued'] ?: substr($row['created_at'], 0, 10);
        $recordYear = substr($recordDate, 0, 4);
        $recordMonth = substr($recordDate, 5, 2);
        
        if ($filters['year'] !== '' && $recordYear !== $filters['year']) {
            return false;
        }
        if ($filters['month'] !== '' && $recordMonth !== $filters['month']) {
            return false;
        }
        if ($filters['date_from'] !== '' && $recordDate < $filters['date_from']) {
            return false;
        }
        if ($filters['date_to'] !== '' && $recordDate > $filters['date_to']) {
            return false;
        }
        
        return true;
    }));

    $search = trim((string) ($_GET['search'] ?? ''));
    if ($search !== '') {
        $filtered = array_values(array_filter($filtered, static function ($row) use ($search) {
            $needle = function_exists('mb_strtolower') ? mb_strtolower($search, 'UTF-8') : strtolower($search);
            $haystacks = [
                (string) $row['control_number'],
                (string) $row['full_name'],
                (string) $row['barangay'],
                (string) $row['disability_types_primary']
            ];
            foreach ($haystacks as $haystack) {
                $haystackLower = function_exists('mb_strtolower') ? mb_strtolower($haystack, 'UTF-8') : strtolower($haystack);
                if ($haystackLower !== '' && strpos($haystackLower, $needle) !== false) {
                    return true;
                }
            }
            return false;
        }));
    }

    $sortBy = $_GET['sort_by'] ?? 'control_number';
    $sortOrder = strtolower($_GET['sort_order'] ?? 'asc');

    usort($filtered, static function ($a, $b) use ($sortBy, $sortOrder) {
        $valA = $a[$sortBy] ?? '';
        $valB = $b[$sortBy] ?? '';
        $cmp = is_numeric($valA) && is_numeric($valB) ? $valA <=> $valB : strnatcasecmp((string)$valA, (string)$valB);
        return $sortOrder === 'desc' ? -$cmp : $cmp;
    });
    
    // Aggregations for charts
    $chartDisability = [];
    $chartBarangay = [];
    $chartYear = [];
    $chartMonth = [];
    $chartSex = [];
    
    $monthNames = ['01'=>'January','02'=>'February','03'=>'March','04'=>'April','05'=>'May','06'=>'June','07'=>'July','08'=>'August','09'=>'September','10'=>'October','11'=>'November','12'=>'December'];
    
    foreach ($filtered as $row) {
        $disabilities = array_map('trim', explode(',', (string)$row['disability_types_primary']));
        foreach ($disabilities as $d) {
            if ($d === '') continue;
            if ($filters['disability'] !== '' && strcasecmp($d, $filters['disability']) !== 0) {
                continue;
            }
            $chartDisability[$d] = ($chartDisability[$d] ?? 0) + 1;
        }
        
        $brgy = trim((string)$row['barangay']) ?: 'Unknown';
        $chartBarangay[$brgy] = ($chartBarangay[$brgy] ?? 0) + 1;
        
        $recordDate = $row['date_issued'] ?: substr($row['created_at'], 0, 10);
        $recordYear = substr($recordDate, 0, 4) ?: 'Unknown';
        $recordMonthNum = substr($recordDate, 5, 2);
        $recordMonth = $monthNames[$recordMonthNum] ?? 'Unknown';
        
        $chartYear[$recordYear] = ($chartYear[$recordYear] ?? 0) + 1;
        $chartMonth[$recordMonth] = ($chartMonth[$recordMonth] ?? 0) + 1;
        
        $sex = trim((string)$row['sex']) ?: 'Unspecified';
        $chartSex[$sex] = ($chartSex[$sex] ?? 0) + 1;
    }
    
    $formatChart = function($arr) {
        $res = [];
        foreach($arr as $k => $v) $res[] = ['label' => (string)$k, 'y' => $v];
        usort($res, fn($a, $b) => $b['y'] <=> $a['y']); // Sort desc by default
        return $res;
    };
    
    $formatChartMonth = function($arr) use ($monthNames) {
        $res = [];
        foreach($monthNames as $num => $name) {
            if (isset($arr[$name])) $res[] = ['label' => $name, 'y' => $arr[$name]];
        }
        return $res;
    };

    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, (int)($_GET['limit'] ?? 10));
    $totalRecords = count($filtered);
    $totalPages = (int) ceil($totalRecords / $limit);
    $offset = ($page - 1) * $limit;
    $pagedRecords = array_slice($filtered, $offset, $limit);

    echo json_encode([
        'data' => $pagedRecords,
        'pagination' => [
            'total' => $totalRecords,
            'pages' => $totalPages,
            'current' => $page,
            'limit' => $limit
        ],
        'charts' => [
            'disability' => $formatChart($chartDisability),
            'barangay' => $formatChart($chartBarangay),
            'year' => $formatChart($chartYear),
            'month' => $formatChartMonth($chartMonth),
            'sex' => $formatChart($chartSex)
        ],
        'totals' => [
            ['title' => 'Total Clients', 'value' => count($filtered)],
            ['title' => 'Total Disability Types', 'value' => count($chartDisability)],
            ['title' => 'Total Male', 'value' => $chartSex['Male'] ?? 0],
            ['title' => 'Total Female', 'value' => $chartSex['Female'] ?? 0]
        ],
        'options' => [
            'barangays' => $barangays,
            'years' => $years
        ]
    ], JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error', 'message' => $e->getMessage()]);
}

