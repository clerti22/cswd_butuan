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

$username = trim((string) ($payload['username'] ?? ''));
$role = strtolower(trim((string) ($payload['role'] ?? '')));

if ($username === '' || !in_array($role, ['admin', 'encoder'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid user details.']);
    exit;
}

try {
    $connection = get_database_connection();

    $columnsResult = $connection->query('SHOW COLUMNS FROM users');
    if (!$columnsResult) {
        throw new RuntimeException('Could not inspect users table.');
    }

    $availableColumns = [];
    while ($column = $columnsResult->fetch_assoc()) {
        $name = (string) ($column['Field'] ?? '');
        if ($name !== '') {
            $availableColumns[$name] = true;
        }
    }

    foreach (['username', 'role', 'statuz'] as $requiredColumn) {
        if (!isset($availableColumns[$requiredColumn])) {
            throw new RuntimeException('Missing users column: ' . $requiredColumn);
        }
    }

    $newStatus = 'Disabled Account';

    $statement = $connection->prepare(
        'UPDATE users SET statuz = ? WHERE username = ? AND LOWER(role) = ? LIMIT 1'
    );

    if (!$statement) {
        throw new RuntimeException('Could not prepare disable account query.');
    }

    $statement->bind_param('sss', $newStatus, $username, $role);
    $statement->execute();

    $affectedRows = $statement->affected_rows;

    $statement->close();
    $connection->close();

    if ($affectedRows < 1) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'User record was not updated.']);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'message' => 'User account disabled successfully.',
        'status' => $newStatus,
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Unable to disable user account right now.']);
}
