<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_login(['admin']);

$context = get_auth_user_context();
$dashboardPath = __DIR__ . '/dashboard.html';
$pageHtml = file_get_contents($dashboardPath);

if ($pageHtml === false) {
    http_response_code(500);
    exit('Unable to load dashboard page.');
}

$divisionLabel = htmlspecialchars(format_division_label($context['division']), ENT_QUOTES, 'UTF-8');
$scopeNotice = '<div style="padding:8px 16px;background:#e7f3ff;color:#0a3d62;font-weight:600;">Division Access: ' . $divisionLabel . '</div>';

if (stripos($pageHtml, '<body>') !== false) {
    $pageHtml = preg_replace('/<body>/', '<body>' . $scopeNotice, $pageHtml, 1) ?? $pageHtml;
}

echo $pageHtml;
