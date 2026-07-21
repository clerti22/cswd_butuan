<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_login(['super admin']);

header('Content-Type: application/json');

$programTables = [
    'PWUD' => 'pwud_table',
    'PYAP' => 'pyap_table',
    'SOLO PARENT' => 'soloparent_info_table',
    'SENIOR CITIZEN' => 'snr_table',
    'PWD' => 'pwd_table',
        'PWD-2' => 'pwd_table2',
    'VAWC' => 'vawc_table',
    'CIU' => 'ciu_table',
    'CAR' => 'car_table',
    'CICL' => 'cicl_table',
    'CSAE' => 'csae_table'
];

$allBarangays = [
    'Maon', 'Golden Ribbon', 'Agao', 'Rajah Soliman', 'Silongan', 'Diego Silang', 'Dagohoy', 'Lapu-lapu',
    'Urduja', 'Sikatuna', 'Humabon', 'Tandang Sora', 'Leon Kilat', 'Limaha', 'San Ignacio', 'Ong Yiu',
    'Fort Poyohon', 'Obrero', 'Buhangin', 'Baan Riverside', 'Mahogany', 'Bading', 'Holy Redeemer', 'Imadejas',
    'Jose P. Rizal', 'New Society Village', 'Bayanihan', 'Agusan Pequeño', 'Ambago', 'Amparo', 'Ampayon',
    'Anticala', 'Antongalon', 'Aupagan', 'Baan Km.3', 'Babag', 'Bancasi', 'Banza', 'Baobaoan', 'Basag',
    'Bilay', 'Bitan-agan', 'Bit-os', 'Bobon', 'Bonbon', 'Bugsukan', 'Cabcabon', 'Camayahan', 'Dankias',
    'De Oro', 'Don Francisco', 'Doongan', 'Dulag', 'Dumalagan', 'Florida', 'Kinamlutan', 'Lemon', 'Libertad',
    'Los Angeles', 'Lumbocan', 'Maguinda', 'Mahay', 'Maibu', 'Mandamo', 'Manila de Bugabus', 'Manuel J. Santos',
    'Masao', 'Maug', 'Nongnong', 'Pagatpatan', 'Pangabugan', 'Pianing', 'Pigdaulan', 'Pinamanculan', 'Salvacion',
    'San Mateo', 'San Vicente', 'Santo Niño', 'Sumile', 'Sumilihon', 'Tagabaca', 'Taguibo', 'Taligaman', 'Tiniwisan',
    'Tungao', 'Villa Kananga'
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

function getTableColumns(mysqli $connection, string $table): array
{
    $result = $connection->query("SHOW COLUMNS FROM {$table}");
    if (!$result) {
        return [];
    }

    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = strtolower((string) $row['Field']);
    }

    return $columns;
}

function resolveColumn(array $columns, array $preferred): ?string
{
    foreach ($preferred as $column) {
        if (in_array(strtolower($column), $columns, true)) {
            return $column;
        }
    }

    return null;
}

function normalizeName(string $value): string
{
    $trimmed = trim($value);
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($trimmed);
    }

    return strtolower($trimmed);
}

$normalizedBarangayCounts = array_fill_keys(array_map('normalizeName', $allBarangays), 0);
$programCounts = [];
$genderCounts = ['Male' => 0, 'Female' => 0];

foreach ($programTables as $program => $table) {
    $columns = getTableColumns($connection, $table);

    $countResult = $connection->query("SELECT COUNT(*) AS total FROM {$table}");
    if (!$countResult) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => "Failed to count records for {$table}: " . $connection->error
        ]);
        $connection->close();
        exit;
    }
    $programCounts[$program] = (int) ($countResult->fetch_assoc()['total'] ?? 0);

    $barangayColumn = resolveColumn($columns, ['barangay']);
    if ($barangayColumn !== null) {
        $barangayResult = $connection->query("SELECT {$barangayColumn} AS barangay_name, COUNT(*) AS total FROM {$table} GROUP BY {$barangayColumn}");

        if ($barangayResult) {
            while ($row = $barangayResult->fetch_assoc()) {
                $barangayName = trim((string) ($row['barangay_name'] ?? ''));
                if ($barangayName === '') {
                    continue;
                }

                $normalizedName = normalizeName($barangayName);
                if (array_key_exists($normalizedName, $normalizedBarangayCounts)) {
                    $normalizedBarangayCounts[$normalizedName] += (int) $row['total'];
                }
            }
        }
    }

    $sexColumn = resolveColumn($columns, ['sex', 'gender']);
    if ($sexColumn !== null) {
        $genderResult = $connection->query("SELECT {$sexColumn} AS gender_name, COUNT(*) AS total FROM {$table} GROUP BY {$sexColumn}");

        if ($genderResult) {
            while ($row = $genderResult->fetch_assoc()) {
                $rawGender = strtolower(trim((string) ($row['gender_name'] ?? '')));
                $count = (int) ($row['total'] ?? 0);

                if (in_array($rawGender, ['male', 'm'], true)) {
                    $genderCounts['Male'] += $count;
                } elseif (in_array($rawGender, ['female', 'f'], true)) {
                    $genderCounts['Female'] += $count;
                }
            }
        }
    }
}

$barangayCounts = [];
foreach ($allBarangays as $barangay) {
    $barangayCounts[] = [
        'label' => $barangay,
        'count' => $normalizedBarangayCounts[normalizeName($barangay)] ?? 0
    ];
}

echo json_encode([
    'success' => true,
    'data' => [
        'barangay' => $barangayCounts,
        'program' => $programCounts,
        'gender' => $genderCounts
    ]
]);

$connection->close();
