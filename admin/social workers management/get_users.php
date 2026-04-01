<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/../division_scope.php';

require_login(['admin']);
header('Content-Type: application/json; charset=utf-8');

$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$search = trim((string) ($_GET['search'] ?? ''));
$searchPattern = '%' . strtolower($search) . '%';
$division = admin_scope_division();

try {
    $connection = get_database_connection();

    $countSql = 'SELECT COUNT(*) AS total_users FROM users WHERE LOWER(role) = ? AND UPPER(COALESCE(division, "")) = ?';
    if ($search !== '') {
        $countSql .= " AND LOWER(COALESCE(full_name, '')) LIKE ?";
    }

    $countStmt = $connection->prepare($countSql);
    $role = 'encoder';
    if ($search !== '') {
        $countStmt->bind_param('sss', $role, $division, $searchPattern);
    } else {
        $countStmt->bind_param('ss', $role, $division);
    }
    $countStmt->execute();
    $totalUsers = (int) (($countStmt->get_result()->fetch_assoc()['total_users'] ?? 0));
    $countStmt->close();

    $totalPages = max(1, (int) ceil($totalUsers / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $sql = 'SELECT full_name, username, email, role, programs AS assignment, age, phone_number, gender, position, statuz
            FROM users
            WHERE LOWER(role) = ? AND UPPER(COALESCE(division, "")) = ?';
    if ($search !== '') {
        $sql .= " AND LOWER(COALESCE(full_name, '')) LIKE ?";
    }
    $sql .= ' ORDER BY full_name ASC LIMIT ? OFFSET ?';

    $stmt = $connection->prepare($sql);
    if ($search !== '') {
        $stmt->bind_param('sssii', $role, $division, $searchPattern, $perPage, $offset);
    } else {
        $stmt->bind_param('ssii', $role, $division, $perPage, $offset);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'full_name' => (string) ($row['full_name'] ?? ''),
            'username' => (string) ($row['username'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'role' => ucfirst((string) ($row['role'] ?? '')),
            'assignment' => (string) ($row['assignment'] ?? ''),
            'age' => (string) ($row['age'] ?? ''),
            'phone_number' => (string) ($row['phone_number'] ?? ''),
            'gender' => (string) ($row['gender'] ?? ''),
            'position' => (string) ($row['position'] ?? ''),
            'statuz' => (string) ($row['statuz'] ?? ''),
        ];
    }
    $stmt->close();
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
    echo json_encode(['users' => [], 'message' => 'Unable to fetch encoder records.']);
}
