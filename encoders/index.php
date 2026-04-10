<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/includes/navigation.php';
require_once __DIR__ . '/encoder_scope.php';

require_login(['encoder']);

$division = encoder_scope_division();
$program = encoder_scope_program();

$pageHtml = file_get_contents(__DIR__ . '/dashboard.html');
if ($pageHtml === false) {
    http_response_code(500);
    exit('Unable to load encoder dashboard.');
}

$sidebarMarkup = render_encoder_sidebar('dashboard', '.');
$rendered = preg_replace('/<nav class="sidebar" id="sidebar">.*?<\/nav>/s', $sidebarMarkup, $pageHtml, 1);
$scopeScript = '<script>window.encoderScope = ' . json_encode([
    'division' => $division,
    'program' => $program,
]) . ';</script>';

$result = $rendered ?? $pageHtml;
$result = str_replace('<script src="index.js"></script>', $scopeScript . PHP_EOL . '    <script src="index.js"></script>', $result);

echo $result;
