<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/db.php';

const PROFILE_ALLOWED_GENDERS = ['Male', 'Female', 'Prefer not to say'];

function profile_get_existing_columns(mysqli $connection): array
{
    $columns = [];
    $result = $connection->query('SHOW COLUMNS FROM users');

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $field = strtolower((string) ($row['Field'] ?? ''));
            if ($field !== '') {
                $columns[$field] = true;
            }
        }
        $result->free();
    }

    return $columns;
}

function profile_ensure_optional_columns(mysqli $connection): array
{
    $columns = profile_get_existing_columns($connection);
    $definitions = [
        'age' => 'ALTER TABLE users ADD COLUMN age INT NULL AFTER email_address',
        'gender' => "ALTER TABLE users ADD COLUMN gender VARCHAR(30) NULL AFTER age",
        'phone_number' => 'ALTER TABLE users ADD COLUMN phone_number VARCHAR(30) NULL AFTER gender',
    ];

    foreach ($definitions as $column => $sql) {
        if (!isset($columns[$column])) {
            $connection->query($sql);
        }
    }

    return profile_get_existing_columns($connection);
}

function profile_build_select(array $columns): string
{
    $fullName = isset($columns['full_name'])
        ? 'COALESCE(NULLIF(full_name, ""), username) AS full_name'
        : 'username AS full_name';

    $email = isset($columns['email_address'])
        ? 'COALESCE(email_address, "") AS email'
        : (isset($columns['email']) ? 'COALESCE(email, "") AS email' : '"" AS email');

    $age = isset($columns['age']) ? 'age' : 'NULL AS age';
    $gender = isset($columns['gender']) ? 'COALESCE(gender, "") AS gender' : '"" AS gender';
    $phone = isset($columns['phone_number']) ? 'COALESCE(phone_number, "") AS phone_number' : '"" AS phone_number';

    return "SELECT {$fullName}, username, role, {$email}, {$age}, {$gender}, {$phone} FROM users WHERE username = ? LIMIT 1";
}

function profile_fetch_user(mysqli $connection, string $username): ?array
{
    $columns = profile_get_existing_columns($connection);
    $statement = $connection->prepare(profile_build_select($columns));

    if ($statement === false) {
        throw new RuntimeException('Unable to prepare profile query.');
    }

    $statement->bind_param('s', $username);
    $statement->execute();
    $result = $statement->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $statement->close();

    if (!$user) {
        return null;
    }

    return [
        'full_name' => trim((string) ($user['full_name'] ?? $username)),
        'username' => trim((string) ($user['username'] ?? $username)),
        'role' => ucwords((string) ($user['role'] ?? 'super admin')),
        'email' => trim((string) ($user['email'] ?? '')),
        'age' => $user['age'] === null ? '' : (string) $user['age'],
        'gender' => trim((string) ($user['gender'] ?? '')),
        'phone_number' => trim((string) ($user['phone_number'] ?? '')),
    ];
}

function profile_validate_input(array $input): array
{
    $errors = [];
    $fullName = trim((string) ($input['full_name'] ?? ''));
    $username = trim((string) ($input['username'] ?? ''));
    $email = trim((string) ($input['email'] ?? ''));
    $age = trim((string) ($input['age'] ?? ''));
    $gender = trim((string) ($input['gender'] ?? ''));
    $phoneNumber = trim((string) ($input['phone_number'] ?? ''));

    if ($fullName === '') {
        $errors['full_name'] = 'Full name is required.';
    }

    if ($username === '') {
        $errors['username'] = 'Username is required.';
    } elseif (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
        $errors['username'] = 'Username must be 3-50 characters and may only contain letters, numbers, dots, underscores, and dashes.';
    }

    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if ($age === '') {
        $errors['age'] = 'Age is required.';
    } elseif (filter_var($age, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 130]]) === false) {
        $errors['age'] = 'Age must be a valid number between 1 and 130.';
    }

    if ($gender === '') {
        $errors['gender'] = 'Gender is required.';
    } elseif (!in_array($gender, PROFILE_ALLOWED_GENDERS, true)) {
        $errors['gender'] = 'Please select a valid gender option.';
    }

    if ($phoneNumber === '') {
        $errors['phone_number'] = 'Phone number is required.';
    } else {
        $normalizedPhone = preg_replace('/[\s()+-]+/', '', $phoneNumber);
        if ($normalizedPhone === null || !preg_match('/^\d{10,15}$/', $normalizedPhone)) {
            $errors['phone_number'] = 'Phone number must contain 10 to 15 digits.';
        }
    }

    return $errors;
}

function profile_json_response(int $statusCode, array $payload): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}
