<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/../encoder_scope.php';

require_login(['encoder']);
header('Content-Type: application/json');

$programToTable = [
    'PWUD' => 'pwud_table',
    'PYAP' => 'pyap_table',
    'SOLO PARENT' => 'soloparent_info_table',
    'PWD' => 'pwd_table',
    'PWD-2' => 'pwd_table2',
    'SENIOR CITIZEN' => 'snr_table',
    'CAR' => 'car_table',
    'CICL' => 'cicl_table',
    'CIU' => 'ciu_table',
    'CSAE' => 'csae_table',
    'VAWC' => 'vawc_table',
];

$program = encoder_scope_program();
$table = $programToTable[$program] ?? '';

if ($table === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid encoder program scope.']);
    exit;
}

try {
    $connection = get_database_connection();

    $totals = [];
    $queries = [
        'total_clients' => "SELECT COUNT(*) AS total_clients FROM `{$table}`",
        'served_this_week' => "SELECT COUNT(*) AS served_this_week FROM `{$table}` WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)",
        'served_this_month' => "SELECT COUNT(*) AS served_this_month FROM `{$table}` WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())",
        'served_today' => "SELECT COUNT(*) AS served_today FROM `{$table}` WHERE DATE(created_at) = CURDATE()",
    ];

    foreach ($queries as $key => $sql) {
        $result = $connection->query($sql);
        if (!$result) {
            throw new RuntimeException('Unable to compute totals.');
        }
        $row = $result->fetch_assoc();
        $totals[$key] = (int) ($row[$key] ?? 0);
    }

    $connection->close();
    echo json_encode(['success' => true, 'data' => $totals]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load report totals.']);
}
