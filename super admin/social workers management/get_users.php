<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/db.php';

require_login(['super admin']);

header('Content-Type: application/json; charset=utf-8');

$role = strtolower(trim((string) ($_GET['role'] ?? '')));
$allowedRoles = ['admin', 'encoder'];

if (!in_array($role, $allowedRoles, true)) {
    http_response_code(400);
    echo json_encode(['users' => [], 'message' => 'Invalid role filter.']);
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

    $requiredColumns = ['full_name', 'role', 'programs', 'statuz'];
    foreach ($requiredColumns as $requiredColumn) {
        if (!isset($availableColumns[$requiredColumn])) {
            throw new RuntimeException('Missing users column: ' . $requiredColumn);
        }
    }

    $statement = $connection->prepare(
        'SELECT full_name, role, programs, statuz FROM users WHERE LOWER(role) = ? ORDER BY full_name ASC'
    );

    if (!$statement) {
        throw new RuntimeException('Could not prepare users query.');
    }

    $statement->bind_param('s', $role);
    $statement->execute();
    $result = $statement->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'full_name' => (string) ($row['full_name'] ?? ''),
            'role' => ucfirst((string) ($row['role'] ?? '')),
            'programs' => (string) ($row['program'] ?? ''),
            'statuz' => (string) ($row['statuz'] ?? ''),
        ];
    }

    $statement->close();
    $connection->close();

    echo json_encode(['users' => $users]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['users' => [], 'message' => 'Unable to fetch users.']);
}
