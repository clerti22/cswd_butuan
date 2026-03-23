<?php

declare(strict_types=1);

function super_admin_sidebar_path(string $basePrefix, string $path): string
{
    $normalizedBase = rtrim($basePrefix, '/');

    if ($normalizedBase === '' || $normalizedBase === '.') {
        return './' . ltrim($path, '/');
    }

    return $normalizedBase . '/' . ltrim($path, '/');
}

function render_super_admin_sidebar(string $activeSection, string $basePrefixToSuperAdmin = '.'): string
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
        'social_workers' => [
            'label' => 'Social Workers Management',
            'href' => 'social workers management/index.php',
            'icon' => <<<'HTML'
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people nav-icon" viewBox="0 0 16 16">
  <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4" />
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
        'feedback' => [
            'label' => 'Feedback',
            'href' => 'feedback/index.php',
            'icon' => <<<'HTML'
<svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
  <polyline points="22,6 12,13 2,6"></polyline>
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

    $logoPath = super_admin_sidebar_path($basePrefixToSuperAdmin, 'images-logo/cswd-logo.png');
    $logoutPath = super_admin_sidebar_path($basePrefixToSuperAdmin, '../auth/logout.php');

    ob_start();
    ?>
<style>
    .logout-modal-backdrop {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        background: rgba(7, 7, 12, 0.72);
        backdrop-filter: blur(4px);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 0.25s ease, visibility 0.25s ease;
        z-index: 1200;
    }

    .logout-modal-backdrop.active {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .logout-modal {
        width: min(100%, 420px);
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 28px;
        padding: 2rem 1.75rem 1.5rem;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.38);
        text-align: center;
        color: #111827;
    }

    .logout-modal__icon-wrap {
        width: 72px;
        height: 72px;
        margin: 0 auto 1.5rem;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(250, 204, 21, 0.42);
        background: radial-gradient(circle at center, rgba(250, 204, 21, 0.22), rgba(250, 204, 21, 0.08));
        box-shadow: inset 0 0 18px rgba(250, 204, 21, 0.08);
    }

    .logout-modal__icon {
        font-size: 1.8rem;
        color: #facc15;
        line-height: 1;
    }

    .logout-modal__title {
        margin: 0;
        font-size: clamp(1.5rem, 3vw, 1.9rem);
        font-weight: 700;
        color: #111827;
    }

    .logout-modal__subtitle {
        margin: 0.75rem 0 0;
        font-size: 1rem;
        color: #6b7280;
    }

    .logout-modal__actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.9rem;
        margin-top: 2rem;
    }

    .logout-modal__button {
        border: 0;
        border-radius: 14px;
        padding: 0.95rem 1rem;
        font: inherit;
        font-weight: 700;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }

    .logout-modal__button:hover {
        transform: translateY(-1px);
    }

    .logout-modal__button--cancel {
        background: #f3f4f6;
        color: #374151;
        box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.08);
    }

    .logout-modal__button--confirm {
        background: linear-gradient(135deg, #ff5a54 0%, #ef4444 100%);
        color: #fff;
        box-shadow: 0 14px 30px rgba(239, 68, 68, 0.28);
    }

    @media (max-width: 480px) {
        .logout-modal {
            border-radius: 24px;
            padding: 1.75rem 1.15rem 1.15rem;
        }

        .logout-modal__actions {
            grid-template-columns: 1fr;
        }
    }
</style>
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="<?= htmlspecialchars($logoPath, ENT_QUOTES, 'UTF-8') ?>" alt="" width="130" height="110">
        <p class="sidebar-title">CCIMS Dashboard</p>
        <button class="close-btn" id="closeSidebar">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <div class="nav-menu">
        <?php foreach ($items as $key => $item): ?>
            <?php
            $isActive = $key === $activeSection;
            $itemClasses = 'nav-item' . ($isActive ? ' active' : '');
            $activeAttributes = $isActive
                ? ' style="color: #fff; background-color: rgba(255, 255, 255, 0.16); box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.18);" aria-current="page"'
                : '';
            ?>
            <a href="<?= htmlspecialchars(super_admin_sidebar_path($basePrefixToSuperAdmin, $item['href']), ENT_QUOTES, 'UTF-8') ?>" class="<?= $itemClasses ?>"<?= $activeAttributes ?>>
                <?= $item['icon'] ?>
                <span class="nav-text"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="sidebar-footer">
        <a href="<?= htmlspecialchars($logoutPath, ENT_QUOTES, 'UTF-8') ?>" class="nav-item" id="logoutTrigger">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-door-open nav-icon" viewBox="0 0 16 16">
                <path d="M8.5 10c-.276 0-.5-.448-.5-1s.224-1 .5-1 .5.448.5 1-.224 1-.5 1" />
                <path d="M10.828.122A.5.5 0 0 1 11 .5V1h.5A1.5 1.5 0 0 1 13 2.5V15h1.5a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1H3V1.5a.5.5 0 0 1 .43-.495l7-1a.5.5 0 0 1 .398.117M11.5 2H11v13h1V2.5a.5.5 0 0 0-.5-.5M4 1.934V15h6V1.077z" />
            </svg>
            <span class="nav-text">Logout</span>
        </a>
    </div>
</nav>
<div class="logout-modal-backdrop" id="logoutModal" aria-hidden="true">
    <div class="logout-modal" role="dialog" aria-modal="true" aria-labelledby="logoutModalTitle" aria-describedby="logoutModalDescription">
        <div class="logout-modal__icon-wrap" aria-hidden="true">
            <i class="bi bi-exclamation-triangle-fill logout-modal__icon"></i>
        </div>
        <h2 class="logout-modal__title" id="logoutModalTitle">Logout Confirmation</h2>
        <p class="logout-modal__subtitle" id="logoutModalDescription">You will be logged out of the system.</p>
        <div class="logout-modal__actions">
            <button class="logout-modal__button logout-modal__button--cancel" id="cancelLogoutButton" type="button">Cancel</button>
            <button class="logout-modal__button logout-modal__button--confirm" id="confirmLogoutButton" type="button">Yes, logout</button>
        </div>
    </div>
</div>
<script>
(function () {
    const initializeLogoutModal = () => {
        const logoutTrigger = document.getElementById('logoutTrigger');
        const logoutModal = document.getElementById('logoutModal');
        const cancelLogoutButton = document.getElementById('cancelLogoutButton');
        const confirmLogoutButton = document.getElementById('confirmLogoutButton');
        const logoutUrl = logoutTrigger?.getAttribute('href') || '../auth/logout.php';

        if (!logoutTrigger || !logoutModal || !cancelLogoutButton || !confirmLogoutButton || logoutTrigger.dataset.logoutModalBound === 'true') {
            return;
        }

        logoutTrigger.dataset.logoutModalBound = 'true';

        const setModalState = (isOpen) => {
            logoutModal.classList.toggle('active', isOpen);
            logoutModal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            document.body.style.overflow = isOpen ? 'hidden' : '';

            if (isOpen) {
                cancelLogoutButton.focus();
                return;
            }

            logoutTrigger.focus();
        };

        logoutTrigger.addEventListener('click', (event) => {
            event.preventDefault();

            if (typeof closeSidebar === 'function') {
                closeSidebar();
            }

            setModalState(true);
        });

        cancelLogoutButton.addEventListener('click', () => setModalState(false));
        confirmLogoutButton.addEventListener('click', () => {
            window.location.href = logoutUrl;
        });

        logoutModal.addEventListener('click', (event) => {
            if (event.target === logoutModal) {
                setModalState(false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && logoutModal.classList.contains('active')) {
                setModalState(false);
            }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeLogoutModal, { once: true });
        return;
    }

    initializeLogoutModal();
})();
</script>
<?php
    return (string) ob_get_clean();
}
