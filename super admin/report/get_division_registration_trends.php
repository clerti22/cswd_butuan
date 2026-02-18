<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_login(['super admin']);

header('Content-Type: application/json');

$divisionTables = [
    'DPSD' => ['pyap_table', 'soloparent_info_table', 'pwud_table'],
    'PSSD' => ['pwd_table', 'snr_table'],
    'RPSD' => ['car_table', 'cicl_table', 'ciu_table', 'csae_table', 'vawc_table']
];

$days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
$period = strtolower(trim((string) ($_GET['period'] ?? 'week')));
$reference = trim((string) ($_GET['reference'] ?? ''));
$divisionFilter = strtoupper(trim((string) ($_GET['division'] ?? 'ALL')));

if (!in_array($period, ['week', 'month', 'year'], true)) {
    $period = 'week';
}

if ($divisionFilter !== 'ALL' && !array_key_exists($divisionFilter, $divisionTables)) {
    $divisionFilter = 'ALL';
}

$connection = new mysqli('localhost', 'root', '', 'cswd_db');

if ($connection->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $connection->connect_error
    ]);
    exit;
}

function getWeekRange(string $reference): array
{
    $date = $reference !== '' ? DateTime::createFromFormat('Y-m-d', $reference) : false;
    if (!$date) {
        $date = new DateTime('now');
    }

    $start = clone $date;
    $start->modify('sunday this week')->setTime(0, 0, 0);

    $end = clone $start;
    $end->modify('+6 days')->setTime(23, 59, 59);

    return [$start, $end];
}

function buildTimeCondition(string $period, string $reference, ?string &$titleSuffix): string
{
    if ($period === 'month') {
        $monthValue = preg_match('/^\d{4}-\d{2}$/', $reference) ? $reference : date('Y-m');
        [$year, $month] = explode('-', $monthValue);
        $yearNumber = (int) $year;
        $monthNumber = (int) $month;

        $monthDate = DateTime::createFromFormat('!Y-n', $yearNumber . '-' . $monthNumber);
        $titleSuffix = $monthDate ? $monthDate->format('F Y') : $monthValue;

        return sprintf('YEAR(created_at) = %d AND MONTH(created_at) = %d', $yearNumber, $monthNumber);
    }

    if ($period === 'year') {
        $yearNumber = preg_match('/^\d{4}$/', $reference) ? (int) $reference : (int) date('Y');
        $titleSuffix = (string) $yearNumber;

        return sprintf('YEAR(created_at) = %d', $yearNumber);
    }

    [$start, $end] = getWeekRange($reference);
    $titleSuffix = $start->format('M j') . ' - ' . $end->format('j, Y');

    return sprintf(
        "created_at >= '%s' AND created_at <= '%s'",
        $start->format('Y-m-d H:i:s'),
        $end->format('Y-m-d H:i:s')
    );
}

$titleSuffix = null;
$timeCondition = buildTimeCondition($period, $reference, $titleSuffix);

$selectedDivisions = $divisionFilter === 'ALL'
    ? array_keys($divisionTables)
    : [$divisionFilter];

$divisionCounts = [];
foreach ($selectedDivisions as $division) {
    $divisionCounts[$division] = array_fill(0, 7, 0);

    foreach ($divisionTables[$division] as $table) {
        $sql = "SELECT DAYOFWEEK(created_at) AS weekday_index, COUNT(*) AS total FROM {$table} WHERE {$timeCondition} GROUP BY DAYOFWEEK(created_at)";
        $result = $connection->query($sql);

        if (!$result) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => "Failed to load trend data from {$table}: " . $connection->error
            ]);
            $connection->close();
            exit;
        }

        while ($row = $result->fetch_assoc()) {
            $weekday = (int) ($row['weekday_index'] ?? 0);
            $count = (int) ($row['total'] ?? 0);

            if ($weekday >= 1 && $weekday <= 7) {
                $divisionCounts[$division][$weekday - 1] += $count;
            }
        }
    }
}

$titlePrefix = ucfirst($period) . ' Client Registration';
$title = $titlePrefix . ($titleSuffix ? " ({$titleSuffix})" : '');

$connection->close();

echo json_encode([
    'success' => true,
    'data' => [
        'title' => $title,
        'period' => $period,
        'days' => $days,
        'divisions' => $divisionCounts
    ]
]);
