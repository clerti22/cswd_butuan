<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/db.php';

/**
 * Shared reporting data builder for the Super Admin report module.
 */
final class ReportDataService
{
    /** @var array<string, string> */
    private array $programTables = [
        'PWUD' => 'pwud_table',
        'SOLO PARENT' => 'soloparent_info_table',
        'PYAP' => 'pyap_table',
        'SENIOR CITIZEN' => 'snr_table',
        'PWD' => 'pwd_table',
        'CAR' => 'car_table',
        'CIU' => 'ciu_table',
        'VAWC' => 'vawc_table',
        'CSAE' => 'csae_table',
        'CICL' => 'cicl_table',
    ];

    /** @var array<string, array<int, string>> */
    private array $columnCandidates = [
        'id' => ['key_id', 'id'],
        'control_number' => ['control_number', 'case_no', 'case_number'],
        'first_name' => ['first_name'],
        'middle_name' => ['middle_name'],
        'last_name' => ['last_name'],
        'suffix' => ['suffix'],
        'sex' => ['sex', 'gender'],
        'age' => ['age'],
        'barangay' => ['barangay'],
        'contact_details' => ['contact_number', 'contact_details', 'phone_number', 'mobile_number'],
        'sector' => ['sector'],
        'household' => ['household', 'household_name', 'household_head'],
        'interventions' => ['interventions', 'intervention', 'intervention_needed', 'assistance_received', 'services_needed'],
        'created_at' => ['created_at', 'date_created'],
    ];

    /** @var array<string, array<string, string>> */
    private array $resolvedColumns = [];

