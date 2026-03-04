<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/db.php';

require_login(['super admin']);

start_auth_session();

$username = (string) ($_SESSION['username'] ?? '');
$profile = [
    'name' => '',
    'username' => $username,
    'role' => '',
    'email' => '',
];

if ($username !== '') {
    try {
        $connection = get_database_connection();
        $statement = $connection->prepare('SELECT COALESCE(full_name, username) AS full_name, username, role, email_address FROM users WHERE username = ? LIMIT 1');

        if ($statement) {
            $statement->bind_param('s', $username);
            $statement->execute();
            $result = $statement->get_result();
            $user = $result ? $result->fetch_assoc() : null;

            if ($user) {
                $profile['name'] = (string) ($user['full_name'] ?? '');
                $profile['username'] = (string) ($user['username'] ?? $username);
                $profile['role'] = (string) ($user['role'] ?? '');
                $profile['email'] = (string) ($user['email_address'] ?? '');
            }

            $statement->close();
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

$profileJson = json_encode($profile, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$profileScript = '<script>window.superAdminProfile = ' . $profileJson . ';</script>';

$renderedPage = str_replace('<script src="index.js"></script>', $profileScript . PHP_EOL . '    <script src="index.js"></script>', $pageHtml);

echo $renderedPage;
