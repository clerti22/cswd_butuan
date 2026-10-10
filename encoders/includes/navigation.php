<?php

declare(strict_types=1);

function encoder_sidebar_path(string $basePrefix, string $path): string
{
    $normalizedBase = rtrim($basePrefix, '/');

    if ($normalizedBase === '' || $normalizedBase === '.') {
        return './' . ltrim($path, '/');
    }

    return $normalizedBase . '/' . ltrim($path, '/');
}

function render_encoder_sidebar(string $activeSection, string $basePrefix = '.'): string
{
    $items = [
        'dashboard' => [
            'label' => 'Dashboard',
            'href' => 'index.php',
            'icon' => <<<'HTML'
<svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
  <path d="M0 0h4v6H0zm6 0h4v10H6zm6 0h4v14h-4zM0 8h4v8H0zm6 12h4V12H6zm6-4h4v4h-4z"/>
</svg>
HTML,
        ],
        'clients' => [
            'label' => 'Clients Management',
            'href' => 'client management/index.php',
            'icon' => <<<'HTML'
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people nav-icon" viewBox="0 0 16 16">
  <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4" />
</svg>
HTML,
        ],
        'report' => [
            'label' => 'Report',
            'href' => 'report/index.php',
            'icon' => <<<'HTML'
<svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
  <path fill="#ffffff" d="M12 6a1 1 0 0 0-1 1v10a1 1 0 0 0 2 0V7a1 1 0 0 0-1-1Zm-5 6a1 1 0 0 0-1 1v4a1 1 0 0 0 2 0v-4a1 1 0 0 0-1-1Zm10-2a1 1 0 0 0-1 1v6a1 1 0 0 0 2 0v-6a1 1 0 0 0-1-1Zm2-8H5a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V5a3 3 0 0 0-3-3Zm1 17a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1Z"/>
</svg>
HTML,
        ],
        'profile' => [
            'label' => 'View Profile',
            'href' => 'profile/index.php',
            'icon' => <<<'HTML'
<svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
  <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0" />
  <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1" />
</svg>
HTML,
        ],
    ];

    $logoutPath = encoder_sidebar_path($basePrefix, '../auth/logout.php');
    $logoutModalStylesPath = encoder_sidebar_path($basePrefix, 'includes/logout-confirmation.css');
    $logoutModalScriptPath = encoder_sidebar_path($basePrefix, 'includes/logout-confirmation.js');

    ob_start();
    ?>
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="<?= htmlspecialchars(encoder_sidebar_path($basePrefix, 'images-logo/cswd-logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="" width="130" height="110">
        <p class="sidebar-title">CCIMS Dashboard</p>
        <button class="close-btn" id="closeSidebar">✕</button>
    </div>
    <div class="nav-menu">
        <?php foreach ($items as $key => $item): ?>
            <a
                href="<?= htmlspecialchars(encoder_sidebar_path($basePrefix, $item['href']), ENT_QUOTES, 'UTF-8') ?>"
                class="nav-item<?= $key === $activeSection ? ' active' : '' ?>"
                <?= $key === $activeSection ? 'style="color: #fff; background-color: rgba(255, 255, 255, 0.16); box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.18);" aria-current="page"' : '' ?>
            >
                <?= $item['icon'] ?? '' ?>
                <span class="nav-text"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="sidebar-footer">
        <a
            href="<?= htmlspecialchars($logoutPath, ENT_QUOTES, 'UTF-8') ?>"
            class="nav-item"
            id="logoutTrigger"
            data-logout-confirm="true"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-door-open nav-icon" viewBox="0 0 16 16">
                <path d="M8.5 10c-.276 0-.5-.448-.5-1s.224-1 .5-1 .5.448.5 1-.224 1-.5 1" />
                <path d="M10.828.122A.5.5 0 0 1 11 .5V1h.5A1.5 1.5 0 0 1 13 2.5V15h1.5a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1H3V1.5a.5.5 0 0 1 .43-.495l7-1a.5.5 0 0 1 .398.117M11.5 2H11v13h1V2.5a.5.5 0 0 0-.5-.5M4 1.934V15h6V1.077z" />
            </svg>
            <span class="nav-text">Logout</span>
        </a>
    </div>
</nav>
<link rel="stylesheet" href="<?= htmlspecialchars($logoutModalStylesPath, ENT_QUOTES, 'UTF-8') ?>">
<div class="logout-modal-backdrop" id="logoutModal" aria-hidden="true">
    <div class="logout-modal" role="dialog" aria-modal="true" aria-labelledby="logoutModalTitle" aria-describedby="logoutModalDescription">
        <h2 class="logout-modal__title" id="logoutModalTitle">Logout Confirmation</h2>
        <p class="logout-modal__subtitle" id="logoutModalDescription">Are you sure you want to log out?</p>
        <div class="logout-modal__actions">
            <button class="logout-modal__button logout-modal__button--cancel" id="cancelLogoutButton" type="button">Cancel</button>
            <button class="logout-modal__button logout-modal__button--confirm" id="confirmLogoutButton" type="button">Yes, logout</button>
        </div>
    </div>
</div>
<script src="<?= htmlspecialchars($logoutModalScriptPath, ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars(encoder_sidebar_path($basePrefix, '../auth/session_timeout.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php
    return (string) ob_get_clean();
}
