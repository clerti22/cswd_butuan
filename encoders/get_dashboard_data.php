<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/db.php';
require_once __DIR__ . '/encoder_scope.php';
require_once __DIR__ . '/report/report_data_service.php';

require_login(['encoder']);

header('Content-Type: application/json; charset=utf-8');

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

try {
    $program = encoder_scope_program();
    $today = date('Y-m-d');

    $service = new ReportDataService();
    $allRecords = $service->getAllRecords();

    $records = array_values(array_filter(
        $allRecords,
        static fn(array $record): bool => strtoupper(trim((string) ($record['program'] ?? ''))) === $program
    ));

    $todayCount = 0;
    $barangayCounts = [];

    foreach ($records as $record) {
        if ((string) ($record['created_at'] ?? '') === $today) {
            $todayCount++;
        }

        $barangay = trim((string) ($record['barangay'] ?? ''));
        $barangayKey = $barangay !== '' ? $barangay : 'Unspecified';
        $barangayCounts[$barangayKey] = ($barangayCounts[$barangayKey] ?? 0) + 1;
    }

    arsort($barangayCounts);
    $barangayChart = [];
    foreach ($barangayCounts as $barangay => $count) {
        $barangayChart[] = ['label' => $barangay, 'y' => $count];
    }

    usort(
        $records,
        static fn(array $a, array $b): int => ((int) ($b['created_at_timestamp'] ?? 0)) <=> ((int) ($a['created_at_timestamp'] ?? 0))
    );

    $clients = array_map('format_client_row', array_slice($records, 0, 5));

    $connection = get_database_connection();

    $activities = [];
    $query = $connection->prepare('SELECT notification_id, name, activity, created_at, role, program, division FROM notifications WHERE UPPER(TRIM(program)) = ? ORDER BY created_at DESC, notification_id DESC LIMIT 3');
    $query->bind_param('s', $program);
    $query->execute();
    $result = $query->get_result();

    while ($row = $result->fetch_assoc()) {
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
    $query->close();

    $notificationCount = 0;
    $countQuery = $connection->prepare('SELECT COUNT(*) AS total FROM notifications WHERE UPPER(TRIM(program)) = ?');
    $countQuery->bind_param('s', $program);
    $countQuery->execute();
    $countResult = $countQuery->get_result();
    $countRow = $countResult ? $countResult->fetch_assoc() : null;
    $notificationCount = (int) ($countRow['total'] ?? 0);
    $countQuery->close();

    $connection->close();

    echo json_encode([
        'today_total' => $todayCount,
        'program_total' => count($records),
        'program' => $program,
        'clients' => $clients,
        'barangay_chart' => $barangayChart,
        'recent_activities' => $activities,
        'notification_count' => $notificationCount,
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'today_total' => 0,
        'program_total' => 0,
        'program' => '',
        'clients' => [],
        'barangay_chart' => [],
        'recent_activities' => [],
        'notification_count' => 0,
        'message' => 'Unable to load dashboard data.',
    ]);
}
