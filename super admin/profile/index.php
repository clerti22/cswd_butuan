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
    'role' => 'Super Admin',
    'email' => '',
];

if ($username !== '') {
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

        $fullNameSelect = isset($columns['full_name'])
            ? 'COALESCE(NULLIF(full_name, ""), username) AS full_name'
            : 'username AS full_name';

        $emailSelect = isset($columns['email'])
            ? 'COALESCE(email, "") AS email'
            : '"" AS email';

        $statement = $connection->prepare(
            "SELECT {$fullNameSelect}, username, role, {$emailSelect} FROM users WHERE username = ? LIMIT 1"
        );

        if ($statement) {
            $statement->bind_param('s', $username);
            $statement->execute();
            $result = $statement->get_result();
            $user = $result ? $result->fetch_assoc() : null;

            if ($user) {
                $profile['name'] = (string) ($user['full_name'] ?? $username);
                $profile['username'] = (string) ($user['username'] ?? $username);
                $profile['role'] = ucwords((string) ($user['role'] ?? 'super admin'));
                $profile['email'] = (string) ($user['email'] ?? '');
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
