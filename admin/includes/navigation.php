<?php

declare(strict_types=1);

function admin_sidebar_path(string $basePrefix, string $path): string
{
    $normalizedBase = rtrim($basePrefix, '/');

    if ($normalizedBase === '' || $normalizedBase === '.') {
        return './' . ltrim($path, '/');
    }

    return $normalizedBase . '/' . ltrim($path, '/');
}

function render_admin_sidebar(string $activeSection, string $basePrefix = '.'): string
{
    $items = [
        'dashboard' => ['label' => 'Dashboard', 'href' => 'index.php'],
        'social_workers' => ['label' => 'Encoders Management', 'href' => 'social workers management/index.php'],
        'clients' => ['label' => 'Clients Management', 'href' => 'client management/index.php'],
        'report' => ['label' => 'Report', 'href' => 'report/index.php'],
        'profile' => ['label' => 'View Profile', 'href' => 'profile/index.php'],
    ];

    ob_start();
    ?>
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="<?= htmlspecialchars(admin_sidebar_path($basePrefix, 'images-logo/cswd-logo.png'), ENT_QUOTES, 'UTF-8') ?>" alt="" width="130" height="110">
        <p class="sidebar-title">CCIMS Dashboard</p>
        <button class="close-btn" id="closeSidebar">✕</button>
    </div>
    <div class="nav-menu">
        <?php foreach ($items as $key => $item): ?>
            <a href="<?= htmlspecialchars(admin_sidebar_path($basePrefix, $item['href']), ENT_QUOTES, 'UTF-8') ?>" class="nav-item<?= $key === $activeSection ? ' active' : '' ?>">
                <span class="nav-text"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="sidebar-footer">
        <a href="<?= htmlspecialchars(admin_sidebar_path($basePrefix, '../auth/logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="nav-item"><span class="nav-text">Logout</span></a>
    </div>
</nav>
<?php
    return (string) ob_get_clean();
}
