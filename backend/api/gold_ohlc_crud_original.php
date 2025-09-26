<?php
declare(strict_types=1);

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';

function respond($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

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

function validate_decimal($v): bool {
    if ($v === null || $v === '') return false;
    return is_numeric($v);
}

// Router
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input  = read_json();

try {
    $conn = get_connection();

    switch ($action) {
        case 'list': {
            // Optional filters: ?from=YYYY-MM-DD&to=YYYY-MM-DD
            $from = $_GET['from'] ?? null;
            $to   = $_GET['to'] ?? null;

            $sql = "SELECT id, `date`, `open`, `high`, `low`, `close`
                    FROM gold_ohlc WHERE 1=1";
            $types = ''; $params = [];

            if ($from !== null) {
                if (!validate_date($from)) { $conn->close(); respond(['error'=>'Invalid from date'], 400); }
                $sql .= " AND `date` >= ?"; $types .= 's'; $params[] = $from;
            }
            if ($to !== null) {
                if (!validate_date($to)) { $conn->close(); respond(['error'=>'Invalid to date'], 400); }
                $sql .= " AND `date` <= ?"; $types .= 's'; $params[] = $to;
            }

            $sql .= " ORDER BY `date` DESC";

            $stmt = $conn->prepare($sql);
            if (!$stmt) { $conn->close(); respond(['error'=>'DB prepare failed'], 500); }
            if ($params) { $stmt->bind_param($types, ...$params); }
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            $stmt->close(); $conn->close();
            respond($rows);
        }

        case 'get': {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) { $conn->close(); respond(['error'=>'Missing id'], 400); }

            $stmt = $conn->prepare("SELECT id, `date`, `open`, `high`, `low`, `close`
                                    FROM gold_ohlc WHERE id = ?");
            if (!$stmt) { $conn->close(); respond(['error'=>'DB prepare failed'], 500); }
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close(); $conn->close();
            if (!$row) respond(['error'=>'Not found'], 404);
            respond($row);
        }

        case 'create': {
            $date = trim((string)($input['date'] ?? ''));
            $open = $input['open'] ?? null;
            $high = $input['high'] ?? null;
            $low  = $input['low']  ?? null;
            $close= $input['close']?? null;

            if (!validate_date($date)) { $conn->close(); respond(['error'=>'Invalid or missing date (YYYY-MM-DD)'], 400); }
            if (!validate_decimal($open) || !validate_decimal($high) || !validate_decimal($low) || !validate_decimal($close)) {
                $conn->close(); respond(['error'=>'open/high/low/close must be numeric'], 400);
            }

            // Enforce uniqueness by date
            $chk = $conn->prepare("SELECT 1 FROM gold_ohlc WHERE `date` = ? LIMIT 1");
            if ($chk) {
                $chk->bind_param('s', $date);
                $chk->execute();
                $res = $chk->get_result();
                if ($res && $res->fetch_row()) { $chk->close(); $conn->close(); respond(['error'=>'Entry for this date already exists'], 409); }
                $chk->close();
            }

            $stmt = $conn->prepare("
                INSERT INTO gold_ohlc (`date`, `open`, `high`, `low`, `close`)
                VALUES (?, ?, ?, ?, ?)
            ");
            if (!$stmt) { $conn->close(); respond(['error'=>'DB prepare failed'], 500); }

            $openF = (float)$open; $highF = (float)$high; $lowF = (float)$low; $closeF = (float)$close;
            $stmt->bind_param('sdddd', $date, $openF, $highF, $lowF, $closeF);

            if (!$stmt->execute()) { $stmt->close(); $conn->close(); respond(['error'=>'DB insert failed'], 500); }
            $newId = $stmt->insert_id;
            $stmt->close();

            $get = $conn->prepare("SELECT id, `date`, `open`, `high`, `low`, `close` FROM gold_ohlc WHERE id = ?");
            $get->bind_param('i', $newId);
            $get->execute();
            $res = $get->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $get->close(); $conn->close();
            respond($row ?? ['id'=>$newId], 201);
        }

        case 'update': {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($input['id'] ?? 0);
            if ($id <= 0) { $conn->close(); respond(['error'=>'Missing id'], 400); }

            $fields=[]; $types=''; $params=[];

            if (array_key_exists('date', $input)) {
                $date = trim((string)$input['date']);
                if ($date !== '') {
                    if (!validate_date($date)) { $conn->close(); respond(['error'=>'Invalid date (YYYY-MM-DD)'], 400); }
                    // uniqueness check for new date
                    $chk = $conn->prepare("SELECT 1 FROM gold_ohlc WHERE `date` = ? AND id <> ? LIMIT 1");
                    if ($chk) {
                        $chk->bind_param('si', $date, $id);
                        $chk->execute();
                        $res = $chk->get_result();
                        if ($res && $res->fetch_row()) { $chk->close(); $conn->close(); respond(['error'=>'Entry for this date already exists'], 409); }
                        $chk->close();
                    }
                    $fields[] = "`date`=?"; $types.='s'; $params[]=$date;
                }
            }

            foreach (['open','high','low','close'] as $k) {
                if (array_key_exists($k, $input)) {
                    $v = $input[$k];
                    if (!validate_decimal($v)) { $conn->close(); respond(['error'=>"$k must be numeric"], 400); }
                    $fields[] = "`$k`=?"; $types.='d'; $params[]=(float)$v;
                }
            }

            if (!$fields) { $conn->close(); respond(['error'=>'No fields to update'], 400); }

            $sql = "UPDATE gold_ohlc SET ".implode(',', $fields)." WHERE id = ?";
            $types.='i'; $params[]=$id;

            $stmt = $conn->prepare($sql);
            if (!$stmt) { $conn->close(); respond(['error'=>'DB prepare failed'], 500); }
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) { $stmt->close(); $conn->close(); respond(['error'=>'DB update failed'], 500); }
            $stmt->close();

            $get = $conn->prepare("SELECT id, `date`, `open`, `high`, `low`, `close` FROM gold_ohlc WHERE id = ?");
            $get->bind_param('i', $id);
            $get->execute();
            $res = $get->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $get->close(); $conn->close();
            if (!$row) respond(['error'=>'Not found after update'], 404);
            respond($row);
        }

        case 'delete': {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($input['id'] ?? 0);
            if ($id <= 0) { $conn->close(); respond(['error'=>'Missing id'], 400); }
            $stmt = $conn->prepare("DELETE FROM gold_ohlc WHERE id = ?");
            if (!$stmt) { $conn->close(); respond(['error'=>'DB prepare failed'], 500); }
            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) { $stmt->close(); $conn->close(); respond(['error'=>'DB delete failed'], 500); }
            $affected = $stmt->affected_rows;
            $stmt->close(); $conn->close();
            if ($affected === 0) respond(['error'=>'Not found'], 404);
            respond(['ok'=>true]);
        }

        default:
            $conn->close();
            respond(['error'=>'Unknown or missing action. Use action=list|get|create|update|delete'], 400);
    }
} catch (Throwable $e) {
    respond(['error'=>'Server error'], 500);
}