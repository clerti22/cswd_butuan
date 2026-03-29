<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/db.php';

require_login(['super admin']);
header('Content-Type: application/json; charset=utf-8');

function clean_value(mixed $value, string $fallback = 'N/A'): string
{
    $text = trim((string) ($value ?? ''));

    return $text !== '' ? $text : $fallback;
}

function format_notification_time(?string $rawTimestamp): string
{
    if ($rawTimestamp === null || trim($rawTimestamp) === '') {
        return 'Unknown time';
    }

    $time = strtotime($rawTimestamp);
    if ($time === false) {
        return $rawTimestamp;
    }

    return date('F j, Y - g:i A', $time);
}

function notification_group_label(?string $rawTimestamp, int $now): string
{
    if ($rawTimestamp === null || trim($rawTimestamp) === '') {
        return 'Older';
    }

    $time = strtotime($rawTimestamp);
    if ($time === false) {
        return 'Older';
    }

    $todayStart = strtotime('today', $now);
    $yesterdayStart = strtotime('yesterday', $now);
    $weekStart = strtotime('monday this week', $now);

    if ($time >= $todayStart) {
        return 'Today';
    }

    if ($time >= $yesterdayStart && $time < $todayStart) {
        return 'Yesterday';
    }

    if ($time >= $weekStart) {
        return 'Earlier this week';
    }

    return 'Older';
}

try {
    $connection = get_database_connection();

    $notifications = [];
    $query = 'SELECT notification_id, name, activity, role, program, division, created_at FROM notifications ORDER BY created_at DESC, notification_id DESC';
    $result = $connection->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'notification_id' => (int) ($row['notification_id'] ?? 0),
                'name' => clean_value($row['name']),
                'activity' => clean_value($row['activity'], 'performed an activity'),
                'role' => clean_value($row['role']),
                'program' => clean_value($row['program']),
                'division' => clean_value($row['division']),
                'created_at' => (string) ($row['created_at'] ?? ''),
                'formatted_created_at' => format_notification_time((string) ($row['created_at'] ?? '')),
            ];
        }
    }

    $now = time();
    $groups = [
        'Today' => [],
        'Yesterday' => [],
        'Earlier this week' => [],
        'Older' => [],
    ];

    foreach ($notifications as $notification) {
        $label = notification_group_label((string) ($notification['created_at'] ?? ''), $now);
        $groups[$label][] = $notification;
    }

    $orderedGroups = [];
    foreach ($groups as $label => $items) {
        if ($items !== []) {
            $orderedGroups[] = [
                'label' => $label,
                'items' => $items,
            ];
        }
    }

    $connection->close();

    echo json_encode([
        'total' => count($notifications),
        'groups' => $orderedGroups,
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'total' => 0,
        'groups' => [],
        'message' => 'Unable to load notifications.',
    ]);
}
