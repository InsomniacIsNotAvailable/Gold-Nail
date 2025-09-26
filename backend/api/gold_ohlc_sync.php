<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../tasks/lib_gold_ohlc_sync.php';

function read_json(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
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
            echo json_encode(['last' => $row && $row['last_date'] ? $row['last_date'] : null]);
            break;
        }
        case 'syncRange': {
            $in = read_json();
            $from = (string)($in['from'] ?? '');
            $to   = (string)($in['to']   ?? '');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                http_response_code(400);
                echo json_encode(['error' => 'from/to must be YYYY-MM-DD']);
                break;
            }
            $start = new DateTimeImmutable($from, new DateTimeZone('UTC'));
            $end   = new DateTimeImmutable($to,   new DateTimeZone('UTC'));
            if ($start > $end) {
                http_response_code(400);
                echo json_encode(['error' => 'from must be <= to']);
                break;
            }
            // Optional guard to avoid huge ranges
            $diffDays = (int)$end->diff($start)->format('%a');
            if ($diffDays > 62) {
                http_response_code(400);
                echo json_encode(['error' => 'Range too large (limit ~62 days)']);
                break;
            }

            $out = [];
            for ($d = $start; $d <= $end; $d = $d->modify('+1 day')) {
                $ymd = $d->format('Y-m-d');
                try {
                    $out[$ymd] = sync_ohlc_for_date($ymd, 'XAU', 'PHP')['stored'] ?? null;
                    usleep(200000); // 0.2s throttle
                } catch (Throwable $e) {
                    $out[$ymd] = ['error' => $e->getMessage()];
                }
            }
            echo json_encode(['from'=>$from,'to'=>$to,'results'=>$out]);
            break;
        }
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action. Use action=lastDate or action=syncRange']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}