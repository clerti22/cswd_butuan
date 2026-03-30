<?php

declare(strict_types=1);

function resolve_logged_in_user_notification_context(
    mysqli $connection,
    string $username,
    string $fallbackDivision,
    string $fallbackProgram
): array {
    $resolvedDivision = trim($fallbackDivision);
    $resolvedProgram = trim($fallbackProgram);

    if ($username === '') {
        return [
            'division' => $resolvedDivision,
            'program' => $resolvedProgram,
        ];
    }

    $programColumn = '';
    foreach (['programs', 'program_assigned', 'program'] as $candidateColumn) {
        $escapedColumn = $connection->real_escape_string($candidateColumn);
        $columnResult = $connection->query("SHOW COLUMNS FROM users LIKE '{$escapedColumn}'");

        if ($columnResult && $columnResult->num_rows > 0) {
            $programColumn = $candidateColumn;
            $columnResult->free();
            break;
        }

        if ($columnResult) {
            $columnResult->free();
        }
    }

    $selectProgramColumn = $programColumn !== '' ? $programColumn : "''";
    $userContextStatement = $connection->prepare(
        "SELECT division, {$selectProgramColumn} AS user_program FROM users WHERE username = ? LIMIT 1"
    );

    if (!$userContextStatement) {
        error_log('Failed to prepare logged-in user context statement for notifications: ' . $connection->error);
        return [
            'division' => $resolvedDivision,
            'program' => $resolvedProgram,
        ];
    }

    $userContextStatement->bind_param('s', $username);

    if (!$userContextStatement->execute()) {
        error_log('Failed to execute logged-in user context statement for notifications: ' . $userContextStatement->error);
        $userContextStatement->close();

        return [
            'division' => $resolvedDivision,
            'program' => $resolvedProgram,
        ];
    }

    $userContextResult = $userContextStatement->get_result();
    $userContext = $userContextResult ? $userContextResult->fetch_assoc() : null;
    $userContextStatement->close();

    if ($userContext) {
        $userDivision = trim((string) ($userContext['division'] ?? ''));
        $userProgram = trim((string) ($userContext['user_program'] ?? ''));

        if ($userDivision !== '') {
            $resolvedDivision = $userDivision;
        }

        if ($userProgram !== '') {
            $resolvedProgram = $userProgram;
        }
    }

    return [
        'division' => $resolvedDivision,
        'program' => $resolvedProgram,
    ];
}

function insert_client_creation_notification(
    mysqli $connection,
    string $name,
    string $role,
    string $program,
    string $division
): void {
    $activity = sprintf('added a new client');
    $insertNotification = $connection->prepare(
        'INSERT INTO notifications (name, activity, role, program, division) VALUES (?, ?, ?, ?, ?)'
    );

    if (!$insertNotification) {
        error_log('Failed to prepare notification insert statement: ' . $connection->error);
        return;
    }

    $insertNotification->bind_param('sssss', $name, $activity, $role, $program, $division);

    if (!$insertNotification->execute()) {
        error_log('Failed to insert notification: ' . $insertNotification->error);
    }

    $insertNotification->close();
}

function insert_client_update_notification(
    mysqli $connection,
    string $name,
    string $role,
    string $program,
    string $division
): void {
    $activity = sprintf('updated a client');
    $insertNotification = $connection->prepare(
        'INSERT INTO notifications (name, activity, role, program, division) VALUES (?, ?, ?, ?, ?)'
    );

    if (!$insertNotification) {
        error_log('Failed to prepare update notification insert statement: ' . $connection->error);
        return;
    }

    $insertNotification->bind_param('sssss', $name, $activity, $role, $program, $division);

    if (!$insertNotification->execute()) {
        error_log('Failed to insert update notification: ' . $insertNotification->error);
    }

    $insertNotification->close();
}
