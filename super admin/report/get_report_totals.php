<?php
header('Content-Type: application/json');

$tables = [
    'pwud_table',
    'soloparent_info_table',
    'pyap_table',
    'snr_table',
    'pwd_table',
    'car_table',
    'ciu_table',
    'vawc_table',
    'csae_table',
    'cicl_table'
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

$queries = [];
foreach ($tables as $table) {
    $queries[] = "SELECT\n        COUNT(*) AS total_clients,\n        SUM(CASE WHEN YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) THEN 1 ELSE 0 END) AS served_this_week,\n        SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) THEN 1 ELSE 0 END) AS served_this_month,\n        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS served_today\n    FROM {$table}";
}

$sql = 'SELECT\n    SUM(total_clients) AS total_clients,\n    SUM(served_this_week) AS served_this_week,\n    SUM(served_this_month) AS served_this_month,\n    SUM(served_today) AS served_today\nFROM (' . implode(' UNION ALL ', $queries) . ') AS combined_totals';

$result = $connection->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load report totals: ' . $connection->error
    ]);
    $connection->close();
    exit;
}

$data = $result->fetch_assoc();

echo json_encode([
    'success' => true,
    'data' => [
        'total_clients' => (int) ($data['total_clients'] ?? 0),
        'served_this_week' => (int) ($data['served_this_week'] ?? 0),
        'served_this_month' => (int) ($data['served_this_month'] ?? 0),
        'served_today' => (int) ($data['served_today'] ?? 0)
    ]
]);

$connection->close();
