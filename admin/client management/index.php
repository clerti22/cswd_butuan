<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../includes/navigation.php';
require_once __DIR__ . '/../division_scope.php';
require_login(['admin']);
$adminDivision = admin_scope_division();

$pagePath = __DIR__ . '/page.html';
$pageHtml = file_get_contents($pagePath);

if ($pageHtml === false) {
    http_response_code(500);
    exit('Unable to load client management page.');
}

$sidebarMarkup = render_admin_sidebar('clients', '..');
$renderedPage = preg_replace('/<nav class="sidebar" id="sidebar">.*?<\/nav>/s', $sidebarMarkup, $pageHtml, 1);

$resultPage = $renderedPage === null ? $pageHtml : $renderedPage;
$divisionScript = '    <script>window.adminDivision = ' . json_encode($adminDivision) . ';</script>' . PHP_EOL;

if (str_contains($resultPage, '<script src="index.js"></script>')) {
    $resultPage = str_replace('<script src="index.js"></script>', $divisionScript . '    <script src="index.js"></script>', $resultPage);
} else {
    $resultPage = str_replace('</body>', $divisionScript . '</body>', $resultPage);
}

echo $resultPage;
