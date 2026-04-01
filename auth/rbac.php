<?php

declare(strict_types=1);

const RBAC_DIVISION_PROGRAMS = [
    'dpsd' => ['pyap', 'solo parent', 'pwud'],
    'pssd' => ['pwd', 'senior citizen'],
    'rpsd' => ['vawc', 'ciu', 'csae', 'car', 'cicl'],
];

const RBAC_ALLOWED_ROLES = ['super_admin', 'admin', 'encoder'];

function normalize_role(string $role): string
{
    $normalizedRole = strtolower(trim($role));

    return match ($normalizedRole) {
        'super admin', 'super_admin' => 'super_admin',
        'admin' => 'admin',
        'encoder' => 'encoder',
        default => '',
    };
}

function normalize_division(?string $division): string
{
    return strtolower(trim((string) $division));
}

function normalize_program(?string $program): string
{
    return strtolower(trim((string) $program));
}

function is_active_status(?string $status): bool
{
    return strtolower(trim((string) $status)) === 'active';
}

function is_supported_division(string $division): bool
{
    return array_key_exists($division, RBAC_DIVISION_PROGRAMS);
}

function is_supported_program(string $division, string $program): bool
{
    if (!is_supported_division($division)) {
        return false;
    }

    return in_array($program, RBAC_DIVISION_PROGRAMS[$division], true);
}

function format_division_label(string $division): string
{
    return strtoupper($division);
}

function role_dashboard_path(string $role): string
{
    return match ($role) {
        'super_admin' => '/cswd_project/cswd/cswd_butuan/super admin/index.php',
        'admin' => '/cswd_project/cswd/cswd_butuan/admin/index.php',
        'encoder' => '/cswd_project/cswd/cswd_butuan/encoders/index.php',
        default => '/login form/index.php?error=invalid_role',
    };
}

function role_home_path(string $role, string $division = '', string $program = ''): string
{
    $basePath = role_dashboard_path($role);

    if ($role === 'admin' && $division !== '') {
        return $basePath . '?division=' . urlencode(format_division_label($division));
    }

    if ($role === 'encoder' && $division !== '' && $program !== '') {
        return $basePath
            . '?division=' . urlencode(format_division_label($division))
            . '&program=' . urlencode(strtoupper($program));
    }

    return $basePath;
}
