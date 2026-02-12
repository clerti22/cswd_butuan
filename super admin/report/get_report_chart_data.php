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

$barangayParts = [];
foreach ($tables as $table) {
    $barangayParts[] = "SELECT TRIM(barangay) AS barangay FROM {$table} WHERE barangay IS NOT NULL AND TRIM(barangay) <> ''";
}

$barangaySql = 'SELECT barangay, COUNT(*) AS total FROM (' . implode(' UNION ALL ', $barangayParts) . ') AS combined_barangays GROUP BY barangay';
$barangayResult = $connection->query($barangaySql);

if (!$barangayResult) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load barangay chart data: ' . $connection->error
    ]);
    $connection->close();
    exit;
}

$barangayCounts = array_fill_keys($barangays, 0);
while ($row = $barangayResult->fetch_assoc()) {
    $name = $row['barangay'];
    if (array_key_exists($name, $barangayCounts)) {
        $barangayCounts[$name] = (int) $row['total'];
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
foreach ($programMap as $program => $table) {
    $result = $connection->query("SELECT COUNT(*) AS total FROM {$table}");
    if (!$result) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to load program chart data: ' . $connection->error
        ]);
        $connection->close();
        exit;
    }

    $row = $result->fetch_assoc();
    $programCounts[$program] = (int) ($row['total'] ?? 0);
}

$genderTotals = ['Male' => 0, 'Female' => 0];

foreach ($tables as $table) {
    $genderSql = "SELECT
        SUM(CASE WHEN LOWER(TRIM(sex)) IN ('male', 'm') THEN 1 ELSE 0 END) AS male_count,
        SUM(CASE WHEN LOWER(TRIM(sex)) IN ('female', 'f') THEN 1 ELSE 0 END) AS female_count
    FROM {$table}";

    $result = $connection->query($genderSql);

    if (!$result) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to load gender chart data: ' . $connection->error
        ]);
        $connection->close();
        exit;
    }

    $row = $result->fetch_assoc();
    $genderTotals['Male'] += (int) ($row['male_count'] ?? 0);
    $genderTotals['Female'] += (int) ($row['female_count'] ?? 0);
}

$connection->close();

echo json_encode([
    'success' => true,
    'data' => [
        'barangay' => [
            'labels' => $barangays,
            'values' => array_values($barangayCounts)
        ],
        'program' => [
            'labels' => array_keys($programCounts),
            'values' => array_values($programCounts)
        ],
        'gender' => [
            'labels' => array_keys($genderTotals),
            'values' => array_values($genderTotals)
        ]
    ]
]);
