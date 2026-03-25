<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/db.php';
require_once __DIR__ . '/report/report_data_service.php';

require_login(['super admin']);

header('Content-Type: application/json; charset=utf-8');

/**
 * @param array<string, mixed> $record
 */
function format_client_row(array $record): array
{
    return [
        'control_number' => trim((string) ($record['control_number'] ?? '')) !== '' ? (string) $record['control_number'] : 'N/A',
        'full_name' => trim((string) ($record['full_name'] ?? '')) !== '' ? (string) $record['full_name'] : 'N/A',
        'age' => ($record['age'] ?? null) !== null && (string) $record['age'] !== '' ? (string) $record['age'] : 'N/A',
        'sex' => trim((string) ($record['sex'] ?? '')) !== '' ? (string) $record['sex'] : 'N/A',
        'barangay' => trim((string) ($record['barangay'] ?? '')) !== '' ? (string) $record['barangay'] : 'N/A',
        'contact_details' => trim((string) ($record['contact_details'] ?? '')) !== '' ? (string) $record['contact_details'] : 'N/A',
    ];
}

function format_activity_timestamp(?string $rawTimestamp): string
{
    if ($rawTimestamp === null || trim($rawTimestamp) === '') {
        return 'Unknown time';
    }

    $time = strtotime($rawTimestamp);
    if ($time === false) {
        return $rawTimestamp;
    }

    $now = time();
    $todayStart = strtotime('today', $now);
    $yesterdayStart = strtotime('yesterday', $now);

    if ($time >= $todayStart) {
        return 'Today ' . date('g:i A', $time);
    }

    if ($time >= $yesterdayStart && $time < $todayStart) {
        return 'Yesterday ' . date('g:i A', $time);
    }

    return date('M j, Y g:i A', $time);
}

