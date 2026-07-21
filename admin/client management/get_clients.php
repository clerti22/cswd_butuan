<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/scan_copy_helpers.php';
require_once __DIR__ . '/../division_scope.php';

require_login(['admin']);

header('Content-Type: application/json; charset=utf-8');

function normalize_search_keyword(string $value): string
{
    $trimmedValue = trim($value);

    if ($trimmedValue === '') {
        return '';
    }

    return function_exists('mb_strtolower')
        ? mb_strtolower($trimmedValue, 'UTF-8')
        : strtolower($trimmedValue);
}

function escape_like_pattern(string $value): string
{
    return str_replace(
        ['\\', '%', '_'],
        ['\\\\', '\\%', '\\_'],
        $value
    );
}

function normalize_filter_value(string $value): string
{
    $trimmedValue = trim($value);

    if ($trimmedValue === '') {
        return '';
    }

    return function_exists('mb_strtolower')
        ? mb_strtolower($trimmedValue, 'UTF-8')
        : strtolower($trimmedValue);
}

function extract_age_value($age): ?int
{
    $ageString = trim((string) $age);
    if ($ageString === '') {
        return null;
    }

    if (!preg_match('/\d+/', $ageString, $matches)) {
        return null;
    }

    return (int) $matches[0];
}

function resolve_age_bracket($age): string
{
    $ageValue = extract_age_value($age);

    if ($ageValue === null || $ageValue < 0) {
        return '';
    }

    if ($ageValue <= 12) {
        return '0-12';
    }
    if ($ageValue <= 17) {
        return '13-17';
    }
    if ($ageValue <= 30) {
        return '18-30';
    }
    if ($ageValue <= 59) {
        return '31-59';
    }

    return '60+';
}

$division = strtolower(admin_scope_division());
$tab = $division;
$tablesByTab = [
    'dpsd' => ['pwud_table', 'pyap_table', 'soloparent_info_table'],
    'pssd' => ['pwd_table', 'pwd_table2', 'snr_table'],
    'rpsd' => ['car_table', 'cicl_table', 'ciu_table', 'csae_table', 'vawc_table'],
    'cod' => ['faced_table'],
];

if (!isset($tablesByTab[$tab])) {
    http_response_code(400);
    echo json_encode(['clients' => [], 'message' => 'Invalid division scope.']);
    exit;
}

