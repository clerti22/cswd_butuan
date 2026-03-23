<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
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

$sanitizedInput = [
    'full_name' => trim((string) ($payload['full_name'] ?? '')),
    'username' => trim((string) ($payload['username'] ?? '')),
    'email' => trim((string) ($payload['email'] ?? '')),
    'age' => trim((string) ($payload['age'] ?? '')),
    'gender' => trim((string) ($payload['gender'] ?? '')),
    'phone_number' => trim((string) ($payload['phone_number'] ?? '')),
];

$errors = profile_validate_input($sanitizedInput);
if ($errors !== []) {
    profile_json_response(422, [
        'success' => false,
        'message' => 'Please correct the highlighted fields.',
        'errors' => $errors,
    ]);
}

$currentUsername = (string) ($_SESSION['username'] ?? '');

try {
    $connection = get_database_connection();
    profile_ensure_optional_columns($connection);

    $usernameCheck = $connection->prepare('SELECT id FROM users WHERE username = ? AND username <> ? LIMIT 1');
    if ($usernameCheck === false) {
        throw new RuntimeException('Unable to prepare username validation query.');
    }

    $usernameCheck->bind_param('ss', $sanitizedInput['username'], $currentUsername);
    $usernameCheck->execute();
    $existingUser = $usernameCheck->get_result()?->fetch_assoc();
    $usernameCheck->close();

    if ($existingUser) {
        profile_json_response(422, [
            'success' => false,
            'message' => 'Please correct the highlighted fields.',
            'errors' => ['username' => 'That username is already in use.'],
        ]);
    }

    $updateStatement = $connection->prepare(
        'UPDATE users SET full_name = ?, username = ?, email_address = ?, age = ?, gender = ?, phone_number = ? WHERE username = ? LIMIT 1'
    );

    if ($updateStatement === false) {
        throw new RuntimeException('Unable to prepare profile update query.');
    }

    $age = (int) $sanitizedInput['age'];
    $updateStatement->bind_param(
        'sssisss',
        $sanitizedInput['full_name'],
        $sanitizedInput['username'],
        $sanitizedInput['email'],
        $age,
        $sanitizedInput['gender'],
        $sanitizedInput['phone_number'],
        $currentUsername
    );
    $updateStatement->execute();
    $updateStatement->close();

    $updatedProfile = profile_fetch_user($connection, $sanitizedInput['username']);
    $connection->close();

    if (!$updatedProfile) {
        throw new RuntimeException('Unable to load updated profile.');
    }

    $_SESSION['username'] = $updatedProfile['username'];
    $_SESSION['full_name'] = $updatedProfile['full_name'];

    profile_json_response(200, [
        'success' => true,
        'message' => 'Profile updated successfully.',
        'profile' => $updatedProfile,
    ]);
} catch (Throwable $exception) {
    profile_json_response(500, ['success' => false, 'message' => 'Unable to update profile right now.']);
}
