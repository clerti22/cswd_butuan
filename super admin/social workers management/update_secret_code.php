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
    echo json_encode(['ok' => false, 'message' => 'Invalid request payload.']);
    exit;
}

$currentCode = trim((string) ($payload['current_code'] ?? ''));
$newCode = trim((string) ($payload['new_code'] ?? ''));
$verifyCode = trim((string) ($payload['verify_code'] ?? ''));

if ($currentCode === '' || $newCode === '' || $verifyCode === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'All fields are required.']);
    exit;
}

if ($newCode !== $verifyCode) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'New secret code and verify secret code do not match.']);
    exit;
}

if (strlen($newCode) < 4) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Secret code must be at least 4 characters long.']);
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

    if (!$row) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'No secret code configured.']);
        exit;
    }

    $storedCode = (string) ($row['code'] ?? '');

    $isValid = str_starts_with($storedCode, '$2y$') ? password_verify($currentCode, $storedCode) : $currentCode === $storedCode;

    if (!$isValid) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'Current secret code is incorrect.']);
        exit;
    }

    $hashedCode = password_hash($newCode, PASSWORD_BCRYPT);

    $updateStatement = $connection->prepare('UPDATE secret_code SET code = ? LIMIT 1');
    if (!$updateStatement) {
        throw new RuntimeException('Could not prepare secret code update.');
    }

    $updateStatement->bind_param('s', $hashedCode);
    $updateStatement->execute();
    $updateStatement->close();
    $connection->close();

    echo json_encode(['ok' => true, 'message' => 'Secret code updated successfully.']);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Unable to update secret code right now.']);
}