try {
    $service = new ReportDataService();
    $records = $service->getAllRecords();

    $divisionPrograms = [
        'DPSD' => ['PWUD', 'SOLO PARENT', 'PYAP'],
        'PSSD' => ['PWD', 'SENIOR CITIZEN'],
        'RPSD' => ['CIU', 'CICL', 'CSAE', 'CAR', 'VAWC'],
    ];

    $totals = [
        'dpsd' => 0,
        'pssd' => 0,
        'rpsd' => 0,
    ];

    $todayCount = 0;
    $todayDate = date('Y-m-d');

    foreach ($records as $record) {
        $program = strtoupper(trim((string) ($record['program'] ?? '')));

        if (in_array($program, $divisionPrograms['DPSD'], true)) {
            $totals['dpsd']++;
        } elseif (in_array($program, $divisionPrograms['PSSD'], true)) {
            $totals['pssd']++;
        } elseif (in_array($program, $divisionPrograms['RPSD'], true)) {
            $totals['rpsd']++;
        }

        if ((string) ($record['created_at'] ?? '') === $todayDate) {
            $todayCount++;
        }
    }

    usort(
        $records,
        static fn(array $a, array $b): int => ((int) ($b['created_at_timestamp'] ?? 0)) <=> ((int) ($a['created_at_timestamp'] ?? 0))
    );

    $clientRows = array_map('format_client_row', array_slice($records, 0, 5));

    $connection = get_database_connection();

    $usersColumnsResult = $connection->query('SHOW COLUMNS FROM users');
    $userColumns = [];
    if ($usersColumnsResult) {
        while ($column = $usersColumnsResult->fetch_assoc()) {
            $name = (string) ($column['Field'] ?? '');
            if ($name !== '') {
                $userColumns[$name] = true;
            }
        }
    }

    $hasFullNameColumn = isset($userColumns['full_name']);
    $nameParts = ['first_name', 'middle_name', 'last_name', 'suffix'];
    $availableNameParts = [];
    foreach ($nameParts as $partColumn) {
        if (isset($userColumns[$partColumn])) {
            $availableNameParts[] = $partColumn;
        }
    }

    $nameSelectParts = [];
    if ($hasFullNameColumn) {
        $nameSelectParts[] = "COALESCE(NULLIF(TRIM(full_name), ''), '')";
    }

    foreach ($availableNameParts as $column) {
        $nameSelectParts[] = "COALESCE(NULLIF(TRIM({$column}), ''), '')";
    }

    if ($nameSelectParts === []) {
        $nameSelect = "'N/A'";
    } else {
        $nameSelect = "TRIM(CONCAT_WS(' ', " . implode(', ', $nameSelectParts) . '))';
    }

    $emailSelect = isset($userColumns['email']) ? 'COALESCE(NULLIF(TRIM(email), \'\'), \'N/A\')' : "'N/A'";
    $phoneSelect = isset($userColumns['phone_number'])
        ? 'COALESCE(NULLIF(TRIM(phone_number), \'\'), \'N/A\')'
        : (isset($userColumns['contact_number']) ? 'COALESCE(NULLIF(TRIM(contact_number), \'\'), \'N/A\')' : "'N/A'");

    $orderColumn = isset($userColumns['created_at'])
        ? 'created_at DESC'
        : (isset($userColumns['id']) ? 'id DESC' : (isset($userColumns['user_id']) ? 'user_id DESC' : 'full_name ASC'));

    $socialWorkersQuery = "SELECT {$nameSelect} AS full_name, {$emailSelect} AS email, {$phoneSelect} AS phone_number FROM users ORDER BY {$orderColumn} LIMIT 5";
    $socialWorkersResult = $connection->query($socialWorkersQuery);

    $socialWorkers = [];
    if ($socialWorkersResult) {
        while ($row = $socialWorkersResult->fetch_assoc()) {
            $socialWorkers[] = [
                'full_name' => trim((string) ($row['full_name'] ?? '')) !== '' ? (string) $row['full_name'] : 'N/A',
                'email' => (string) ($row['email'] ?? 'N/A'),
                'phone_number' => (string) ($row['phone_number'] ?? 'N/A'),
            ];
        }
    }

    $statusColumn = isset($userColumns['statuz']) ? 'statuz' : (isset($userColumns['status']) ? 'status' : '');
    $activeSocialWorkers = 0;

    if ($statusColumn !== '') {
        $activeQuery = "SELECT COUNT(*) AS total_active FROM users WHERE LOWER(COALESCE({$statusColumn}, '')) = 'active'";
        $activeResult = $connection->query($activeQuery);
        $activeRow = $activeResult ? $activeResult->fetch_assoc() : null;
        $activeSocialWorkers = (int) ($activeRow['total_active'] ?? 0);
    }

    $activities = [];
    $notificationsQuery = 'SELECT notification_id, name, activity, created_at, role, program, division FROM notifications ORDER BY created_at DESC, notification_id DESC LIMIT 5';
    $notificationsResult = $connection->query($notificationsQuery);
    if ($notificationsResult) {
        while ($row = $notificationsResult->fetch_assoc()) {
            $name = trim((string) ($row['name'] ?? ''));
            $activity = trim((string) ($row['activity'] ?? ''));
            $mainLine = $activity !== '' ? $activity : ($name !== '' ? $name : 'Unknown activity');
            if ($name !== '' && stripos($mainLine, $name) === false) {
                $mainLine .= ' · ' . $name;
            }

            $activities[] = [
                'notification_id' => (int) ($row['notification_id'] ?? 0),
                'name' => $name,
                'activity' => $activity,
                'created_at' => (string) ($row['created_at'] ?? ''),
                'role' => trim((string) ($row['role'] ?? 'Unknown')),
                'program' => trim((string) ($row['program'] ?? '')),
                'division' => trim((string) ($row['division'] ?? '')),
                'main_line' => $mainLine,
                'secondary_line' => trim((string) ($row['role'] ?? 'Unknown')) . ' · ' . format_activity_timestamp((string) ($row['created_at'] ?? '')),
            ];
        }
    }

    $connection->close();

    echo json_encode([
        'totals' => $totals,
        'today_total' => $todayCount,
        'active_social_workers' => $activeSocialWorkers,
        'clients' => $clientRows,
        'social_workers' => $socialWorkers,
        'recent_activities' => $activities,
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'totals' => ['dpsd' => 0, 'pssd' => 0, 'rpsd' => 0],
        'today_total' => 0,
        'active_social_workers' => 0,
        'clients' => [],
        'social_workers' => [],
        'recent_activities' => [],
        'message' => 'Unable to load dashboard data.',
    ]);
}
