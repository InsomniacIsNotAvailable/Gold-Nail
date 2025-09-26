<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

function bad(string $msg, int $code = 400) {
  http_response_code($code);
  header('Content-Type: text/plain; charset=utf-8');
  echo $msg;
  exit;
}

$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ts   = isset($_GET['ts']) ? (int)$_GET['ts'] : 0;
$sig  = (string)($_GET['sig'] ?? '');

$cfgPath = __DIR__ . '/../config/brevo_email_config.php';
$cfg = is_file($cfgPath) ? require $cfgPath : [];
$secret = (string)($cfg['link_secret'] ?? '');

if ($id <= 0 || $ts <= 0 || $sig === '' || $secret === '') {
  bad('Invalid or missing parameters.');
}

if (abs(time() - $ts) > 60 * 60 * 24 * 14) { // 14 days
  bad('Link expired.');
}

$base = $secret;
$check = hash_hmac('sha256', $id . ':' . $ts, $base);
if (!hash_equals($check, $sig)) {
  bad('Invalid signature.', 403);
}

try {
  $conn = get_connection();

  $stmt = $conn->prepare("UPDATE appointments SET status = 'Cancelled' WHERE id = ?");
  if (!$stmt) { $conn->close(); bad('DB error', 500); }
  $stmt->bind_param('i', $id);
  $ok = $stmt->execute();
  $stmt->close();
  $conn->close();

  if (!$ok) bad('Update failed', 500);

  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><meta charset="utf-8"><title>Appointment Cancelled</title><body style="font-family:Segoe UI,Arial,sans-serif;max-width:640px;margin:40px auto;padding:0 16px">';
  echo '<h1>Appointment Cancelled</h1><p>Your appointment (ID ' . htmlspecialchars((string)$id) . ') has been marked as Cancelled. If this was a mistake, please contact us to reschedule.</p>';
  echo '</body>';
} catch (Throwable $e) {
  bad('Server error', 500);
}