$page = (int) ($_GET['page'] ?? 1);
if ($page < 1) {
    $page = 1;
}
$perPage = 10;
$searchKeyword = normalize_search_keyword((string) ($_GET['search'] ?? ''));
$searchPattern = '%' . escape_like_pattern($searchKeyword) . '%';
$programFilter = trim((string) ($_GET['program'] ?? ''));
$barangayFilter = trim((string) ($_GET['barangay'] ?? ''));
$ageBracketFilter = trim((string) ($_GET['age_bracket'] ?? ''));
$sexFilter = trim((string) ($_GET['sex'] ?? ''));

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
    ensure_client_scans_table($connection);
    $allClients = [];

    foreach ($tablesByTab[$tab] as $tableName) {
        $columnsResult = $connection->query('SHOW COLUMNS FROM `' . $connection->real_escape_string($tableName) . '`');
        if (!$columnsResult) {
            if ($connection->errno === 1146) {
                continue;
            }

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
                continue 2;
            }
        }

        $hasKeyId = isset($availableColumns['key_id']);
        $keyIdSelect = $hasKeyId ? 'key_id' : 'NULL AS key_id';
        $query = "SELECT {$keyIdSelect}, control_number, first_name, middle_name, last_name, suffix, age, sex, barangay, program, division, contact_number, created_at FROM `{$tableName}`";

        if ($searchKeyword !== '') {
            $query .= " WHERE LOWER(COALESCE(first_name, '')) LIKE ? ESCAPE '\\\\'"
                . " OR LOWER(COALESCE(middle_name, '')) LIKE ? ESCAPE '\\\\'"
                . " OR LOWER(COALESCE(last_name, '')) LIKE ? ESCAPE '\\\\'"
                . " OR LOWER(COALESCE(control_number, '')) LIKE ? ESCAPE '\\\\'";
        }

        $query .= ' ORDER BY created_at DESC';
        $statement = $connection->prepare($query);

        if (!$statement) {
            throw new RuntimeException('Could not prepare client query.');
        }

        if ($searchKeyword !== '') {
            $statement->bind_param('ssss', $searchPattern, $searchPattern, $searchPattern, $searchPattern);
        }

        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Could not fetch client records.');
        }

        $result = $statement->get_result();
        if (!$result) {
            $statement->close();
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
                $parsedTime = strtotime($createdAtRaw);
                if ($parsedTime !== false) {
                    $timestamp = $parsedTime;
                    $createdAt = date('m/d/y', $timestamp);
                }
            }

            $allClients[] = [
                'key_id' => (int) ($row['key_id'] ?? 0),
                'control_number' => (string) ($row['control_number'] ?? ''),
                'full_name' => $fullName,
                'age' => (string) ($row['age'] ?? ''),
                'sex' => (string) ($row['sex'] ?? ''),
                'barangay' => (string) ($row['barangay'] ?? ''),
                'program' => (string) ($row['program'] ?? ''),
                'division' => (string) ($row['division'] ?? ''),
                'source_table' => $tableName,
                'contact_number' => (string) ($row['contact_number'] ?? ''),
                'created_at' => $createdAt,
                'created_at_sort' => $timestamp,
            ];
        }

        $statement->close();
    }

    usort(
        $allClients,
        static function (array $first, array $second): int {
            return ((int) ($second['created_at_sort'] ?? 0)) <=> ((int) ($first['created_at_sort'] ?? 0));
        }
    );

    $programOptions = [];
    $barangayOptions = [];
    foreach ($allClients as $client) {
        $program = trim((string) ($client['program'] ?? ''));
        $barangay = trim((string) ($client['barangay'] ?? ''));
        if ($program !== '') {
            $programOptions[$program] = true;
        }
        if ($barangay !== '') {
            $barangayOptions[$barangay] = true;
        }
    }

    $filteredClients = array_values(array_filter(
        $allClients,
        static function (array $client) use ($programFilter, $barangayFilter, $ageBracketFilter, $sexFilter): bool {
            if ($programFilter !== '' && normalize_filter_value((string) ($client['program'] ?? '')) !== normalize_filter_value($programFilter)) {
                return false;
            }

            if ($barangayFilter !== '' && normalize_filter_value((string) ($client['barangay'] ?? '')) !== normalize_filter_value($barangayFilter)) {
                return false;
            }

            if ($sexFilter !== '' && normalize_filter_value((string) ($client['sex'] ?? '')) !== normalize_filter_value($sexFilter)) {
                return false;
            }

            if ($ageBracketFilter !== '' && resolve_age_bracket($client['age'] ?? '') !== $ageBracketFilter) {
                return false;
            }

            return true;
        }
    ));

    $totalRows = count($filteredClients);
    $totalPages = max(1, (int) ceil($totalRows / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }

    $offset = ($page - 1) * $perPage;
    $clients = array_slice($filteredClients, $offset, $perPage);

    foreach ($clients as &$client) {
        $controlNumber = (string) ($client['control_number'] ?? '');
        $program = (string) ($client['program'] ?? '');
        $sourceTable = (string) ($client['source_table'] ?? '');
        $scan = null;

        if ($sourceTable === 'ciu_table') {
            $ciuScanPrograms = [
                $program,
                'CIU Form',
                'Social Case Study Report (SCSR)',
            ];

            foreach (array_values(array_unique(array_filter($ciuScanPrograms, static fn(string $value): bool => trim($value) !== ''))) as $scanProgram) {
                $scan = get_latest_client_scan($connection, $controlNumber, $scanProgram);
                if ($scan !== null) {
                    break;
                }
            }
        } else {
            $scan = get_latest_client_scan($connection, $controlNumber, $program);
        }

        $client['has_scan_copy'] = $scan !== null;
        $client['scan_copy_path'] = $scan['image_path'] ?? '';
        $client['scan_uploaded_by'] = $scan['uploaded_by'] ?? '';
        $client['scan_uploaded_at'] = $scan['uploaded_at'] ?? '';
        unset($client['created_at_sort']);
    }
    unset($client);

    $connection->close();

    $programValues = array_keys($programOptions);
    $barangayValues = array_keys($barangayOptions);
    natcasesort($programValues);
    natcasesort($barangayValues);

    echo json_encode([
        'clients' => $clients,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total_rows' => $totalRows,
            'total_pages' => $totalPages,
        ],
        'filter_options' => [
            'programs' => array_values($programValues),
            'barangays' => array_values($barangayValues),
        ],
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['clients' => [], 'message' => 'Unable to fetch clients.']);
}
