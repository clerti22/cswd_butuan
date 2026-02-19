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
    $statement = $connection->prepare('SELECT username, password, role FROM users WHERE username = ? LIMIT 1');

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

$storedPassword = (string) ($user['password'] ?? '');

// Support both bcrypt hashes and legacy plaintext VARCHAR passwords.
$isHashedPassword = password_get_info($storedPassword)['algo'] !== null;
$isValidPassword = $isHashedPassword
    ? password_verify($password, $storedPassword)
    : hash_equals($storedPassword, $password);

if (!$isValidPassword) {
    header('Location: /login form/index.php?error=invalid_password');
    exit;
}

// If account still uses plaintext or an outdated hash, migrate it to bcrypt after successful login.
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

$role = strtolower(trim((string) ($user['role'] ?? '')));
$allowedRoles = ['super admin', 'admin', 'encoder'];

if (!in_array($role, $allowedRoles, true)) {
    header('Location: /login form/index.php?error=invalid_role');
    exit;
}

login_user($username, $role);
header('Location: ' . role_dashboard_path($role));
exit;
