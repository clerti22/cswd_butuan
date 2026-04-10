<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';

function encoder_scope_division(): string
{
    $division = current_user_division();
    $map = division_program_map();

    if (!isset($map[$division])) {
        throw new RuntimeException('Invalid encoder division context.');
    }

    return $division;
}

function encoder_scope_program(): string
{
    $program = strtoupper(trim(current_user_program()));
    $division = encoder_scope_division();
    $allowedPrograms = division_program_map()[$division] ?? [];

    if ($program === '' || !in_array($program, $allowedPrograms, true)) {
        throw new RuntimeException('Invalid encoder program context.');
    }

    return $program;
}
