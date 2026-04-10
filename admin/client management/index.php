<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../includes/navigation.php';
require_once __DIR__ . '/../division_scope.php';
require_once __DIR__ . '/../../auth/access_control.php';
require_operational_access();
$adminDivision = scoped_division();
$currentRole = current_user_role();

$pagePath = __DIR__ . '/page.html';
$pageHtml = file_get_contents($pagePath);

if ($pageHtml === false) {
    http_response_code(500);
    exit('Unable to load client management page.');
}

$sidebarMarkup = render_admin_sidebar('clients', '..', $currentRole);
$renderedPage = preg_replace('/<nav class="sidebar" id="sidebar">.*?<\/nav>/s', $sidebarMarkup, $pageHtml, 1);

$resultPage = $renderedPage === null ? $pageHtml : $renderedPage;
$resultPage = str_replace('</body>', '    <script>window.adminDivision = ' . json_encode($adminDivision) . '; window.currentRole = ' . json_encode($currentRole) . '; window.currentProgram = ' . json_encode(current_user_program()) . ';</script>' . PHP_EOL . '</body>', $resultPage);
echo $resultPage;
