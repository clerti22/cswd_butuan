<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../auth/auth.php';
require_once __DIR__ . '/../../../auth/db.php';

require_login(['super admin']);

$formState = [
    'status' => '',
    'message' => '',
    'old' => [
        'full_name' => '',
        'username' => '',
        'email' => '',
        'phone_number' => '',
        'gender' => '',
        'age' => '',
        'role' => '',
        'division' => '',
    ],
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phoneNumber = trim((string) ($_POST['phone_number'] ?? ''));
    $gender = trim((string) ($_POST['gender'] ?? ''));
    $age = trim((string) ($_POST['age'] ?? ''));
    $role = trim((string) ($_POST['role'] ?? ''));
    $division = trim((string) ($_POST['division'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    $formState['old'] = [
        'full_name' => $fullName,
        'username' => $username,
        'email' => $email,
        'phone_number' => $phoneNumber,
        'gender' => $gender,
        'age' => $age,
        'role' => $role,
        'division' => $division,
    ];

    if (
        $fullName === '' ||
        $username === '' ||
        $email === '' ||
        $phoneNumber === '' ||
        $gender === '' ||
        $age === '' ||
        $role === '' ||
        $division === '' ||
        $password === '' ||
        $confirmPassword === ''
    ) {
        $formState['status'] = 'error';
        $formState['message'] = 'Please complete all fields before submitting.';
    } elseif (!ctype_digit($age) || (int) $age <= 0) {
        $formState['status'] = 'error';
        $formState['message'] = 'Please provide a valid age.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formState['status'] = 'error';
        $formState['message'] = 'Please provide a valid email address.';
    } elseif (!in_array(strtoupper($division), ['DPSD', 'PSSD', 'RPSD'], true)) {
        $formState['status'] = 'error';
        $formState['message'] = 'Division assigned must be DPSD, PSSD, or RPSD only.';
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

            $requiredColumns = ['full_name', 'username', 'email', 'phone_number', 'division', 'gender', 'age', 'password', 'role'];

            foreach ($requiredColumns as $requiredColumn) {
                if (!isset($columns[$requiredColumn])) {
                    throw new RuntimeException('The users table is missing the required column: ' . $requiredColumn);
                }
            }

            $existingUserStatement = $connection->prepare('SELECT username, email FROM users WHERE username = ? OR email = ? LIMIT 1');
            if ($existingUserStatement === false) {
                throw new RuntimeException('Failed to prepare username and email check.');
            }

            $existingUserStatement->bind_param('ss', $username, $email);
            $existingUserStatement->execute();
            $existingUserResult = $existingUserStatement->get_result();
            $existingUser = $existingUserResult ? $existingUserResult->fetch_assoc() : null;
            $existingUserStatement->close();

            if ($existingUser && isset($existingUser['email']) && strcasecmp((string) $existingUser['email'], $email) === 0) {
                $formState['status'] = 'error';
                $formState['message'] = 'Email is used.';
            } elseif ($existingUser) {
                $formState['status'] = 'error';
                $formState['message'] = 'Username exist please use different username.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $normalizedRole = strtolower($role);
                $normalizedDivision = strtoupper($division);
                $ageValue = (int) $age;

                $insertStatement = $connection->prepare(
                    'INSERT INTO users (full_name, username, email, phone_number, division, gender, age, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );

                if ($insertStatement === false) {
                    throw new RuntimeException('Failed to prepare user insert statement.');
                }

                $insertStatement->bind_param(
                    'ssssssiss',
                    $fullName,
                    $username,
                    $email,
                    $phoneNumber,
                    $normalizedDivision,
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
                    'email' => '',
                    'phone_number' => '',
                    'gender' => '',
                    'age' => '',
                    'role' => '',
                    'division' => '',
                ];
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
