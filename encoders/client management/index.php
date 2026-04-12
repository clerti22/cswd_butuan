<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../includes/navigation.php';
require_once __DIR__ . '/../encoder_scope.php';
require_login(['encoder']);
$encoderDivision = encoder_scope_division();
$encoderProgram = encoder_scope_program();

$pagePath = __DIR__ . '/page.html';
$pageHtml = file_get_contents($pagePath);

if ($pageHtml === false) {
    http_response_code(500);
    exit('Unable to load client management page.');
}

$sidebarMarkup = render_encoder_sidebar('clients', '..');
$renderedPage = preg_replace('/<nav class="sidebar" id="sidebar">.*?<\/nav>/s', $sidebarMarkup, $pageHtml, 1);

$resultPage = $renderedPage === null ? $pageHtml : $renderedPage;
$scopeScript = '<script>window.encoderScope = ' . json_encode(['division' => $encoderDivision, 'program' => $encoderProgram]) . ';</script>';
$resultPage = str_replace('<script src="index.js"></script>', $scopeScript . PHP_EOL . '    <script src="index.js"></script>', $resultPage);
echo $resultPage;
