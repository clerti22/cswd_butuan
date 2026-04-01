<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/includes/navigation.php';
require_login(['super_admin']);

$pagePath = __DIR__ . '/dashboard.html';
$pageHtml = file_get_contents($pagePath);

if ($pageHtml === false) {
    http_response_code(500);
    exit('Unable to load dashboard page.');
}

$sidebarMarkup = render_super_admin_sidebar('dashboard', '.');
$renderedPage = preg_replace('/<nav class="sidebar" id="sidebar">.*?<\/nav>/s', $sidebarMarkup, $pageHtml, 1);

echo $renderedPage === null ? $pageHtml : $renderedPage;
