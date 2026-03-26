<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/scan_copy_helpers.php';

require_login(['super admin', 'admin']);

header('Content-Type: application/json; charset=utf-8');

$controlNumber = normalize_scan_value((string) ($_GET['client_control_number'] ?? ''));
$program = normalize_scan_value((string) ($_GET['program'] ?? ''));

if ($controlNumber === '' || $program === '') {
    http_response_code(400);
    echo json_encode(['message' => 'Missing required client details.']);
    exit;
}

$tokenPayload = [
    'client_control_number' => $controlNumber,
    'program' => $program,
    'uploaded_by' => (string) ($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Unknown user'),
    'exp' => time() + SCAN_COPY_TOKEN_TTL_SECONDS,
];

try {
    $token = create_scan_copy_token($tokenPayload);
    echo json_encode([
        'mobile_upload_url' => 'mobile_scan_upload.php?token=' . rawurlencode($token),
        'expires_at' => $tokenPayload['exp'],
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['message' => 'Unable to generate scan QR link.']);
}
