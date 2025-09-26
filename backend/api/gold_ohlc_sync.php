<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../tasks/lib_gold_ohlc_sync.php';

function respond($data, int $code = 200): void { http_response_code($code); echo json_encode($data); exit; }

function read_json(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function validate_date(string $d): bool {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return false;
    [$y,$m,$day] = array_map('intval', explode('-', $d));
    return checkdate($m, $day, $y);
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'lastDate': {
            $conn = get_connection();
            @$conn->query("CREATE TABLE IF NOT EXISTS gold_ohlc (
              id INT AUTO_INCREMENT PRIMARY KEY,
              `date` DATE NOT NULL,
              `open` DECIMAL(18,6) NOT NULL,
              `high` DECIMAL(18,6) NOT NULL,
              `low`  DECIMAL(18,6) NOT NULL,
              `close` DECIMAL(18,6) NOT NULL,
              UNIQUE KEY uq_gold_ohlc_date (`date`)
            )");
            $res = $conn->query("SELECT MAX(`date`) AS last_date FROM gold_ohlc");
            $row = $res ? $res->fetch_assoc() : null;
            $conn->close();
            $last = ($row && $row['last_date']) ? $row['last_date'] : null;
            respond(['last' => $last]);
        }

        case 'syncRange': {
            $input = read_json();
            $from = (string)($input['from'] ?? '');
            $to   = (string)($input['to'] ?? '');
            if (!validate_date($from) || !validate_date($to)) respond(['error'=>'Invalid from/to'], 400);
            if ($from > $to) respond(['error'=>'from > to'], 400);

            // Optional per-request overwrite toggle (overrides env default)
            $overwriteParam = $_GET['overwrite'] ?? $input['overwrite'] ?? null;
            if ($overwriteParam !== null) {
                $ov = filter_var($overwriteParam, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($ov !== null) {
                    // sync_ohlc_for_date reads global $metalCfg
                    $GLOBALS['metalCfg']['overwrite'] = (bool)$ov;
                }
            }

            $dates = [];
            $d = new DateTimeImmutable($from);
            $end = new DateTimeImmutable($to);
            while ($d <= $end) { $dates[] = $d->format('Y-m-d'); $d = $d->modify('+1 day'); }

            $ok = 0; $errors = [];
            foreach ($dates as $ymd) {
                try {
                    $res = sync_ohlc_for_date($ymd, 'XAU', 'PHP');
                    if (!empty($res['stored'])) $ok++;
                } catch (Throwable $e) {
                    $errors[] = [$ymd, $e->getMessage()];
                }
            }
            $status = [
                'success' => empty($errors),
                'stored' => $ok,
                'attempted' => count($dates),
                'overwrite' => (bool)($GLOBALS['metalCfg']['overwrite'] ?? false)
            ];
            if ($errors) $status['errors'] = $errors;
            respond($status, $errors ? 207 : 200);
        }

        default:
            respond(['error' => 'Unknown action'], 400);
    }
} catch (Throwable $e) {
    respond(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
?>