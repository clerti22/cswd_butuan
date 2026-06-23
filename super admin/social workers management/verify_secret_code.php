<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/db.php';

require_login(['super admin']);

header('Content-Type: application/json; charset=utf-8');

$rawInput = file_get_contents('php://input');
$payload = json_decode((string) $rawInput, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['valid' => false, 'message' => 'Invalid request payload.']);
    exit;
}

$code = trim((string) ($payload['code'] ?? ''));

if ($code === '') {
    http_response_code(400);
    echo json_encode(['valid' => false, 'message' => 'Secret code is required.']);
    exit;
}

try {
    $connection = get_database_connection();

    $statement = $connection->prepare('SELECT code FROM secret_code LIMIT 1');
    if (!$statement) {
        throw new RuntimeException('Could not prepare secret code query.');
    }

    $statement->execute();
    $result = $statement->get_result();
    $row = $result->fetch_assoc();
    $statement->close();
    $connection->close();

    if (!$row) {
        http_response_code(500);
        echo json_encode(['valid' => false, 'message' => 'No secret code configured.']);
        exit;
    }

    $storedCode = (string) ($row['code'] ?? '');

    $isValid = str_starts_with($storedCode, '$2y$') ? password_verify($code, $storedCode) : $code === $storedCode;

    if (!$isValid) {
        echo json_encode(['valid' => false, 'message' => 'Incorrect secret code. Please try again.']);
        exit;
    }

    echo json_encode(['valid' => true]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['valid' => false, 'message' => 'Unable to verify secret code right now.']);
}
