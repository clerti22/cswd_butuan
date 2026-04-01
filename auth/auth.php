<?php

declare(strict_types=1);

const SESSION_TIMEOUT_SECONDS = 1800;

function start_auth_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT_SECONDS,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return;
    }

    if ((time() - (int) $_SESSION['last_activity']) > SESSION_TIMEOUT_SECONDS) {
        logout_user();
        header('Location: /login form/index.php?error=session_expired');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

function login_user(string $full_name, string $username, string $role, string $division = '', string $program = ''): void
{
    start_auth_session();

    session_regenerate_id(true);
    $_SESSION['full_name'] = $full_name;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['division'] = strtoupper(trim($division));
    $_SESSION['program'] = trim($program);
    $_SESSION['last_activity'] = time();
}

function logout_user(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }

    session_destroy();
}

function require_login(array $allowedRoles): void
{
    start_auth_session();

    $username = $_SESSION['username'] ?? null;
    $role = $_SESSION['role'] ?? null;
   

    if (!$username || !$role) {
        header('Location: /login form/index.php?error=unauthorized');
        exit;
    }

    if (!in_array($role, $allowedRoles, true)) {
        header('Location: ' . role_dashboard_path($role));
        exit;
    }
}



function current_user_division(): string
{
    start_auth_session();
    return strtoupper(trim((string) ($_SESSION['division'] ?? '')));
}

function current_user_program(): string
{
    start_auth_session();
    return trim((string) ($_SESSION['program'] ?? ''));
}

/**
 * @return array<string, array<int, string>>
 */
function division_program_map(): array
{
    return [
        'DPSD' => ['PYAP', 'SOLO PARENT', 'PWUD'],
        'PSSD' => ['PWD', 'SENIOR CITIZEN'],
        'RPSD' => ['VAWC', 'CIU', 'CSAE', 'CAR', 'CICL'],
    ];
}

function role_dashboard_path(string $role): string
{
    if ($role === 'admin') {
        $division = current_user_division();
        if (!in_array($division, ['DPSD', 'PSSD', 'RPSD'], true)) {
            return '/login form/index.php?error=invalid_division';
        }

        return '/cswd_project/cswd/cswd_butuan/admin/index.php?division=' . urlencode($division);
    }

    return match ($role) {
        'super admin' => '/cswd_project/cswd/cswd_butuan/super admin/index.php',
        'encoder' => '/cswd_project/cswd/cswd_butuan/encoders/index.php',
        default => '/login form/index.php?error=invalid_role',
    };
}
