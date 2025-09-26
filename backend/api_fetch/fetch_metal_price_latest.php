<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$config = require dirname(__DIR__) . '/config/metal_price_api_config.php';

$apiKey = $config['api_key'] ?? '';
if ($apiKey === '') {
    http_response_code(500);
    echo json_encode(['error' => 'Missing METAL_PRICE_API_KEY in .env']);
    exit;
}

// Build OHLC endpoint from configured base_url (works whether it ends with /latest or with /v1)
$baseUrl = (string)($config['base_url'] ?? '');
if ($baseUrl === '') {
    $baseUrl = 'https://api.metalpriceapi.com/v1/latest';
}
$endpoint = preg_replace('#/latest/?$#', '/ohlc', rtrim($baseUrl, '/'));
if (!preg_match('#/ohlc$#', $endpoint)) {
    $endpoint = rtrim($baseUrl, '/') . '/ohlc';
}

// Defaults for OHLC: base = XAU (gold), currency = PHP, date = today (UTC)
$base     = strtoupper(trim((string)($_GET['base'] ?? 'XAU')));
$currency = strtoupper(trim((string)($_GET['currency'] ?? 'PHP')));

// Allow ?date=YYYY-MM-DD override; otherwise use current date in UTC
$dateParam = isset($_GET['date']) ? (string)$_GET['date'] : (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d');

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateParam)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD.']);
    exit;
}

$query = http_build_query([
    'api_key'  => $apiKey,
    'base'     => $base,
    'currency' => $currency,
    'date'     => $dateParam,
], '', '&', PHP_QUERY_RFC3986);

$url = $endpoint . '?' . $query;

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => (int)($config['timeout'] ?? 10),
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
]);
$body = curl_exec($ch);
$err  = curl_error($ch);
$code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

if ($err || $code >= 400 || $body === false) {
    http_response_code($code ?: 502);
    echo json_encode(['error' => $err ?: "HTTP $code"]);
    exit;
}

echo $body;