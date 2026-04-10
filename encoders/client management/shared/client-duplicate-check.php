<?php

declare(strict_types=1);

final class DuplicateClientException extends RuntimeException
{
}

/**
 * @param int|string|null $excludeRecordId
 */
function enforce_unique_client_identity(
    mysqli $connection,
    string $tableName,
    string $keyColumn,
    string $birthDateColumn,
    string $firstName,
    string $lastName,
    string $birthDate,
    string $barangay,
    int|string|null $excludeRecordId = null
): void {
    foreach ([$tableName, $keyColumn, $birthDateColumn] as $identifier) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $identifier)) {
            throw new InvalidArgumentException('Invalid duplicate check configuration.');
        }
    }

    $query = sprintf(
        'SELECT `%s` FROM `%s` WHERE LOWER(TRIM(first_name)) = LOWER(TRIM(?)) AND LOWER(TRIM(last_name)) = LOWER(TRIM(?)) AND DATE(`%s`) = DATE(?) AND LOWER(TRIM(barangay)) = LOWER(TRIM(?))',
        $keyColumn,
        $tableName,
        $birthDateColumn
    );

    $params = [$firstName, $lastName, $birthDate, $barangay];
    $types = 'ssss';

    if ($excludeRecordId !== null && $excludeRecordId !== '') {
        $query .= sprintf(' AND `%s` <> ?', $keyColumn);
        $params[] = (string) $excludeRecordId;
        $types .= 's';
    }

    $query .= ' LIMIT 1';

    $statement = $connection->prepare($query);
    if (!$statement) {
        throw new RuntimeException('Failed to prepare duplicate client check statement.');
    }

    $statement->bind_param($types, ...$params);

    if (!$statement->execute()) {
        $message = $statement->error;
        $statement->close();
        throw new RuntimeException($message !== '' ? $message : 'Failed to execute duplicate client check statement.');
    }

    $statement->store_result();
    $exists = $statement->num_rows > 0;
    $statement->close();

    if ($exists) {
        throw new DuplicateClientException('Client already exists: full name, birthday, and barangay already match an existing record.');
    }
}
