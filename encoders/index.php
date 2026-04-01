<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_login(['encoder']);

$context = get_auth_user_context();
$dashboardPath = __DIR__ . '/dashboard.html';
$pageHtml = file_get_contents($dashboardPath);

if ($pageHtml === false) {
    http_response_code(500);
    exit('Unable to load dashboard page.');
}

$divisionLabel = htmlspecialchars(format_division_label($context['division']), ENT_QUOTES, 'UTF-8');
$programLabel = htmlspecialchars(strtoupper($context['program']), ENT_QUOTES, 'UTF-8');
$scopeNotice = '<div style="padding:8px 16px;background:#fff7e6;color:#7a4b00;font-weight:600;">Program Access: ' . $divisionLabel . ' / ' . $programLabel . '</div>';

if (stripos($pageHtml, '<body>') !== false) {
    $pageHtml = preg_replace('/<body>/', '<body>' . $scopeNotice, $pageHtml, 1) ?? $pageHtml;
}

echo $pageHtml;
