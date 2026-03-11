<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/db.php';

require_login(['super admin']);

header('Content-Type: application/json; charset=utf-8');

$role = strtolower(trim((string) ($_GET['role'] ?? '')));
$allowedRoles = ['admin', 'encoder'];
$perPage = 10;
$page = (int) ($_GET['page'] ?? 1);
if ($page < 1) {
    $page = 1;
}

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

    $baseColumns = ['full_name', 'role', 'statuz'];
    foreach ($baseColumns as $requiredColumn) {
        if (!isset($availableColumns[$requiredColumn])) {
            throw new RuntimeException('Missing users column: ' . $requiredColumn);
        }
    }

    $assignmentColumn = $role === 'admin' ? 'division' : 'programs';
    if (!isset($availableColumns[$assignmentColumn])) {
        throw new RuntimeException('Missing users column: ' . $assignmentColumn);
    }

    $countStatement = $connection->prepare('SELECT COUNT(*) AS total_users FROM users WHERE LOWER(role) = ?');
    if (!$countStatement) {
        throw new RuntimeException('Could not prepare users count query.');
    }

    $countStatement->bind_param('s', $role);
    $countStatement->execute();
    $countResult = $countStatement->get_result();
    $countRow = $countResult ? $countResult->fetch_assoc() : null;
    $totalUsers = (int) ($countRow['total_users'] ?? 0);
    $countStatement->close();

    $totalPages = max(1, (int) ceil($totalUsers / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }

    $offset = ($page - 1) * $perPage;

    $statement = $connection->prepare(
        "SELECT full_name, role, {$assignmentColumn} AS assignment, statuz FROM users WHERE LOWER(role) = ? ORDER BY full_name ASC LIMIT ? OFFSET ?"
    );

    if (!$statement) {
        throw new RuntimeException('Could not prepare users query.');
    }

    $statement->bind_param('sii', $role, $perPage, $offset);
    $statement->execute();
    $result = $statement->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'full_name' => (string) ($row['full_name'] ?? ''),
            'role' => ucfirst((string) ($row['role'] ?? '')),
            'assignment' => (string) ($row['assignment'] ?? ''),
            'statuz' => (string) ($row['statuz'] ?? ''),
        ];
    }

    $statement->close();
    $connection->close();

    echo json_encode([
        'users' => $users,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total_users' => $totalUsers,
            'total_pages' => $totalPages,
        ],
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['users' => [], 'message' => 'Unable to fetch users.']);
}
