<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function current_user_role(): string
{
    start_auth_session();
    return strtolower(trim((string) ($_SESSION['role'] ?? '')));
}

function require_operational_access(): void
{
    require_login(['admin', 'encoder']);
}

function scoped_division(): string
{
    $division = current_user_division();
    $map = division_program_map();

    if (!array_key_exists($division, $map)) {
        throw new RuntimeException('Invalid division scope.');
    }

    return $division;
}

/** @return array<int, string> */
function scoped_programs(): array
{
    $division = scoped_division();
    $map = division_program_map();
    $role = current_user_role();

    if ($role === 'encoder') {
        $program = strtoupper(trim(current_user_program()));
        if ($program === '' || !in_array($program, $map[$division] ?? [], true)) {
            throw new RuntimeException('Invalid encoder program scope.');
        }

        return [$program];
    }

    return $map[$division] ?? [];
}

function can_manage_users(): bool
{
    return current_user_role() === 'admin';
}

function can_access_advanced_reports(): bool
{
    return current_user_role() === 'admin';
}

function is_program_in_scope(string $program): bool
{
    $program = strtoupper(trim($program));
    if ($program === '') {
        return false;
    }

    return in_array($program, scoped_programs(), true);
}
