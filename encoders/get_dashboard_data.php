<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/access_control.php';
require_once __DIR__ . '/../auth/db.php';
require_once __DIR__ . '/../admin/report/report_data_service.php';

require_login(['encoder']);
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
    $division = scoped_division();
    $role = current_user_role();
    $programs = array_map(static fn(string $program): string => strtoupper(trim($program)), scoped_programs());

    $service = new ReportDataService();
    $records = $service->getAllRecords();

    $filteredRecords = array_values(array_filter($records, static function (array $record) use ($programs): bool {
        $program = strtoupper(trim((string) ($record['program'] ?? '')));
        return in_array($program, $programs, true);
    }));

    $todayCount = 0;
    $todayDate = date('Y-m-d');
    $barangayTotals = [];

    foreach ($filteredRecords as $record) {
        $createdDate = substr((string) ($record['created_at'] ?? ''), 0, 10);
        if ($createdDate === $todayDate) {
            $todayCount++;
        }

        $barangay = trim((string) ($record['barangay'] ?? ''));
        $key = $barangay !== '' ? $barangay : 'Unspecified';
        $barangayTotals[$key] = ($barangayTotals[$key] ?? 0) + 1;
    }

    arsort($barangayTotals);

    usort(
        $filteredRecords,
        static fn(array $a, array $b): int => ((int) ($b['created_at_timestamp'] ?? 0)) <=> ((int) ($a['created_at_timestamp'] ?? 0))
    );

    $clientRows = array_map('format_client_row', array_slice($filteredRecords, 0, 10));

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
    $notificationCount = 0;

    if ($role === 'encoder') {
        $program = $programs[0] ?? '';
        $notificationsQuery = 'SELECT notification_id, name, activity, created_at, role, program, division FROM notifications WHERE UPPER(TRIM(division)) = ? AND UPPER(TRIM(program)) = ? ORDER BY created_at DESC, notification_id DESC LIMIT 3';
        $notificationsStatement = $connection->prepare($notificationsQuery);
        if ($notificationsStatement) {
            $notificationsStatement->bind_param('ss', $division, $program);
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

        $notificationCountQuery = 'SELECT COUNT(*) AS total FROM notifications WHERE UPPER(TRIM(division)) = ? AND UPPER(TRIM(program)) = ?';
        $notificationCountStatement = $connection->prepare($notificationCountQuery);
        if ($notificationCountStatement) {
            $notificationCountStatement->bind_param('ss', $division, $program);
            if ($notificationCountStatement->execute()) {
                $countResult = $notificationCountStatement->get_result();
                $countRow = $countResult ? $countResult->fetch_assoc() : null;
                $notificationCount = (int) ($countRow['total'] ?? 0);
            }
            $notificationCountStatement->close();
        }
    } else {
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
    }

    $connection->close();

    $scopeLabel = $role === 'encoder'
        ? ((string) ($programs[0] ?? 'Assigned Program'))
        : 'Division';

    echo json_encode([
        'division' => $division,
        'total_beneficiaries' => count($filteredRecords),
        'beneficiary_scope_label' => $scopeLabel,
        'today_total' => $todayCount,
        'active_social_workers' => $activeSocialWorkers,
        'clients' => $clientRows,
        'barangay_totals' => $barangayTotals,
        'recent_activities' => $activities,
        'notification_count' => $notificationCount,
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'division' => '',
        'total_beneficiaries' => 0,
        'beneficiary_scope_label' => 'Assigned Scope',
        'today_total' => 0,
        'active_social_workers' => 0,
        'clients' => [],
        'barangay_totals' => [],
        'recent_activities' => [],
        'notification_count' => 0,
        'message' => 'Unable to load dashboard data.',
    ]);
}
