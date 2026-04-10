<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../admin/includes/navigation.php';

require_login(['encoder']);

$division = current_user_division();
$role = 'encoder';

$pageHtml = file_get_contents(__DIR__ . '/dashboard.html');
if ($pageHtml === false) {
    http_response_code(500);
    exit('Unable to load encoder dashboard.');
}

$sidebarMarkup = render_admin_sidebar('dashboard', '../admin', $role);
$rendered = preg_replace('/<nav class="sidebar" id="sidebar">.*?<\/nav>/s', $sidebarMarkup, $pageHtml, 1);
$script = '<script>window.adminDivision = ' . json_encode($division) . '; window.currentRole = ' . json_encode($role) . '; window.currentProgram = ' . json_encode(current_user_program()) . ';</script>';
$rendered = str_replace('<script src="index.js"></script>', $script . PHP_EOL . '  <script src="index.js"></script>', $rendered ?? $pageHtml);

echo $rendered;
