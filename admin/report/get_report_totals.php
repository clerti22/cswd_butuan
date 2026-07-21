<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_login(['admin']);

header('Content-Type: application/json');

$tables = [
    'pwud_table',
    'soloparent_info_table',
    'pyap_table',
    'snr_table',
    'pwd_table',
    'pwd_table2',
    'car_table',
    'ciu_table',
    'vawc_table',
    'csae_table',
    'cicl_table',
    'faced_table'
];

$connection = new mysqli('localhost', 'root', '', 'cswd_db');

if ($connection->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $connection->connect_error
    ]);
    exit;
}

function buildTotalsQuery(array $tables, string $alias, string $condition = ''): string
{
    $parts = [];

    foreach ($tables as $table) {
        $whereClause = $condition !== '' ? ' WHERE ' . $condition : '';
        $parts[] = "(SELECT COUNT(*) FROM {$table}{$whereClause})";
    }

    return 'SELECT (' . implode(' + ', $parts) . ") AS {$alias}";
}

$metricQueries = [
    'total_clients' => buildTotalsQuery($tables, 'total_clients'),
    'served_this_week' => buildTotalsQuery($tables, 'served_this_week', 'YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)'),
    'served_this_month' => buildTotalsQuery($tables, 'served_this_month', 'YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())'),
    'served_today' => buildTotalsQuery($tables, 'served_today', 'DATE(created_at) = CURDATE()')
];

$totals = [];

foreach ($metricQueries as $key => $sql) {
    $result = $connection->query($sql);

    if (!$result) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => "Failed to load {$key}: " . $connection->error
        ]);
        $connection->close();
        exit;
    }

    $row = $result->fetch_assoc();
    $totals[$key] = (int) ($row[$key] ?? 0);
}

echo json_encode([
    'success' => true,
    'data' => $totals
]);

$connection->close();
