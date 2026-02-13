<?php
header('Content-Type: application/json');

$tables = [
    'pwud_table',
    'soloparent_info_table',
    'pyap_table',
    'snr_table',
    'pwd_table',
    'car_table',
    'ciu_table',
    'vawc_table',
    'csae_table',
    'cicl_table'
];

$barangays = [
    'Maon', 'Golden Ribbon', 'Agao', 'Rajah Soliman', 'Silongan', 'Diego Silang', 'Dagohoy', 'Lapu-lapu',
    'Urduja', 'Sikatuna', 'Humabon', 'Tandang Sora', 'Leon Kilat', 'Limaha', 'San Ignacio', 'Ong Yiu',
    'Fort Poyohon', 'Obrero', 'Buhangin', 'Baan Riverside', 'Mahogany', 'Bading', 'Holy Redeemer', 'Imadejas',
    'Jose P. Rizal', 'New Society Village', 'Bayanihan', 'Agusan Pequeño', 'Ambago', 'Amparo', 'Ampayon',
    'Anticala', 'Antongalon', 'Aupagan', 'Baan Km.3', 'Babag', 'Bancasi', 'Banza', 'Baobaoan', 'Basag',
    'Bilay', 'Bitan-agan', 'Bit-os', 'Bobon', 'Bonbon', 'Bugsukan', 'Cabcabon', 'Camayahan', 'Dankias',
    'De Oro', 'Don Francisco', 'Doongan', 'Dulag', 'Dumalagan', 'Florida', 'Kinamlutan', 'Lemon', 'Libertad',
    'Los Angeles', 'Lumbocan', 'Maguinda', 'Mahay', 'Maibu', 'Mandamo', 'Manila de Bugabus', 'Manuel J. Santos',
    'Masao', 'Maug', 'Nongnong', 'Pagatpatan', 'Pangabugan', 'Pianing', 'Pigdaulan', 'Pinamanculan', 'Salvacion',
    'San Mateo', 'San Vicente', 'Santo Niño', 'Sumile', 'Sumilihon', 'Tagabaca', 'Taguibo', 'Taligaman',
    'Tiniwisan', 'Tungao', 'Villa Kananga'
];

$connection = new mysqli('localhost', 'root', '', 'cswd_db');

if ($connection->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $connection->connect_error
    ]);
    exit;
}

function normalizeValue(string $value): string
{
    return strtolower(trim(preg_replace('/\s+/', ' ', $value)));
}

$barangayLookup = [];
foreach ($barangays as $barangay) {
    $barangayLookup[normalizeValue($barangay)] = $barangay;
}

$barangayCounts = array_fill_keys($barangays, 0);

$barangayQueryParts = [];
foreach ($tables as $table) {
    $barangayQueryParts[] = "SELECT barangay FROM {$table} WHERE barangay IS NOT NULL AND TRIM(barangay) <> ''";
}

$barangaySql = 'SELECT barangay, COUNT(*) AS total FROM (' . implode(' UNION ALL ', $barangayQueryParts) . ') AS all_barangays GROUP BY barangay';
$barangayResult = $connection->query($barangaySql);

if (!$barangayResult) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load barangay data: ' . $connection->error
    ]);
    $connection->close();
    exit;
}

while ($row = $barangayResult->fetch_assoc()) {
    $normalized = normalizeValue($row['barangay']);
    if (isset($barangayLookup[$normalized])) {
        $matchedName = $barangayLookup[$normalized];
        $barangayCounts[$matchedName] += (int) $row['total'];
    }
}

$programMap = [
    'PWUD' => 'pwud_table',
    'PYAP' => 'pyap_table',
    'SOLO PARENT' => 'soloparent_info_table',
    'SENIOR CITIZEN' => 'snr_table',
    'PWD' => 'pwd_table',
    'VAWC' => 'vawc_table',
    'CIU' => 'ciu_table',
    'CAR' => 'car_table',
    'CICL' => 'cicl_table',
    'CSAE' => 'csae_table'
];

$programCounts = [];
foreach ($programMap as $label => $table) {
    $programResult = $connection->query("SELECT COUNT(*) AS total FROM {$table}");

    if (!$programResult) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to load program data: ' . $connection->error
        ]);
        $connection->close();
        exit;
    }

    $programRow = $programResult->fetch_assoc();
    $programCounts[$label] = (int) ($programRow['total'] ?? 0);
}

$genderCounts = ['Male' => 0, 'Female' => 0];
foreach ($tables as $table) {
    $genderSql = "SELECT
        SUM(CASE WHEN LOWER(TRIM(sex)) IN ('male', 'm') THEN 1 ELSE 0 END) AS male_total,
        SUM(CASE WHEN LOWER(TRIM(sex)) IN ('female', 'f') THEN 1 ELSE 0 END) AS female_total
    FROM {$table}";

    $genderResult = $connection->query($genderSql);

    if (!$genderResult) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to load gender data: ' . $connection->error
        ]);
        $connection->close();
        exit;
    }

    $genderRow = $genderResult->fetch_assoc();
    $genderCounts['Male'] += (int) ($genderRow['male_total'] ?? 0);
    $genderCounts['Female'] += (int) ($genderRow['female_total'] ?? 0);
}

$connection->close();

echo json_encode([
    'success' => true,
    'data' => [
        'barangay' => [
            'labels' => array_keys($barangayCounts),
            'values' => array_values($barangayCounts)
        ],
        'program' => [
            'labels' => array_keys($programCounts),
            'values' => array_values($programCounts)
        ],
        'gender' => [
            'labels' => array_keys($genderCounts),
            'values' => array_values($genderCounts)
        ]
    ]
]);
