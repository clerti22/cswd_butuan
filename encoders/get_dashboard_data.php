<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/db.php';
require_once __DIR__ . '/../admin/report/report_data_service.php';

require_login(['encoder']);
header('Content-Type: application/json; charset=utf-8');

function format_client_row(array $record): array
{
    return [
        'control_number' => trim((string) ($record['control_number'] ?? '')) ?: 'N/A',
        'full_name' => trim((string) ($record['full_name'] ?? '')) ?: 'N/A',
        'age' => (string) ($record['age'] ?? 'N/A'),
        'sex' => trim((string) ($record['sex'] ?? '')) ?: 'N/A',
        'barangay' => trim((string) ($record['barangay'] ?? '')) ?: 'N/A',
        'contact_details' => trim((string) ($record['contact_details'] ?? '')) ?: 'N/A',
    ];
}

try {
    $division = current_user_division();
    $program = strtoupper(trim(current_user_program()));
    $map = division_program_map();
    if (!isset($map[$division]) || !in_array($program, $map[$division], true)) {
        throw new RuntimeException('Invalid encoder scope.');
    }

    $service = new ReportDataService();
    $records = $service->getAllRecords();

    $filtered = array_values(array_filter($records, static function (array $record) use ($program): bool {
        return strtoupper(trim((string) ($record['program'] ?? ''))) === $program;
    }));

    $today = date('Y-m-d');
    $todayCount = 0;
    $barangayTotals = [];

    foreach ($filtered as $record) {
        $createdDate = substr((string) ($record['created_at'] ?? ''), 0, 10);
        if ($createdDate === $today) {
            $todayCount++;
        }

        $barangay = trim((string) ($record['barangay'] ?? ''));
        $key = $barangay !== '' ? $barangay : 'Unspecified';
        $barangayTotals[$key] = ($barangayTotals[$key] ?? 0) + 1;
    }

    usort($filtered, static fn(array $a, array $b): int => ((int) ($b['created_at_timestamp'] ?? 0)) <=> ((int) ($a['created_at_timestamp'] ?? 0)));

    $connection = get_database_connection();
    $activeSocialWorkers = 0;
    $columns = $connection->query('SHOW COLUMNS FROM users');
    $hasStatus = false;
    $statusColumn = 'status';
    if ($columns) {
        while ($column = $columns->fetch_assoc()) {
            $field = strtolower((string) ($column['Field'] ?? ''));
            if ($field === 'status' || $field === 'statuz') {
                $hasStatus = true;
                $statusColumn = $field;
                break;
            }
        }
    }

    if ($hasStatus) {
        $activeStatement = $connection->prepare("SELECT COUNT(*) AS total_active FROM users WHERE UPPER(TRIM(division)) = ? AND LOWER(COALESCE({$statusColumn}, '')) = 'active'");
        if ($activeStatement) {
            $activeStatement->bind_param('s', $division);
            if ($activeStatement->execute()) {
                $activeSocialWorkers = (int) (($activeStatement->get_result()?->fetch_assoc()['total_active']) ?? 0);
            }
            $activeStatement->close();
        }
    }

    $activities = [];
    $notificationsStatement = $connection->prepare('SELECT notification_id, name, activity, created_at, role, program, division FROM notifications WHERE UPPER(TRIM(division)) = ? AND UPPER(TRIM(program)) = ? ORDER BY created_at DESC, notification_id DESC LIMIT 3');
    if ($notificationsStatement) {
        $notificationsStatement->bind_param('ss', $division, $program);
        if ($notificationsStatement->execute()) {
            $result = $notificationsStatement->get_result();
            while ($row = $result->fetch_assoc()) {
                $activities[] = [
                    'name' => trim((string) ($row['name'] ?? '')),
                    'activity' => trim((string) ($row['activity'] ?? '')),
                    'program' => trim((string) ($row['program'] ?? '')),
                    'formatted_created_at' => (string) ($row['created_at'] ?? ''),
                ];
            }
        }
        $notificationsStatement->close();
    }

    $connection->close();

    echo json_encode([
        'total_beneficiaries' => count($filtered),
        'today_total' => $todayCount,
        'active_social_workers' => $activeSocialWorkers,
        'clients' => array_map('format_client_row', array_slice($filtered, 0, 10)),
        'barangay_totals' => $barangayTotals,
        'recent_activities' => $activities,
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'total_beneficiaries' => 0,
        'today_total' => 0,
        'active_social_workers' => 0,
        'clients' => [],
        'barangay_totals' => [],
        'recent_activities' => [],
    ]);
}
