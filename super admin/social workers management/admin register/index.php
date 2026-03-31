<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../auth/auth.php';
require_once __DIR__ . '/../../../auth/db.php';
require_once __DIR__ . '/../../includes/navigation.php';

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
        'position' => '',
        'age' => '',
        'role' => '',
        'division' => '',
        'program_assigned' => '',
    ],
];

$encoderProgramsByDivision = [
    'DPSD' => ['PWUD', 'Solo Parent', 'PYAP'],
    'PSSD' => ['PWD', 'Senior Citizen'],
    'RPSD' => ['VAWC', 'CSAE', 'CICL', 'CAR', 'CIU'],
];
$allowedPositions = ['SWO I', 'SWO II', 'SWO III', 'SWO IV'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phoneNumber = trim((string) ($_POST['phone_number'] ?? ''));
    $gender = trim((string) ($_POST['gender'] ?? ''));
    $position = trim((string) ($_POST['position'] ?? ''));
    $age = trim((string) ($_POST['age'] ?? ''));
    $role = strtolower(trim((string) ($_POST['role'] ?? '')));
    $division = strtoupper(trim((string) ($_POST['division'] ?? '')));
    $programAssigned = trim((string) ($_POST['program_assigned'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    $formState['old'] = [
        'full_name' => $fullName,
        'username' => $username,
        'email' => $email,
        'phone_number' => $phoneNumber,
        'gender' => $gender,
        'position' => $position,
        'age' => $age,
        'role' => $role,
        'division' => $division,
        'program_assigned' => $programAssigned,
    ];

    if (
        $fullName === '' ||
        $username === '' ||
        $email === '' ||
        $phoneNumber === '' ||
        $gender === '' ||
        $position === '' ||
        $age === '' ||
        $role === '' ||
        $password === '' ||
        $confirmPassword === ''
    ) {
        $formState['status'] = 'error';
        $formState['message'] = 'Please complete all fields before submitting.';
    } elseif (!ctype_digit($age) || (int) $age <= 0) {
        $formState['status'] = 'error';
        $formState['message'] = 'Please provide a valid age.';
    } elseif ($position === '') {
        $formState['status'] = 'error';
        $formState['message'] = 'Position is required.';
    } elseif (!in_array($position, $allowedPositions, true)) {
        $formState['status'] = 'error';
        $formState['message'] = 'Position must be SWO I, SWO II, SWO III, or SWO IV.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formState['status'] = 'error';
        $formState['message'] = 'Please provide a valid email address.';
    } elseif (!in_array($role, ['super admin', 'admin', 'encoder'], true)) {
        $formState['status'] = 'error';
        $formState['message'] = 'Role must be super admin, admin, or encoder only.';
    } elseif ($role !== 'super admin' && !in_array($division, ['DPSD', 'PSSD', 'RPSD'], true)) {
        $formState['status'] = 'error';
        $formState['message'] = 'Division must be DPSD, PSSD, or RPSD only.';
    } elseif ($role === 'super admin' && ($division !== '' || $programAssigned !== '')) {
        $formState['status'] = 'error';
        $formState['message'] = 'Super admin accounts do not require division or program assignment.';
    } elseif ($role === 'admin' && $division === '') {
        $formState['status'] = 'error';
        $formState['message'] = 'Division assigned is required for admin accounts.';
    } elseif ($role === 'admin' && $programAssigned !== '') {
        $formState['status'] = 'error';
        $formState['message'] = 'Program assigned is only available for encoder accounts.';
    } elseif ($role === 'encoder' && $division === '') {
        $formState['status'] = 'error';
        $formState['message'] = 'Division is required for encoder accounts.';
    } elseif ($role === 'encoder' && $programAssigned === '') {
        $formState['status'] = 'error';
        $formState['message'] = 'Program assigned is required for encoder accounts.';
    } elseif ($role === 'encoder' && !in_array($programAssigned, $encoderProgramsByDivision[$division] ?? [], true)) {
        $formState['status'] = 'error';
        $formState['message'] = 'Program assigned is invalid for the selected encoder division.';
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

            $requiredColumns = ['full_name', 'username', 'email', 'phone_number', 'division', 'gender', 'position', 'age', 'password', 'role'];

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
                $ageValue = (int) $age;
                $normalizedDivision = $role === 'super admin' ? '' : $division;
                $programValue = $role === 'encoder' ? $programAssigned : '';
                $statusValue = 'Offline';

                $columnsForInsert = ['full_name', 'username', 'email', 'phone_number', 'division', 'gender', 'position', 'age', 'password', 'role'];
                $placeholders = ['?', '?', '?', '?', '?', '?', '?', '?', '?', '?'];
                $types = 'sssssssiss';
                $values = [
                    $fullName,
                    $username,
                    $email,
                    $phoneNumber,
                    $normalizedDivision,
                    $gender,
                    $position,
                    $ageValue,
                    $hashedPassword,
                    $role,
                ];

                if (isset($columns['programs'])) {
                    $columnsForInsert[] = 'programs';
                    $placeholders[] = '?';
                    $types .= 's';
                    $values[] = $programValue;
                }

                if (isset($columns['status'])) {
                    $columnsForInsert[] = 'status';
                    $placeholders[] = '?';
                    $types .= 's';
                    $values[] = $statusValue;
                }

                $insertSql = sprintf(
                    'INSERT INTO users (%s) VALUES (%s)',
                    implode(', ', $columnsForInsert),
                    implode(', ', $placeholders)
                );

                $insertStatement = $connection->prepare($insertSql);

                if ($insertStatement === false) {
                    throw new RuntimeException('Failed to prepare user insert statement.');
                }

                $insertStatement->bind_param($types, ...$values);
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
                    'position' => '',
                    'age' => '',
                    'role' => '',
                    'division' => '',
                    'program_assigned' => '',
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

$sidebarMarkup = render_super_admin_sidebar('social_workers', '../..');
$renderedPage = preg_replace('/<nav class="sidebar" id="sidebar">.*?<\/nav>/s', $sidebarMarkup, $pageHtml, 1);

$formStateJson = json_encode($formState, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$formStateScript = '<script>window.adminRegistrationState = ' . $formStateJson . ';</script>';

$renderedPage = str_replace(
    '<script src="index.js"></script>',
    $formStateScript . PHP_EOL . '    <script src="index.js"></script>',
    $renderedPage ?? $pageHtml
);

echo $renderedPage;
