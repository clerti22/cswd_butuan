<?php

declare(strict_types=1);

const SCAN_COPY_TOKEN_SECRET = 'ccims_scan_copy_secret_2026';
const SCAN_COPY_TOKEN_TTL_SECONDS = 900;

function ensure_client_scans_table(mysqli $connection): void
{
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS client_scans (
    id INT(11) NOT NULL AUTO_INCREMENT,
    client_control_number VARCHAR(255) NOT NULL,
    program VARCHAR(50) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    uploaded_by VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_client_program (client_control_number, program)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    if (!$connection->query($sql)) {
        throw new RuntimeException('Unable to prepare client scan storage.');
    }
}

function normalize_scan_value(string $value): string
{
    return trim($value);
}

function create_scan_copy_token(array $payload): string
{
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Unable to encode scan token payload.');
    }

    $encodedPayload = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    $signature = hash_hmac('sha256', $encodedPayload, SCAN_COPY_TOKEN_SECRET);

    return $encodedPayload . '.' . $signature;
}

function verify_scan_copy_token(string $token): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 2) {
        return null;
    }

    [$encodedPayload, $providedSignature] = $parts;
    if ($encodedPayload === '' || $providedSignature === '') {
        return null;
    }

    $expectedSignature = hash_hmac('sha256', $encodedPayload, SCAN_COPY_TOKEN_SECRET);
    if (!hash_equals($expectedSignature, $providedSignature)) {
        return null;
    }

    $decoded = base64_decode(strtr($encodedPayload, '-_', '+/'), true);
    if ($decoded === false) {
        return null;
    }

    $payload = json_decode($decoded, true);
    if (!is_array($payload)) {
        return null;
    }

    $expiresAt = isset($payload['exp']) ? (int) $payload['exp'] : 0;
    if ($expiresAt <= time()) {
        return null;
    }

    return $payload;
}

function get_latest_client_scan(mysqli $connection, string $controlNumber, string $program): ?array
{
    $query = $connection->prepare(
        'SELECT id, image_path, uploaded_by, uploaded_at FROM client_scans WHERE client_control_number = ? AND program = ? LIMIT 1'
    );

    if (!$query) {
        throw new RuntimeException('Unable to prepare scan query.');
    }

    $query->bind_param('ss', $controlNumber, $program);
    if (!$query->execute()) {
        $query->close();
        throw new RuntimeException('Unable to fetch scan record.');
    }

    $result = $query->get_result();
    $scan = $result ? $result->fetch_assoc() : null;
    $query->close();

    if (!$scan) {
        return null;
    }

    return [
        'id' => (int) ($scan['id'] ?? 0),
        'image_path' => (string) ($scan['image_path'] ?? ''),
        'uploaded_by' => (string) ($scan['uploaded_by'] ?? ''),
        'uploaded_at' => (string) ($scan['uploaded_at'] ?? ''),
    ];
}
