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
        'dashboard' => ['label' => 'Dashboard', 'href' => 'index.php'],
        'clients' => ['label' => 'Clients Management', 'href' => 'client management/index.php'],
        'report' => ['label' => 'Report', 'href' => 'report/index.php'],
        'profile' => ['label' => 'View Profile', 'href' => 'profile/index.php'],
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
            <a href="<?= htmlspecialchars(encoder_sidebar_path($basePrefix, $item['href']), ENT_QUOTES, 'UTF-8') ?>" class="nav-item<?= $key === $activeSection ? ' active' : '' ?>">
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
<?php
    return (string) ob_get_clean();
}
