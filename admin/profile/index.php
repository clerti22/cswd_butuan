<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/db.php';
require_once __DIR__ . '/profile_repository.php';
require_once __DIR__ . '/../includes/navigation.php';
require_once __DIR__ . '/../division_scope.php';
require_once __DIR__ . '/../../auth/access_control.php';

require_operational_access();
$adminDivision = scoped_division();
$currentRole = current_user_role();
start_auth_session();

$username = (string) ($_SESSION['username'] ?? '');
$profile = [
    'full_name' => (string) ($_SESSION['full_name'] ?? ''),
    'username' => $username,
    'role' => $currentRole === 'encoder' ? 'Encoder' : 'Admin',
    'email' => '',
    'age' => '',
    'gender' => '',
    'phone_number' => '',
    'position' => '',
];

if ($username !== '') {
    try {
        $connection = get_database_connection();
        profile_ensure_optional_columns($connection);
        $userProfile = profile_fetch_user($connection, $username);
        if ($userProfile) {
            $profile = $userProfile;
        }
        $connection->close();
    } catch (Throwable $exception) {
        // Keep default safe values when profile data cannot be retrieved.
    }
}

$pagePath = __DIR__ . '/page.html';
$pageHtml = file_get_contents($pagePath);

if ($pageHtml === false) {
    http_response_code(500);
    exit('Unable to load profile page.');
}

$sidebarMarkup = render_admin_sidebar('profile', '..', $currentRole);
$renderedPage = preg_replace('/<nav class="sidebar" id="sidebar">.*?<\/nav>/s', $sidebarMarkup, $pageHtml, 1);

$profileJson = json_encode($profile, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$profileScript = '<script>window.superAdminProfile = ' . $profileJson . ';</script>';
$renderedPage = str_replace('<script src="index.js"></script>', $profileScript . PHP_EOL . '    <script src="index.js"></script>', $renderedPage ?? $pageHtml);

echo $renderedPage;
