<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: /login form/index.php');
    exit;
}

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    header('Location: /login form/index.php?error=missing_credentials');
    exit;
}

try {
    $connection = get_database_connection();
    $statement = $connection->prepare('SELECT full_name, username, password, role, division, programs, statuz FROM users WHERE username = ? LIMIT 1');

    if ($statement === false) {
        throw new RuntimeException('Failed to prepare login statement.');
    }

    $statement->bind_param('s', $username);
    $statement->execute();
    $result = $statement->get_result();
    $user = $result ? $result->fetch_assoc() : null;

    $statement->close();
    $connection->close();
} catch (Throwable $exception) {
    header('Location: /login form/index.php?error=server_error');
    exit;
}

if (!$user) {
    header('Location: /login form/index.php?error=user_not_found');
    exit;
}

if (!is_active_status((string) ($user['statuz'] ?? ''))) {
    header('Location: /login form/index.php?error=account_disabled');
    exit;
}

$storedPassword = (string) ($user['password'] ?? '');

$isHashedPassword = password_get_info($storedPassword)['algo'] !== null;
$isValidPassword = $isHashedPassword
    ? password_verify($password, $storedPassword)
    : hash_equals($storedPassword, $password);

if (!$isValidPassword) {
    header('Location: /login form/index.php?error=invalid_password');
    exit;
}

$shouldUpgradePassword = !$isHashedPassword || password_needs_rehash($storedPassword, PASSWORD_BCRYPT);

if ($shouldUpgradePassword) {
    try {
        $connection = get_database_connection();
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        $updateStatement = $connection->prepare('UPDATE users SET password = ? WHERE username = ?');

        if ($updateStatement) {
            $updateStatement->bind_param('ss', $newHash, $username);
            $updateStatement->execute();
            $updateStatement->close();
        }

        $connection->close();
    } catch (Throwable $exception) {
        // Ignore migration errors without blocking a valid login.
    }
}

$role = normalize_role((string) ($user['role'] ?? ''));
$fullName = trim((string) ($user['full_name'] ?? ''));
$division = normalize_division((string) ($user['division'] ?? ''));
$program = normalize_program((string) ($user['programs'] ?? ''));

if (!in_array($role, RBAC_ALLOWED_ROLES, true)) {
    header('Location: /login form/index.php?error=invalid_role');
    exit;
}

if ($role === 'admin' && !is_supported_division($division)) {
    header('Location: /login form/index.php?error=invalid_division');
    exit;
}

if ($role === 'encoder' && !is_supported_program($division, $program)) {
    header('Location: /login form/index.php?error=invalid_program');
    exit;
}

if ($role === 'super_admin') {
    $division = '';
    $program = '';
}

login_user($fullName, $username, $role, $division, $program);
header('Location: ' . role_home_path($role, $division, $program));
exit;
