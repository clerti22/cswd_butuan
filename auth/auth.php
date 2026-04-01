<?php

declare(strict_types=1);

require_once __DIR__ . '/rbac.php';

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

function login_user(string $fullName, string $username, string $role, string $division = '', string $program = ''): void
{
    start_auth_session();

    session_regenerate_id(true);
    $_SESSION['full_name'] = $fullName;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['division'] = $division;
    $_SESSION['program'] = $program;
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

    $normalizedAllowedRoles = array_values(array_filter(array_map(
        static fn (string $allowedRole): string => normalize_role($allowedRole),
        $allowedRoles
    )));

    $username = $_SESSION['username'] ?? null;
    $role = $_SESSION['role'] ?? null;
    $division = normalize_division($_SESSION['division'] ?? '');
    $program = normalize_program($_SESSION['program'] ?? '');

    if (!$username || !$role) {
        header('Location: /login form/index.php?error=unauthorized');
        exit;
    }

    if (!in_array($role, $normalizedAllowedRoles, true)) {
        header('Location: ' . role_home_path($role, $division, $program));
        exit;
    }

    if ($role === 'admin' && !is_supported_division($division)) {
        logout_user();
        header('Location: /login form/index.php?error=invalid_division');
        exit;
    }

    if ($role === 'encoder' && !is_supported_program($division, $program)) {
        logout_user();
        header('Location: /login form/index.php?error=invalid_program');
        exit;
    }
}

function get_auth_user_context(): array
{
    start_auth_session();

    return [
        'full_name' => (string) ($_SESSION['full_name'] ?? ''),
        'username' => (string) ($_SESSION['username'] ?? ''),
        'role' => (string) ($_SESSION['role'] ?? ''),
        'division' => normalize_division($_SESSION['division'] ?? ''),
        'program' => normalize_program($_SESSION['program'] ?? ''),
    ];
}

function can_access_division(string $division): bool
{
    $division = normalize_division($division);
    $context = get_auth_user_context();

    if ($context['role'] === 'super_admin') {
        return is_supported_division($division);
    }

    return $context['division'] !== '' && $context['division'] === $division;
}

function can_access_program(string $division, string $program): bool
{
    $division = normalize_division($division);
    $program = normalize_program($program);
    $context = get_auth_user_context();

    if (!is_supported_program($division, $program)) {
        return false;
    }

    if ($context['role'] === 'super_admin') {
        return true;
    }

    if ($context['role'] === 'admin') {
        return $context['division'] === $division;
    }

    return $context['division'] === $division && $context['program'] === $program;
}
