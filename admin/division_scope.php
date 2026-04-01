<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';

function admin_scope_division(): string
{
    $division = current_user_division();
    if (!in_array($division, ['DPSD', 'PSSD', 'RPSD'], true)) {
        throw new RuntimeException('Invalid admin division context.');
    }

    return $division;
}

/** @return array<int, string> */
function admin_scope_programs(): array
{
    $map = division_program_map();
    $division = admin_scope_division();
    return $map[$division] ?? [];
}
