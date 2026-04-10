<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/report_data_service.php';
require_once __DIR__ . '/../encoder_scope.php';

require_login(['encoder']);

try {
    $service = new ReportDataService();
    $records = $service->getAllRecords();

    $filters = [
        'program' => trim((string) ($_GET['program'] ?? '')),
        'barangay' => trim((string) ($_GET['barangay'] ?? '')),
        'year' => trim((string) ($_GET['year'] ?? '')),
        'month' => trim((string) ($_GET['month'] ?? '')),
        'sex' => trim((string) ($_GET['sex'] ?? '')),
        'age_bracket' => trim((string) ($_GET['age_bracket'] ?? '')),
        'sector' => trim((string) ($_GET['sector'] ?? '')),
        'household' => trim((string) ($_GET['household'] ?? '')),
        'date_from' => trim((string) ($_GET['date_from'] ?? '')),
        'date_to' => trim((string) ($_GET['date_to'] ?? '')),
    ];

    $filtered = apply_report_filters($records, $filters);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cswdd_report_' . date('Ymd_His') . '.csv"');

    $output = fopen('php://output', 'wb');
    if ($output === false) {
        throw new RuntimeException('Unable to open output stream.');
    }

    fputcsv($output, ['CSWDD Report']);
    fputcsv($output, ['Generated At', date('Y-m-d H:i:s')]);

    foreach ($filters as $key => $value) {
        if ($value !== '') {
            fputcsv($output, [ucwords(str_replace('_', ' ', $key)), $value]);
        }
    }

    fputcsv($output, []);
    fputcsv($output, ['Control Number', 'Full Name', 'Program', 'Barangay', 'Sex', 'Age', 'Age Bracket', 'Sector', 'Household', 'Interventions', 'Intervention Count', 'Date']);

    foreach ($filtered as $row) {
        fputcsv($output, [
            (string) ($row['control_number'] ?? ''),
            (string) ($row['full_name'] ?? ''),
            (string) ($row['program'] ?? ''),
            (string) ($row['barangay'] ?? ''),
            (string) ($row['sex'] ?? ''),
            (string) ($row['age'] ?? ''),
            (string) ($row['age_bracket'] ?? ''),
            (string) ($row['sector'] ?? ''),
            (string) ($row['household'] ?? ''),
            (string) ($row['interventions'] ?? ''),
            (string) ($row['interventions_count'] ?? ''),
            (string) ($row['created_at'] ?? ''),
        ]);
    }

    fclose($output);
} catch (Throwable $exception) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Unable to export report.';
}
