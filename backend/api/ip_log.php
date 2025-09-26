<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../lib/ip_logger.php';

function respond(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

$tag = isset($_GET['tag']) ? (string)$_GET['tag'] : null;
$result = log_request_ip($tag);
if (!$result['ok']) {
    respond(['error' => $result['error']], 500);
}
respond($result, 201);