<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/includes/navigation.php';
require_login(['admin']);

$division = current_user_division();
if (!in_array($division, ['DPSD', 'PSSD', 'RPSD'], true)) {
    header('Location: /login form/index.php?error=invalid_division');
    exit;
}

$pageHtml = file_get_contents(__DIR__ . '/dashboard.html');
if ($pageHtml === false) {
    http_response_code(500);
    exit('Unable to load admin dashboard.');
}

$sidebarMarkup = render_admin_sidebar('dashboard', '.');
$rendered = preg_replace('/<nav class="sidebar" id="sidebar">.*?<\/nav>/s', $sidebarMarkup, $pageHtml, 1);
$script = '<script>window.adminDivision = ' . json_encode($division) . ';</script>';
$rendered = str_replace('<script src="index.js"></script>', $script . PHP_EOL . '    <script src="index.js"></script>', $rendered ?? $pageHtml);

echo $rendered;