    public function getProgramTables(): array
    {
        return $this->programTables;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAllRecords(): array
    {
        $connection = get_database_connection();

        try {
            $records = [];
            foreach ($this->programTables as $programName => $tableName) {
                $columns = $this->inspectColumns($connection, $tableName);
                $this->resolvedColumns[$tableName] = $columns;

                $select = $this->buildSelectColumns($columns, $programName, $tableName);
                $sql = 'SELECT ' . implode(', ', $select) . " FROM `{$tableName}`";
                $result = $connection->query($sql);

                if (!$result) {
                    continue;
                }

                while ($row = $result->fetch_assoc()) {
                    $records[] = $this->normalizeRecord($row);
                }
            }

            return $records;
        } finally {
            $connection->close();
        }
    }

    private function inspectColumns(mysqli $connection, string $tableName): array
    {
        $available = [];
        $query = "SHOW COLUMNS FROM `{$tableName}`";
        $result = $connection->query($query);

        if (!$result) {
            return $available;
        }

        while ($row = $result->fetch_assoc()) {
            $field = (string) ($row['Field'] ?? '');
            if ($field !== '') {
                $available[$field] = true;
            }
        }

        $resolved = [];
        foreach ($this->columnCandidates as $semantic => $candidates) {
            foreach ($candidates as $candidate) {
                if (isset($available[$candidate])) {
                    $resolved[$semantic] = $candidate;
                    break;
                }
            }
        }

        return $resolved;
    }

    private function buildSelectColumns(array $resolved, string $programName, string $tableName): array
    {
        $columns = [];

        foreach (array_keys($this->columnCandidates) as $semantic) {
            if ($semantic === 'program') {
                continue;
            }

            if (isset($resolved[$semantic])) {
                $columns[] = '`' . $resolved[$semantic] . '` AS `' . $semantic . '`';
            } else {
                $columns[] = "'' AS `{$semantic}`";
            }
        }

        $columns[] = "'" . addslashes($programName) . "' AS `program`";
        $columns[] = "'" . addslashes($tableName) . "' AS `source_table`";

        return $columns;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeRecord(array $row): array
    {
        $firstName = trim((string) ($row['first_name'] ?? ''));
        $middleName = trim((string) ($row['middle_name'] ?? ''));
        $lastName = trim((string) ($row['last_name'] ?? ''));
        $suffix = trim((string) ($row['suffix'] ?? ''));

        $fullName = trim(implode(' ', array_values(array_filter([$firstName, $middleName, $lastName, $suffix], static fn(string $part): bool => $part !== ''))));
        $controlNumber = trim((string) ($row['control_number'] ?? ''));
        $sex = $this->normalizeSex((string) ($row['sex'] ?? ''));
        $age = is_numeric($row['age'] ?? null) ? (int) $row['age'] : null;

        $createdAtRaw = trim((string) ($row['created_at'] ?? ''));
        $createdTimestamp = $createdAtRaw !== '' ? strtotime($createdAtRaw) : false;

        return [
            'id' => (string) ($row['id'] ?? ''),
            'control_number' => $controlNumber,
            'full_name' => $fullName,
            'sex' => $sex,
            'age' => $age,
            'age_bracket' => $this->deriveAgeBracket($age),
            'barangay' => trim((string) ($row['barangay'] ?? '')),
            'contact_details' => trim((string) ($row['contact_details'] ?? '')),
            'program' => trim((string) ($row['program'] ?? 'Unknown')),
            'sector' => trim((string) ($row['sector'] ?? '')),
            'household' => trim((string) ($row['household'] ?? '')),
            'interventions' => $this->normalizeInterventions((string) ($row['interventions'] ?? '')),
            'interventions_count' => $this->countInterventions((string) ($row['interventions'] ?? '')),
            'created_at' => $createdTimestamp ? date('Y-m-d', (int) $createdTimestamp) : '',
            'created_at_timestamp' => $createdTimestamp ? (int) $createdTimestamp : 0,
            'source_table' => (string) ($row['source_table'] ?? ''),
            'unique_key' => $controlNumber !== '' ? $controlNumber : ((string) ($row['source_table'] ?? '') . '#' . (string) ($row['id'] ?? '')),
        ];
    }

    private function normalizeSex(string $value): string
    {
        $raw = strtolower(trim($value));
        if (in_array($raw, ['male', 'm'], true)) {
            return 'Male';
        }

        if (in_array($raw, ['female', 'f'], true)) {
            return 'Female';
        }

        return $value !== '' ? ucfirst($value) : 'Unspecified';
    }

    private function deriveAgeBracket(?int $age): string
    {
        if ($age === null || $age < 0) {
            return 'Unknown';
        }

        return match (true) {
            $age <= 12 => '0-12',
            $age <= 17 => '13-17',
            $age <= 24 => '18-24',
            $age <= 39 => '25-39',
            $age <= 59 => '40-59',
            default => '60+',
        };
    }

    private function normalizeInterventions(string $value): string
    {
        $parts = $this->splitInterventions($value);
        return implode(', ', $parts);
    }

    private function countInterventions(string $value): int
    {
        return count($this->splitInterventions($value));
    }

    /**
     * @return array<int, string>
     */
    private function splitInterventions(string $value): array
    {
        $cleaned = preg_replace('/\r\n|\r|\n/', ',', $value) ?? '';
        $parts = preg_split('/[,;|]+/', $cleaned) ?: [];

        $items = [];
        foreach ($parts as $part) {
            $trimmed = trim($part);
            if ($trimmed !== '') {
                $items[] = $trimmed;
            }
        }

        return array_values(array_unique($items));
    }
}

/**
 * @param array<int, array<string, mixed>> $records
 * @param array<string, string> $filters
 * @return array<int, array<string, mixed>>
 */
function apply_report_filters(array $records, array $filters): array
{
    return array_values(array_filter($records, static function (array $row) use ($filters): bool {
        $program = trim((string) ($filters['program'] ?? ''));
        if ($program !== '' && strcasecmp((string) ($row['program'] ?? ''), $program) !== 0) {
            return false;
        }

        $barangay = trim((string) ($filters['barangay'] ?? ''));
        if ($barangay !== '' && strcasecmp((string) ($row['barangay'] ?? ''), $barangay) !== 0) {
            return false;
        }

        $sex = trim((string) ($filters['sex'] ?? ''));
        if ($sex !== '' && strcasecmp((string) ($row['sex'] ?? ''), $sex) !== 0) {
            return false;
        }

        $ageBracket = trim((string) ($filters['age_bracket'] ?? ''));
        if ($ageBracket !== '' && strcasecmp((string) ($row['age_bracket'] ?? ''), $ageBracket) !== 0) {
            return false;
        }

        $sector = trim((string) ($filters['sector'] ?? ''));
        if ($sector !== '' && strcasecmp((string) ($row['sector'] ?? ''), $sector) !== 0) {
            return false;
        }

        $household = trim((string) ($filters['household'] ?? ''));
        if ($household !== '' && stripos((string) ($row['household'] ?? ''), $household) === false) {
            return false;
        }

        $year = trim((string) ($filters['year'] ?? ''));
        if ($year !== '') {
            $recordYear = $row['created_at'] !== '' ? substr((string) $row['created_at'], 0, 4) : '';
            if ($recordYear !== $year) {
                return false;
            }
        }

        $month = trim((string) ($filters['month'] ?? ''));
        if ($month !== '') {
            $recordMonth = $row['created_at'] !== '' ? substr((string) $row['created_at'], 5, 2) : '';
            if ($recordMonth !== str_pad($month, 2, '0', STR_PAD_LEFT)) {
                return false;
            }
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '' && (string) ($row['created_at'] ?? '') < $dateFrom) {
            return false;
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '' && (string) ($row['created_at'] ?? '') > $dateTo) {
            return false;
        }

        return true;
    }));
}

/**
 * @param array<int, array<string, mixed>> $records
 * @return array<int, array<string, mixed>>
 */
function build_households_with_multiple_interventions(array $records): array
{
    $grouped = [];

    foreach ($records as $row) {
        $household = trim((string) ($row['household'] ?? ''));
        $control = trim((string) ($row['control_number'] ?? ''));
        $fallback = trim((string) ($row['full_name'] ?? ''));
        $groupKey = $household !== '' ? $household : ($control !== '' ? $control : $fallback);

        if ($groupKey === '') {
            continue;
        }

        if (!isset($grouped[$groupKey])) {
            $grouped[$groupKey] = [
                'household' => $household !== '' ? $household : $groupKey,
                'control_number' => $control,
                'household_head' => (string) ($row['full_name'] ?? ''),
                'barangay' => (string) ($row['barangay'] ?? ''),
                'interventions' => [],
                'intervention_count' => 0,
            ];
        }

        $items = array_filter(array_map('trim', explode(',', (string) ($row['interventions'] ?? ''))), static fn(string $value): bool => $value !== '');
        foreach ($items as $item) {
            $grouped[$groupKey]['interventions'][$item] = true;
        }
    }

    $results = [];
    foreach ($grouped as $entry) {
        $interventions = array_keys($entry['interventions']);
        $count = count($interventions);
        if ($count < 2) {
            continue;
        }

        $entry['intervention_count'] = $count;
        $entry['interventions_received'] = implode(', ', $interventions);
        unset($entry['interventions']);
        $results[] = $entry;
    }

    usort($results, static fn(array $a, array $b): int => $b['intervention_count'] <=> $a['intervention_count']);

    return $results;
}
