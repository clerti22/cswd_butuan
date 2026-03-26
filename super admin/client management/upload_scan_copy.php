<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/scan_copy_helpers.php';

header('Content-Type: application/json; charset=utf-8');

$token = (string) ($_POST['token'] ?? '');
$payload = verify_scan_copy_token($token);

if (!$payload) {
    http_response_code(401);
    echo json_encode(['message' => 'Invalid or expired upload session. Please generate a new QR code.']);
    exit;
}

if (!isset($_FILES['scan_image']) || !is_array($_FILES['scan_image'])) {
    http_response_code(400);
    echo json_encode(['message' => 'No image uploaded.']);
    exit;
}

$file = $_FILES['scan_image'];
if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['message' => 'Failed to receive uploaded image.']);
    exit;
}

$tmpPath = (string) ($file['tmp_name'] ?? '');
$size = (int) ($file['size'] ?? 0);
if ($tmpPath === '' || $size <= 0 || $size > 8 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid image file.']);
    exit;
}

$mimeType = mime_content_type($tmpPath);
$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

if (!isset($allowedMimeTypes[$mimeType])) {
    http_response_code(400);
    echo json_encode(['message' => 'Only JPG, PNG, or WEBP images are allowed.']);
    exit;
}

$controlNumber = normalize_scan_value((string) ($payload['client_control_number'] ?? ''));
$program = normalize_scan_value((string) ($payload['program'] ?? ''));
$uploadedBy = normalize_scan_value((string) ($payload['uploaded_by'] ?? 'Unknown user'));

if ($controlNumber === '' || $program === '') {
    http_response_code(400);
    echo json_encode(['message' => 'Missing client upload details.']);
    exit;
}

$uploadDir = __DIR__ . '/uploads/client_scans';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
    http_response_code(500);
    echo json_encode(['message' => 'Unable to prepare upload directory.']);
    exit;
}

$extension = $allowedMimeTypes[$mimeType];
$sanitizedControl = preg_replace('/[^A-Za-z0-9_-]/', '_', $controlNumber) ?: 'client';
$sanitizedProgram = preg_replace('/[^A-Za-z0-9_-]/', '_', $program) ?: 'program';
$fileName = sprintf('%s_%s_%s.%s', $sanitizedControl, $sanitizedProgram, date('Ymd_His'), $extension);
$destinationPath = $uploadDir . '/' . $fileName;

if (!move_uploaded_file($tmpPath, $destinationPath)) {
    http_response_code(500);
    echo json_encode(['message' => 'Unable to save uploaded image.']);
    exit;
}

$relativePath = 'uploads/client_scans/' . $fileName;

try {
    $connection = get_database_connection();
    ensure_client_scans_table($connection);

    $existing = get_latest_client_scan($connection, $controlNumber, $program);

    if ($existing) {
        $update = $connection->prepare(
            'UPDATE client_scans SET image_path = ?, uploaded_by = ?, uploaded_at = CURRENT_TIMESTAMP WHERE client_control_number = ? AND program = ?'
        );
        if (!$update) {
            throw new RuntimeException('Unable to prepare update query.');
        }
        $update->bind_param('ssss', $relativePath, $uploadedBy, $controlNumber, $program);
        if (!$update->execute()) {
            $update->close();
            throw new RuntimeException('Unable to update scan copy.');
        }
        $update->close();

        if (!empty($existing['image_path'])) {
            $oldPath = __DIR__ . '/' . ltrim((string) $existing['image_path'], '/');
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }
    } else {
        $insert = $connection->prepare(
            'INSERT INTO client_scans (client_control_number, program, image_path, uploaded_by) VALUES (?, ?, ?, ?)'
        );
        if (!$insert) {
            throw new RuntimeException('Unable to prepare insert query.');
        }
        $insert->bind_param('ssss', $controlNumber, $program, $relativePath, $uploadedBy);
        if (!$insert->execute()) {
            $insert->close();
            throw new RuntimeException('Unable to save scan copy.');
        }
        $insert->close();
    }

    $savedScan = get_latest_client_scan($connection, $controlNumber, $program);
    $connection->close();

    echo json_encode([
        'ok' => true,
        'message' => 'Scan copy uploaded successfully.',
        'scan' => $savedScan,
    ]);
} catch (Throwable $exception) {
    if (is_file($destinationPath)) {
        @unlink($destinationPath);
    }
    http_response_code(500);
    echo json_encode(['message' => 'Unable to save scan copy right now.']);
}
