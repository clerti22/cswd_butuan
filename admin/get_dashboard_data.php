<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/db.php';
require_once __DIR__ . '/division_scope.php';
require_once __DIR__ . '/report/report_data_service.php';

require_login(['admin']);
header('Content-Type: application/json; charset=utf-8');

/**
 * @param array<string, mixed> $record
 * @return array<string, string>
 */
function format_client_row(array $record): array
{
    return [
        'control_number' => trim((string) ($record['control_number'] ?? '')) !== '' ? (string) $record['control_number'] : 'N/A',
        'full_name' => trim((string) ($record['full_name'] ?? '')) !== '' ? (string) $record['full_name'] : 'N/A',
        'age' => ($record['age'] ?? null) !== null && (string) ($record['age'] ?? '') !== '' ? (string) $record['age'] : 'N/A',
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
    $division = admin_scope_division();
    $programs = array_map(static fn(string $program): string => strtoupper(trim($program)), admin_scope_programs());

    $service = new ReportDataService();
    $records = $service->getAllRecords();

    $filteredRecords = array_values(array_filter($records, static function (array $record) use ($programs): bool {
        $program = strtoupper(trim((string) ($record['program'] ?? '')));
        return in_array($program, $programs, true);
    }));

    $programTotals = [];
    foreach ($programs as $program) {
        $programTotals[$program] = 0;
    }

    $todayCount = 0;
    $todayDate = date('Y-m-d');

    foreach ($filteredRecords as $record) {
        $program = strtoupper(trim((string) ($record['program'] ?? '')));
        if (isset($programTotals[$program])) {
            $programTotals[$program]++;
        }

        if ((string) ($record['created_at'] ?? '') === $todayDate) {
            $todayCount++;
        }
    }

    usort(
        $filteredRecords,
        static fn(array $a, array $b): int => ((int) ($b['created_at_timestamp'] ?? 0)) <=> ((int) ($a['created_at_timestamp'] ?? 0))
    );

    $clientRows = array_map('format_client_row', array_slice($filteredRecords, 0, 5));

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

    $nameSelect = $nameSelectParts === []
        ? "'N/A'"
        : "TRIM(CONCAT_WS(' ', " . implode(', ', $nameSelectParts) . '))';

    $emailSelect = isset($userColumns['email']) ? 'COALESCE(NULLIF(TRIM(email), \'\'), \'N/A\')' : "'N/A'";
    $phoneSelect = isset($userColumns['phone_number'])
        ? 'COALESCE(NULLIF(TRIM(phone_number), \'\'), \'N/A\')'
        : (isset($userColumns['contact_number']) ? 'COALESCE(NULLIF(TRIM(contact_number), \'\'), \'N/A\')' : "'N/A'");

    $orderColumn = isset($userColumns['created_at'])
        ? 'created_at DESC'
        : (isset($userColumns['id']) ? 'id DESC' : (isset($userColumns['user_id']) ? 'user_id DESC' : 'full_name ASC'));

    $divisionWhere = isset($userColumns['division']) ? 'WHERE UPPER(TRIM(division)) = ?' : '';
    $socialWorkersQuery = "SELECT {$nameSelect} AS full_name, {$emailSelect} AS email, {$phoneSelect} AS phone_number FROM users {$divisionWhere} ORDER BY {$orderColumn} LIMIT 5";
    $socialWorkersStatement = $connection->prepare($socialWorkersQuery);

    if ($socialWorkersStatement && $divisionWhere !== '') {
        $socialWorkersStatement->bind_param('s', $division);
    }

    $socialWorkers = [];
    if ($socialWorkersStatement && $socialWorkersStatement->execute()) {
        $result = $socialWorkersStatement->get_result();
        while ($row = $result->fetch_assoc()) {
            $socialWorkers[] = [
                'full_name' => trim((string) ($row['full_name'] ?? '')) !== '' ? (string) $row['full_name'] : 'N/A',
                'email' => (string) ($row['email'] ?? 'N/A'),
                'phone_number' => (string) ($row['phone_number'] ?? 'N/A'),
            ];
        }
    }
    if ($socialWorkersStatement) {
        $socialWorkersStatement->close();
    }

    $statusColumn = isset($userColumns['statuz']) ? 'statuz' : (isset($userColumns['status']) ? 'status' : '');
    $activeSocialWorkers = 0;

    if ($statusColumn !== '' && isset($userColumns['division'])) {
        $activeQuery = "SELECT COUNT(*) AS total_active FROM users WHERE UPPER(TRIM(division)) = ? AND LOWER(COALESCE({$statusColumn}, '')) = 'active'";
        $activeStatement = $connection->prepare($activeQuery);
        if ($activeStatement) {
            $activeStatement->bind_param('s', $division);
            if ($activeStatement->execute()) {
                $activeResult = $activeStatement->get_result();
                $activeRow = $activeResult ? $activeResult->fetch_assoc() : null;
                $activeSocialWorkers = (int) ($activeRow['total_active'] ?? 0);
            }
            $activeStatement->close();
        }
    }

    $activities = [];
    $notificationsQuery = 'SELECT notification_id, name, activity, created_at, role, program, division FROM notifications WHERE UPPER(TRIM(division)) = ? ORDER BY created_at DESC, notification_id DESC LIMIT 3';
    $notificationsStatement = $connection->prepare($notificationsQuery);
    if ($notificationsStatement) {
        $notificationsStatement->bind_param('s', $division);
        if ($notificationsStatement->execute()) {
            $notificationsResult = $notificationsStatement->get_result();
            while ($row = $notificationsResult->fetch_assoc()) {
                $activities[] = [
                    'notification_id' => (int) ($row['notification_id'] ?? 0),
                    'name' => trim((string) ($row['name'] ?? '')),
                    'activity' => trim((string) ($row['activity'] ?? '')),
                    'created_at' => (string) ($row['created_at'] ?? ''),
                    'role' => trim((string) ($row['role'] ?? 'Unknown')),
                    'program' => trim((string) ($row['program'] ?? '')),
                    'division' => trim((string) ($row['division'] ?? '')),
                    'formatted_created_at' => format_activity_timestamp((string) ($row['created_at'] ?? '')),
                ];
            }
        }
        $notificationsStatement->close();
    }

    $notificationCount = 0;
    $notificationCountQuery = 'SELECT COUNT(*) AS total FROM notifications WHERE UPPER(TRIM(division)) = ?';
    $notificationCountStatement = $connection->prepare($notificationCountQuery);
    if ($notificationCountStatement) {
        $notificationCountStatement->bind_param('s', $division);
        if ($notificationCountStatement->execute()) {
            $countResult = $notificationCountStatement->get_result();
            $countRow = $countResult ? $countResult->fetch_assoc() : null;
            $notificationCount = (int) ($countRow['total'] ?? 0);
        }
        $notificationCountStatement->close();
    }

    $connection->close();

    echo json_encode([
        'division' => $division,
        'program_totals' => $programTotals,
        'today_total' => $todayCount,
        'active_social_workers' => $activeSocialWorkers,
        'clients' => $clientRows,
        'social_workers' => $socialWorkers,
        'recent_activities' => $activities,
        'notification_count' => $notificationCount,
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'division' => '',
        'program_totals' => [],
        'today_total' => 0,
        'active_social_workers' => 0,
        'clients' => [],
        'social_workers' => [],
        'recent_activities' => [],
        'notification_count' => 0,
        'message' => 'Unable to load dashboard data.',
    ]);
}
