<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

try {
  $cfg = require __DIR__ . '/../config/metal_price_api_config.php';
  echo json_encode([
    'has_key' => ($cfg['api_key'] !== ''),
    'key_len' => strlen((string)$cfg['api_key']),
    'base_url' => $cfg['base_url'],
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}