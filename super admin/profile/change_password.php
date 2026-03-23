<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/profile_repository.php';

require_login(['super admin']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    profile_json_response(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput ?: '[]', true);

if (!is_array($payload)) {
    profile_json_response(400, ['success' => false, 'message' => 'Invalid request payload.']);
}

$currentPassword = (string) ($payload['current_password'] ?? '');
$newPassword = (string) ($payload['new_password'] ?? '');
$confirmPassword = (string) ($payload['confirm_password'] ?? '');
$errors = [];

if ($currentPassword === '') {
    $errors['current_password'] = 'Current password is required.';
}

if ($newPassword === '') {
    $errors['new_password'] = 'New password is required.';
} elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $newPassword)) {
    $errors['new_password'] = 'New password must be at least 8 characters and include uppercase, lowercase, and a number.';
}

if ($confirmPassword === '') {
    $errors['confirm_password'] = 'Please confirm your new password.';
} elseif (!hash_equals($newPassword, $confirmPassword)) {
    $errors['confirm_password'] = 'Confirm password must match the new password.';
}

if ($errors !== []) {
    profile_json_response(422, [
        'success' => false,
        'message' => 'Please correct the highlighted fields.',
        'errors' => $errors,
    ]);
}

$username = (string) ($_SESSION['username'] ?? '');

try {
    $connection = get_database_connection();
    $statement = $connection->prepare('SELECT password FROM users WHERE username = ? LIMIT 1');

    if ($statement === false) {
        throw new RuntimeException('Unable to prepare password query.');
    }

    $statement->bind_param('s', $username);
    $statement->execute();
    $user = $statement->get_result()?->fetch_assoc();
    $statement->close();

    if (!$user) {
        profile_json_response(404, ['success' => false, 'message' => 'User account not found.']);
    }

    $storedPassword = (string) ($user['password'] ?? '');
    $isHashedPassword = password_get_info($storedPassword)['algo'] !== null;
    $isValidPassword = $isHashedPassword
        ? password_verify($currentPassword, $storedPassword)
        : hash_equals($storedPassword, $currentPassword);

    if (!$isValidPassword) {
        profile_json_response(422, [
            'success' => false,
            'message' => 'Please correct the highlighted fields.',
            'errors' => ['current_password' => 'Current password is incorrect.'],
        ]);
    }

    if (hash_equals($currentPassword, $newPassword)) {
        profile_json_response(422, [
            'success' => false,
            'message' => 'Please correct the highlighted fields.',
            'errors' => ['new_password' => 'New password must be different from the current password.'],
        ]);
    }

    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $updateStatement = $connection->prepare('UPDATE users SET password = ? WHERE username = ? LIMIT 1');

    if ($updateStatement === false) {
        throw new RuntimeException('Unable to prepare password update query.');
    }

    $updateStatement->bind_param('ss', $passwordHash, $username);
    $updateStatement->execute();
    $updateStatement->close();
    $connection->close();

    profile_json_response(200, [
        'success' => true,
        'message' => 'Password successfully changed.',
    ]);
} catch (Throwable $exception) {
    profile_json_response(500, ['success' => false, 'message' => 'Unable to change password right now.']);
}
