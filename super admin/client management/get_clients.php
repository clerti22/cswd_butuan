<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/db.php';

require_login(['super admin']);

header('Content-Type: application/json; charset=utf-8');

$tab = strtolower(trim((string) ($_GET['tab'] ?? '')));
$tablesByTab = [
    'dpsd' => ['pwud_table', 'pyap_table', 'soloparent_info_table'],
    'pssd' => ['pwd_table', 'snr_table'],
    'rpsd' => ['car_table', 'cicl_table', 'ciu_table', 'csae_table', 'vawc_table'],
];

if (!isset($tablesByTab[$tab])) {
    http_response_code(400);
    echo json_encode(['clients' => [], 'message' => 'Invalid tab.']);
    exit;
}

$requiredColumns = [
    'control_number',
    'first_name',
    'middle_name',
    'last_name',
    'suffix',
    'age',
    'sex',
    'barangay',
    'program',
    'division',
    'contact_number',
    'created_at',
];

try {
    $connection = get_database_connection();
    $clients = [];

    foreach ($tablesByTab[$tab] as $tableName) {
        $columnsResult = $connection->query('SHOW COLUMNS FROM `' . $connection->real_escape_string($tableName) . '`');
        if (!$columnsResult) {
            throw new RuntimeException('Could not inspect table columns.');
        }

        $availableColumns = [];
        while ($column = $columnsResult->fetch_assoc()) {
            $fieldName = (string) ($column['Field'] ?? '');
            if ($fieldName !== '') {
                $availableColumns[$fieldName] = true;
            }
        }

        foreach ($requiredColumns as $requiredColumn) {
            if (!isset($availableColumns[$requiredColumn])) {
                throw new RuntimeException('Missing required column: ' . $requiredColumn);
            }
        }

        $query = "SELECT control_number, first_name, middle_name, last_name, suffix, age, sex, barangay, program, division, contact_number, created_at FROM `{$tableName}` ORDER BY created_at DESC";
        $result = $connection->query($query);

        if (!$result) {
            throw new RuntimeException('Could not fetch client records.');
        }

        while ($row = $result->fetch_assoc()) {
            $fullNameParts = [
                trim((string) ($row['first_name'] ?? '')),
                trim((string) ($row['middle_name'] ?? '')),
                trim((string) ($row['last_name'] ?? '')),
                trim((string) ($row['suffix'] ?? '')),
            ];

            $fullName = trim(implode(' ', array_values(array_filter($fullNameParts, static fn(string $part): bool => $part !== ''))));

            $createdAtRaw = (string) ($row['created_at'] ?? '');
            $createdAt = '';
            $timestamp = 0;
            if ($createdAtRaw !== '') {
                $timestamp = strtotime($createdAtRaw);
                if ($timestamp !== false) {
                    $createdAt = date('m/d/y', $timestamp);
                }
            }

            $clients[] = [
                'control_number' => (string) ($row['control_number'] ?? ''),
                'full_name' => $fullName,
                'age' => (string) ($row['age'] ?? ''),
                'sex' => (string) ($row['sex'] ?? ''),
                'barangay' => (string) ($row['barangay'] ?? ''),
                'program' => (string) ($row['program'] ?? ''),
                'division' => (string) ($row['division'] ?? ''),
                'contact_number' => (string) ($row['contact_number'] ?? ''),
                'created_at' => $createdAt,
                'created_at_sort' => (int) ($timestamp ?: 0),
                'source_table' => $tableName,
            ];
        }
    }

    usort(
        $clients,
        static function (array $first, array $second): int {
            return ((int) ($second['created_at_sort'] ?? 0)) <=> ((int) ($first['created_at_sort'] ?? 0));
        }
    );

    $connection->close();

    foreach ($clients as &$client) {
        unset($client['created_at_sort']);
    }
    unset($client);

    echo json_encode(['clients' => $clients]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['clients' => [], 'message' => 'Unable to fetch clients.']);
}
