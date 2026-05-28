<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/scan_copy_helpers.php';

require_login(['super admin', 'admin']);

header('Content-Type: application/json; charset=utf-8');

function is_loopback_host(string $host): bool
{
    $normalized = strtolower(trim($host));
    return $normalized === 'localhost' || $normalized === '127.0.0.1' || $normalized === '::1';
}

function find_private_server_ip(): string
{
    $candidates = find_private_server_ips();
    return $candidates[0] ?? '';
}

function find_private_server_ips(): array
{
    $candidates = [];

    $serverAddr = trim((string) ($_SERVER['SERVER_ADDR'] ?? ''));
    if ($serverAddr !== '') {
        $candidates[] = $serverAddr;
    }

    $hostname = gethostname();
    if (is_string($hostname) && $hostname !== '') {
        $resolved = gethostbynamel($hostname);
        if (is_array($resolved)) {
            $candidates = array_merge($candidates, $resolved);
        }
    }

    foreach ($candidates as $candidate) {
        if (!filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            continue;
        }

        if ($candidate === '127.0.0.1') {
            continue;
        }

        if (preg_match('/^(10\\.|172\\.(1[6-9]|2\\d|3[0-1])\\.|192\\.168\\.)/', $candidate) === 1) {
            $candidates[] = $candidate;
        }
    }

    return array_values(array_unique($candidates));
}

function build_public_origin(): string
{
    $configuredOrigin = trim((string) getenv('CCIMS_PUBLIC_ORIGIN'));
    if ($configuredOrigin !== '') {
        return rtrim($configuredOrigin, '/');
    }

    $preferredDefaultOrigin = 'http://192.168.254.101';
    if ($preferredDefaultOrigin !== '') {
        return rtrim($preferredDefaultOrigin, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $hostHeader = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = $hostHeader !== '' ? $hostHeader : trim((string) ($_SERVER['SERVER_NAME'] ?? ''));

    if (is_loopback_host($host) || str_starts_with($host, '127.0.0.1:') || str_starts_with($host, 'localhost:')) {
        $privateIp = find_private_server_ip();
        if ($privateIp !== '') {
            $port = (string) ($_SERVER['SERVER_PORT'] ?? '');
            $isDefaultPort = ($scheme === 'http' && $port === '80') || ($scheme === 'https' && $port === '443');
            $host = $isDefaultPort || $port === '' ? $privateIp : $privateIp . ':' . $port;
        }
    }

    return $scheme . '://' . $host;
}

$controlNumber = normalize_scan_value((string) ($_GET['client_control_number'] ?? ''));
$program = normalize_scan_value((string) ($_GET['program'] ?? ''));

if ($controlNumber === '' || $program === '') {
    http_response_code(400);
    echo json_encode(['message' => 'Missing required client details.']);
    exit;
}

$tokenPayload = [
    'client_control_number' => $controlNumber,
    'program' => $program,
    'uploaded_by' => (string) ($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Unknown user'),
    'exp' => time() + SCAN_COPY_TOKEN_TTL_SECONDS,
];

try {
    $token = create_scan_copy_token($tokenPayload);
    $origin = build_public_origin();
    $scriptDirectory = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
    $mobilePath = $scriptDirectory . '/mobile_scan_upload.php?token=' . rawurlencode($token);
    $mobileUrl = $origin . $mobilePath;
    $port = (string) ($_SERVER['SERVER_PORT'] ?? '');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $candidateOrigins = [];
    foreach (find_private_server_ips() as $candidateIp) {
        $isDefaultPort = ($scheme === 'http' && $port === '80') || ($scheme === 'https' && $port === '443');
        $candidateHost = $isDefaultPort || $port === '' ? $candidateIp : $candidateIp . ':' . $port;
        $candidateOrigins[] = $scheme . '://' . $candidateHost;
    }
    $candidateOrigins = array_values(array_unique($candidateOrigins));

    echo json_encode([
        'mobile_upload_url' => $mobileUrl,
        'mobile_upload_path' => $mobilePath,
        'expires_at' => $tokenPayload['exp'],
        'public_origin' => $origin,
        'candidate_origins' => $candidateOrigins,
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['message' => 'Unable to generate scan QR link.']);
}
