<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/report_data_service.php';

require_login(['super admin']);
header('Content-Type: application/json; charset=utf-8');

try {
    $service = new ReportDataService();
    $allRecords = $service->getAllRecords();

    $filters = [
        'program' => trim((string) ($_GET['program'] ?? '')),
        'barangay' => trim((string) ($_GET['barangay'] ?? '')),
        'year' => trim((string) ($_GET['year'] ?? '')),
        'month' => trim((string) ($_GET['month'] ?? '')),
        'sex' => trim((string) ($_GET['sex'] ?? '')),
        'age_bracket' => trim((string) ($_GET['age_bracket'] ?? '')),
        'sector' => trim((string) ($_GET['sector'] ?? '')),
        'household' => trim((string) ($_GET['household'] ?? '')),
        'date_from' => trim((string) ($_GET['date_from'] ?? '')),
        'date_to' => trim((string) ($_GET['date_to'] ?? '')),
    ];

    $filtered = apply_report_filters($allRecords, $filters);

    $search = trim((string) ($_GET['search'] ?? ''));
    if ($search !== '') {
        $filtered = array_values(array_filter($filtered, static function (array $row) use ($search): bool {
            $needle = function_exists('mb_strtolower') ? mb_strtolower($search, 'UTF-8') : strtolower($search);
            $haystacks = [
                (string) ($row['control_number'] ?? ''),
                (string) ($row['full_name'] ?? ''),
                (string) ($row['barangay'] ?? ''),
                (string) ($row['program'] ?? ''),
                (string) ($row['sector'] ?? ''),
                (string) ($row['household'] ?? ''),
                (string) ($row['interventions'] ?? ''),
            ];

            foreach ($haystacks as $value) {
                $normalized = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
                if (str_contains($normalized, $needle)) {
                    return true;
                }
            }

            return false;
        }));
    }

    $sortBy = (string) ($_GET['sort_by'] ?? 'created_at');
    $sortDir = strtolower((string) ($_GET['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
    $allowedSorts = ['created_at', 'full_name', 'control_number', 'program', 'barangay', 'sex', 'age', 'sector'];
    if (!in_array($sortBy, $allowedSorts, true)) {
        $sortBy = 'created_at';
    }

    usort($filtered, static function (array $a, array $b) use ($sortBy, $sortDir): int {
        $left = $a[$sortBy] ?? '';
        $right = $b[$sortBy] ?? '';

        if (is_numeric($left) && is_numeric($right)) {
            $cmp = (float) $left <=> (float) $right;
        } else {
            $cmp = strcasecmp((string) $left, (string) $right);
        }

        return $sortDir === 'asc' ? $cmp : -$cmp;
    });

    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 10)));
    $totalRows = count($filtered);
    $totalPages = max(1, (int) ceil($totalRows / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }

    $offset = ($page - 1) * $perPage;
    $rows = array_slice($filtered, $offset, $perPage);

    $uniqueBeneficiaries = [];
    $uniqueHouseholds = [];
    $programCounts = [];
    $barangayCounts = [];
    $yearCounts = [];
    $monthCounts = [];
    $sexCounts = [];
    $ageBracketCounts = [];
    $sectorCounts = [];

    foreach ($filtered as $row) {
        $uniqueBeneficiaries[(string) ($row['unique_key'] ?? '')] = true;

        $householdKey = trim((string) ($row['household'] ?? ''));
        if ($householdKey !== '') {
            $uniqueHouseholds[$householdKey] = true;
        }

        $program = (string) ($row['program'] ?? 'Unknown');
        $barangay = (string) ($row['barangay'] ?? 'Unspecified');
        $sex = (string) ($row['sex'] ?? 'Unspecified');
        $ageBracket = (string) ($row['age_bracket'] ?? 'Unknown');
        $sector = (string) ($row['sector'] ?? 'Unspecified');

        $year = $row['created_at'] !== '' ? substr((string) $row['created_at'], 0, 4) : 'Unknown';
        $month = $row['created_at'] !== '' ? substr((string) $row['created_at'], 0, 7) : 'Unknown';

        $programCounts[$program] = ($programCounts[$program] ?? 0) + 1;
        $barangayCounts[$barangay] = ($barangayCounts[$barangay] ?? 0) + 1;
        $yearCounts[$year] = ($yearCounts[$year] ?? 0) + 1;
        $monthCounts[$month] = ($monthCounts[$month] ?? 0) + 1;
        $sexCounts[$sex] = ($sexCounts[$sex] ?? 0) + 1;
        $ageBracketCounts[$ageBracket] = ($ageBracketCounts[$ageBracket] ?? 0) + 1;
        $sectorCounts[$sector] = ($sectorCounts[$sector] ?? 0) + 1;
    }

    ksort($yearCounts);
    ksort($monthCounts);

    $householdInterventions = build_households_with_multiple_interventions($filtered);

    $filterOptions = [
        'programs' => array_values(array_unique(array_map(static fn(array $row): string => (string) ($row['program'] ?? ''), $allRecords))),
        'barangays' => array_values(array_unique(array_filter(array_map(static fn(array $row): string => (string) ($row['barangay'] ?? ''), $allRecords), static fn(string $val): bool => $val !== ''))),
        'years' => array_values(array_unique(array_filter(array_map(static function (array $row): string {
            return $row['created_at'] !== '' ? substr((string) $row['created_at'], 0, 4) : '';
        }, $allRecords), static fn(string $val): bool => $val !== ''))),
        'sexes' => array_values(array_unique(array_map(static fn(array $row): string => (string) ($row['sex'] ?? 'Unspecified'), $allRecords))),
        'age_brackets' => ['0-12', '13-17', '18-24', '25-39', '40-59', '60+', 'Unknown'],
        'sectors' => array_values(array_unique(array_filter(array_map(static fn(array $row): string => (string) ($row['sector'] ?? ''), $allRecords), static fn(string $val): bool => $val !== ''))),
    ];

    sort($filterOptions['programs']);
    sort($filterOptions['barangays']);
    sort($filterOptions['years']);
    sort($filterOptions['sexes']);
    sort($filterOptions['sectors']);

    echo json_encode([
        'success' => true,
        'data' => [
            'summary' => [
                'total_beneficiaries' => count($uniqueBeneficiaries),
                'total_households_served' => count($uniqueHouseholds),
                'total_programs' => count(array_filter($programCounts, static fn(int $count): bool => $count > 0)),
                'total_male' => (int) ($sexCounts['Male'] ?? 0),
                'total_female' => (int) ($sexCounts['Female'] ?? 0),
                'total_with_two_plus_interventions' => count($householdInterventions),
            ],
            'charts' => [
                'beneficiaries_per_program' => $programCounts,
                'beneficiaries_per_barangay' => $barangayCounts,
                'beneficiaries_per_year' => $yearCounts,
                'beneficiaries_per_month' => $monthCounts,
                'by_sex' => $sexCounts,
                'by_age_bracket' => $ageBracketCounts,
                'by_sector' => $sectorCounts,
            ],
            'table' => [
                'rows' => $rows,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_rows' => $totalRows,
                    'total_pages' => $totalPages,
                ],
            ],
            'household_interventions' => $householdInterventions,
            'filter_options' => $filterOptions,
            'applied_filters' => $filters,
            'generated_at' => date('Y-m-d H:i:s'),
        ],
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to generate report data.',
    ]);
}
