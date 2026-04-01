<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../auth/auth.php';
require_once __DIR__ . '/../../../auth/db.php';
require_once __DIR__ . '/../../includes/navigation.php';
require_once __DIR__ . '/../../division_scope.php';

require_login(['admin']);

$adminDivision = admin_scope_division();
$programsByDivision = division_program_map();
$allowedPrograms = $programsByDivision[$adminDivision] ?? [];
$allowedPositions = ['SWO I', 'SWO II', 'SWO III', 'SWO IV'];

$formState = ['status' => '', 'message' => '', 'old' => []];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phoneNumber = trim((string) ($_POST['phone_number'] ?? ''));
    $gender = trim((string) ($_POST['gender'] ?? ''));
    $position = trim((string) ($_POST['position'] ?? ''));
    $age = trim((string) ($_POST['age'] ?? ''));
    $programAssigned = strtoupper(trim((string) ($_POST['program_assigned'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($fullName === '' || $username === '' || $email === '' || $phoneNumber === '' || $gender === '' || $position === '' || $age === '' || $programAssigned === '' || $password === '' || $confirmPassword === '') {
        $formState = ['status' => 'error', 'message' => 'Please complete all fields before submitting.', 'old' => $_POST];
    } elseif (!ctype_digit($age) || (int) $age <= 0 || !in_array($position, $allowedPositions, true) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($programAssigned, $allowedPrograms, true) || !hash_equals($password, $confirmPassword)) {
        $formState = ['status' => 'error', 'message' => 'Please check the provided values and try again.', 'old' => $_POST];
    } else {
        try {
            $connection = get_database_connection();
            $existing = $connection->prepare('SELECT username, email FROM users WHERE username = ? OR email = ? LIMIT 1');
            $existing->bind_param('ss', $username, $email);
            $existing->execute();
            $row = $existing->get_result()->fetch_assoc();
            $existing->close();

            if ($row) {
                $formState = ['status' => 'error', 'message' => 'Username or email already exists.', 'old' => $_POST];
            } else {
                $role = 'encoder';
                $status = 'Offline';
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $ageValue = (int) $age;

                $insert = $connection->prepare('INSERT INTO users (full_name, username, email, phone_number, division, gender, position, age, password, role, programs, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $insert->bind_param('sssssssissss', $fullName, $username, $email, $phoneNumber, $adminDivision, $gender, $position, $ageValue, $hashedPassword, $role, $programAssigned, $status);
                $insert->execute();
                $insert->close();
                $formState = ['status' => 'success', 'message' => 'Encoder account added successfully.', 'old' => []];
            }
            $connection->close();
        } catch (Throwable $exception) {
            $formState = ['status' => 'error', 'message' => 'Unable to save encoder account right now.', 'old' => $_POST];
        }
    }
}

$pageHtml = file_get_contents(__DIR__ . '/page.html');
$sidebarMarkup = render_admin_sidebar('social_workers', '../..');
$renderedPage = preg_replace('/<nav class="sidebar" id="sidebar">.*?<\/nav>/s', $sidebarMarkup, $pageHtml ?: '', 1);
$formState['admin_division'] = $adminDivision;
$formState['allowed_programs'] = $allowedPrograms;
$renderedPage = str_replace('<script src="index.js"></script>', '<script>window.adminRegistrationState=' . json_encode($formState, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ';</script>' . PHP_EOL . '    <script src="index.js"></script>', $renderedPage ?: $pageHtml);
echo $renderedPage;
