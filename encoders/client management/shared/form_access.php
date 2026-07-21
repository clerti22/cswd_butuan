<?php

declare(strict_types=1);

require_once __DIR__ . '/../../encoder_scope.php';

function require_encoder_program_access(string $expectedProgram): void
{
    require_login(['encoder']);

    $normalizedExpected = strtoupper(trim($expectedProgram));
    $normalizedCurrent = encoder_scope_program();

    if ($normalizedExpected === 'PWD-2' && $normalizedCurrent === 'PWD') {
        return;
    }

    if ($normalizedExpected === '' || $normalizedCurrent !== $normalizedExpected) {
        http_response_code(403);
        exit('You are not authorized to access this form.');
    }
}
