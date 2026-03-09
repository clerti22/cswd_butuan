<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/db.php';

require_login(['super admin']);

$formState = [
    'status' => '',
    'message' => '',
    'old' => [
        'full_name' => '',
        'username' => '',
        'phone_number' => '',
        'email' => '',
        'gender' => '',
        'age' => '',
        'role' => '',
    ],
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $phoneNumber = trim((string) ($_POST['phone_number'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $gender = trim((string) ($_POST['gender'] ?? ''));
    $age = trim((string) ($_POST['age'] ?? ''));
    $role = trim((string) ($_POST['role'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    $formState['old'] = [
        'full_name' => $fullName,
        'username' => $username,
        'phone_number' => $phoneNumber,
        'email' => $email,
        'gender' => $gender,
        'age' => $age,
        'role' => $role,
    ];

    if (
        $fullName === '' ||
        $username === '' ||
        $phoneNumber === '' ||
        $email === '' ||
        $gender === '' ||
        $age === '' ||
        $role === '' ||
        $password === '' ||
        $confirmPassword === ''
    ) {
        $formState['status'] = 'error';
        $formState['message'] = 'Please complete all fields before submitting.';
    } elseif (strpos($email, '@gmail.com') === false) {
        $formState['status'] = 'error';
        $formState['message'] = 'Email must contain @gmail.com.';
    } elseif (!ctype_digit($age) || (int) $age <= 0) {
        $formState['status'] = 'error';
        $formState['message'] = 'Please provide a valid age.';
    } elseif (!in_array(strtolower($role), ['super admin', 'admin', 'encoder'], true)) {
        $formState['status'] = 'error';
        $formState['message'] = 'Role must be super admin, admin, or encoder only.';
    } elseif (!hash_equals($password, $confirmPassword)) {
        $formState['status'] = 'error';
        $formState['message'] = 'Password and confirm password do not match.';
    } else {
        try {
            $connection = get_database_connection();

            $columns = [];
            $columnsResult = $connection->query('SHOW COLUMNS FROM users');

            if ($columnsResult) {
                while ($columnRow = $columnsResult->fetch_assoc()) {
                    $field = strtolower((string) ($columnRow['Field'] ?? ''));
                    if ($field !== '') {
                        $columns[$field] = true;
                    }
                }

                $columnsResult->free();
            }

            $requiredColumns = ['full_name', 'username', 'phone_number', 'email', 'gender', 'age', 'password', 'role'];

            foreach ($requiredColumns as $requiredColumn) {
                if (!isset($columns[$requiredColumn])) {
                    throw new RuntimeException('The users table is missing the required column: ' . $requiredColumn);
                }
            }

            $existingUserStatement = $connection->prepare('SELECT username FROM users WHERE username = ? LIMIT 1');
            if ($existingUserStatement === false) {
                throw new RuntimeException('Failed to prepare username check.');
            }

            $existingUserStatement->bind_param('s', $username);
            $existingUserStatement->execute();
            $existingUserResult = $existingUserStatement->get_result();
            $existingUser = $existingUserResult ? $existingUserResult->fetch_assoc() : null;
            $existingUserStatement->close();

            if ($existingUser) {
                $formState['status'] = 'error';
                $formState['message'] = 'Username exist please use different username.';
            } else {
                $existingEmailStatement = $connection->prepare('SELECT email FROM users WHERE email = ? LIMIT 1');
                if ($existingEmailStatement === false) {
                    throw new RuntimeException('Failed to prepare email check.');
                }

                $existingEmailStatement->bind_param('s', $email);
                $existingEmailStatement->execute();
                $existingEmailResult = $existingEmailStatement->get_result();
                $existingEmail = $existingEmailResult ? $existingEmailResult->fetch_assoc() : null;
                $existingEmailStatement->close();

                if ($existingEmail) {
                    $formState['status'] = 'error';
                    $formState['message'] = 'This email is already used.';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $normalizedRole = strtolower($role);
                    $ageValue = (int) $age;

                    $insertStatement = $connection->prepare(
                        'INSERT INTO users (full_name, username, phone_number, email, gender, age, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                    );

                    if ($insertStatement === false) {
                        throw new RuntimeException('Failed to prepare user insert statement.');
                    }

                    $insertStatement->bind_param(
                        'sssssiss',
                        $fullName,
                        $username,
                        $phoneNumber,
                        $email,
                        $gender,
                        $ageValue,
                        $hashedPassword,
                        $normalizedRole
                    );

                    $insertStatement->execute();
                    $insertStatement->close();

                $formState['status'] = 'success';
                $formState['message'] = 'Admin account has been added successfully.';
                $formState['old'] = [
                    'full_name' => '',
                    'username' => '',
                    'phone_number' => '',
                    'email' => '',
                    'gender' => '',
                    'age' => '',
                    'role' => '',
                ];
                }
            }

            $connection->close();
        } catch (Throwable $exception) {
            $formState['status'] = 'error';
            $formState['message'] = 'Unable to save the admin account at the moment.';
        }
    }
}

$pagePath = __DIR__ . '/page.html';
$pageHtml = file_get_contents($pagePath);

if ($pageHtml === false) {
    http_response_code(500);
    exit('Unable to load social workers management page.');
}

$formStateJson = json_encode($formState, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$formStateScript = '<script>window.adminRegistrationState = ' . $formStateJson . ';</script>';

$renderedPage = str_replace(
    '<script src="index.js"></script>',
    $formStateScript . PHP_EOL . '    <script src="index.js"></script>',
    $pageHtml
);

echo $renderedPage;
