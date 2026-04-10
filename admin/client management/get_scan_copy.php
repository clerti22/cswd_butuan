<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/access_control.php';
require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/scan_copy_helpers.php';

require_login(['super admin', 'admin', 'encoder']);
header('Content-Type: application/json; charset=utf-8');

$controlNumber = normalize_scan_value((string) ($_GET['client_control_number'] ?? ''));
$program = normalize_scan_value((string) ($_GET['program'] ?? ''));

if ($controlNumber === '' || $program === '') {
    http_response_code(400);
    echo json_encode(['message' => 'Missing required client details.']);
    exit;
}

if (current_user_role() === 'encoder' && !is_program_in_scope($program)) {
    http_response_code(403);
    echo json_encode(['message' => 'Access denied for this program.']);
    exit;
}

try {
    $connection = get_database_connection();
    ensure_client_scans_table($connection);

    $scan = get_latest_client_scan($connection, $controlNumber, $program);
    $connection->close();

    echo json_encode([
        'has_scan' => $scan !== null,
        'scan' => $scan,
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['message' => 'Unable to fetch scan copy.']);
}
