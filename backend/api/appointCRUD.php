<?php
ini_set('display_errors','0');
ini_set('log_errors','1');

require_once __DIR__ . '/../config.php';

function respond($data, int $code = 200): void {
    if (ob_get_length()) { @ob_clean(); } // clear any accidental output
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function read_json(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
function valid_status(?string $s): bool {
    if ($s === null) return true;
    static $allowed = ['New','Confirmed','Completed','Cancelled'];
    return in_array($s, $allowed, true);
}
function get_slot_capacity(): int {
    $env = getenv('APPT_SLOT_CAPACITY');
    if ($env === false || $env === '') return 2; // default to 2
    $cap = (int)$env;
    return max(1, $cap);
}

// Router
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input  = read_json();

try {
    $conn = get_connection();

    switch ($action) {
        case 'list': {
            @$conn->query("UPDATE appointments
                           SET status = 'Cancelled'
                           WHERE appointment_datetime < NOW()
                             AND status NOT IN ('Completed','Cancelled')");

            $status = $_GET['status'] ?? null;
            $from   = $_GET['from'] ?? null;
            $to     = $_GET['to'] ?? null;

            $sql = "SELECT id, name, email, phone, appointment_datetime, purpose, message, status, created_at
                    FROM appointments WHERE 1=1";
            $types = ''; $params = [];

            if ($status !== null) {
                if (!valid_status($status)) { $conn->close(); respond(['error'=>'Invalid status'], 400); }
                $sql .= " AND status = ?"; $types .= 's'; $params[] = $status;
            }
            if ($from !== null) { $sql .= " AND appointment_datetime >= ?"; $types .= 's'; $params[] = $from . ' 00:00:00'; }
            if ($to   !== null) { $sql .= " AND appointment_datetime <= ?"; $types .= 's'; $params[] = $to   . ' 23:59:59'; }

            $sql .= " ORDER BY appointment_datetime DESC";

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

            $stmt = $conn->prepare("SELECT id, name, email, phone, appointment_datetime, purpose, message, status, created_at
                                    FROM appointments WHERE id = ?");
            if (!$stmt) { $conn->close(); respond(['error'=>'DB prepare failed'], 500); }
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close(); $conn->close();
            if (!$row) respond(['error'=>'Not found'], 404);
            respond($row);
        }

        case 'free_slots': {
            $days = max(1, min(60, (int)($_GET['days'] ?? 14)));
            $startHour = max(0, min(23, (int)($_GET['startHour'] ?? 7)));
            $endHour   = max($startHour, min(23, (int)($_GET['endHour'] ?? 20)));
            $offsetDays = max(0, min(30, (int)($_GET['offsetDays'] ?? 1)));

            $capacity = get_slot_capacity();

            $tz = new DateTimeZone(date_default_timezone_get());
            $now = new DateTime('now', $tz);
            $start = (clone $now)->setTime(0,0,0)->modify("+$offsetDays day");
            $end   = (clone $start)->modify("+$days day")->setTime(23,59,59);

            $sql = "SELECT DATE_FORMAT(appointment_datetime, '%Y-%m-%d %H:00:00') AS slot, COUNT(*) AS n
                    FROM appointments
                    WHERE appointment_datetime BETWEEN ? AND ?
                      AND status <> 'Cancelled'
                    GROUP BY slot";
            $stmt = $conn->prepare($sql);
            if (!$stmt) { $conn->close(); respond(['error'=>'DB prepare failed'], 500); }
            $fromStr = $start->format('Y-m-d H:i:s');
            $toStr   = $end->format('Y-m-d H:i:s');
            $stmt->bind_param('ss', $fromStr, $toStr);
            $stmt->execute();
            $res = $stmt->get_result();

            $bookedCounts = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $slot = (string)($row['slot'] ?? '');
                    if ($slot !== '') $bookedCounts[$slot] = (int)$row['n'];
                }
            }
            $stmt->close(); $conn->close();

            $out = [];
            $day = clone $start;
            for ($d = 0; $d < $days; $d++) {
                for ($h = $startHour; $h <= $endHour; $h++) {
                    $slot = (clone $day)->setTime($h, 0, 0);
                    $value = $slot->format('Y-m-d H:00:00');
                    $text  = $slot->format('l, F j, Y \a\t g:00 A');
                    $count = $bookedCounts[$value] ?? 0;
                    $out[] = [
                        'value'  => $value,
                        'text'   => $text,
                        'booked' => ($count >= $capacity),
                    ];
                }
                $day->modify('+1 day');
            }
            respond($out);
        }

        case 'probe': {
            $dt = trim((string)($_GET['dt'] ?? ''));
            if ($dt === '') { $conn->close(); respond(['error'=>'Missing dt'], 400); }

            $probe = $conn->prepare("SELECT id, status, appointment_datetime FROM appointments WHERE appointment_datetime = ? ORDER BY id DESC LIMIT 10");
            if (!$probe) { $conn->close(); respond(['error'=>'DB prepare failed'], 500); }
            $probe->bind_param('s', $dt);
            $probe->execute();
            $pr = $probe->get_result();
            $rows = $pr ? $pr->fetch_all(MYSQLI_ASSOC) : [];
            $probe->close();

            $info = [];
            if ($res = $conn->query("SELECT DATABASE() AS db, @@session.time_zone AS session_tz, @@global.time_zone AS global_tz, NOW() AS server_now")) {
                $info = $res->fetch_assoc() ?: [];
                $res->close();
            }
            $conn->close();
            respond(['dt'=>$dt, 'matches'=>$rows, 'info'=>$info]);
        }

        case 'create': {
            $name  = trim((string)($input['name'] ?? ''));
            $dt    = trim((string)($input['appointment_datetime'] ?? ''));
            $purpose = trim((string)($input['purpose'] ?? ''));

            $email = trim((string)($input['email'] ?? ''));
            $phone = trim((string)($input['phone'] ?? ''));
            $message = trim((string)($input['message'] ?? ''));
            $status = $input['status'] ?? 'New';

            if ($name === '' || $dt === '' || $purpose === '') {
                $conn->close(); respond(['error'=>'Missing required fields (name, appointment_datetime, purpose)'], 400);
            }
            if (!valid_status($status)) { $conn->close(); respond(['error'=>'Invalid status'], 400); }

            $capacity = get_slot_capacity();

            // Capacity check for this exact slot timestamp
            $stmtCheck = $conn->prepare("SELECT COUNT(*) AS n FROM appointments WHERE appointment_datetime = ? AND status <> 'Cancelled'");
            if ($stmtCheck) {
                $stmtCheck->bind_param('s', $dt);
                $stmtCheck->execute();
                $res = $stmtCheck->get_result();
                $n = 0;
                if ($res) {
                    $rowN = $res->fetch_assoc();
                    $n = (int)($rowN['n'] ?? 0);
                }
                if ($n >= $capacity) {
                    $stmtCheck->close();
                    $dbg = $conn->prepare("SELECT id, status, appointment_datetime FROM appointments WHERE appointment_datetime = ? AND status <> 'Cancelled' LIMIT 5");
                    $conflicts = [];
                    if ($dbg) {
                        $dbg->bind_param('s', $dt);
                        $dbg->execute();
                        $dbgRes = $dbg->get_result();
                        $conflicts = $dbgRes ? $dbgRes->fetch_all(MYSQLI_ASSOC) : [];
                        $dbg->close();
                    }
                    $conn->close();
                    respond(['error'=>'This time slot is already booked', 'conflicts'=>$conflicts, 'dt'=>$dt], 409);
                }
                $stmtCheck->close();
            }

            $stmt = $conn->prepare("
                INSERT INTO appointments (name, email, phone, appointment_datetime, purpose, message, status)
                VALUES (?,?,?,?,?,?,?)
            ");
            if (!$stmt) { $conn->close(); respond(['error'=>'DB prepare failed'], 500); }
            $stmt->bind_param('sssssss', $name, $email, $phone, $dt, $purpose, $message, $status);
            if (!$stmt->execute()) { $stmt->close(); $conn->close(); respond(['error'=>'DB insert failed'], 500); }
            $newId = $stmt->insert_id;
            $stmt->close();

            $get = $conn->prepare("SELECT id, name, email, phone, appointment_datetime, purpose, message, status, created_at
                                   FROM appointments WHERE id = ?");
            $get->bind_param('i', $newId);
            $get->execute();
            $res = $get->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $get->close(); $conn->close();

            $row = $row ?? ['id' => $newId];
            $row['email_attempted'] = false;

            $sendEmails = function_exists('env_flag') ? env_flag('SEND_EMAILS', false) : (getenv('SEND_EMAILS') === '1');
            if ($sendEmails) {
                if (!empty($row['email']) && filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    $row['email_attempted'] = true;
                    $emailStart = microtime(true);
                    try {
                        require_once __DIR__ . '/../emails/appointment_summary.php';
                        $sendRes = send_appointment_summary_email($row, ['brand' => 'Appointment']);
                        $row['email_time_ms'] = (int)round((microtime(true) - $emailStart) * 1000);
                        if (!empty($sendRes['ok'])) {
                            $row['email_receipt'] = 'sent';
                            if (!empty($sendRes['messageId'])) $row['email_messageId'] = $sendRes['messageId'];
                            if (!empty($sendRes['subject'])) $row['email_subject'] = $sendRes['subject'];
                        } else {
                            $row['email_receipt'] = 'error';
                        }
                    } catch (Throwable $e) {
                        $row['email_time_ms'] = (int)round((microtime(true) - $emailStart) * 1000);
                        $row['email_receipt'] = 'error';
                        if (getenv('EMAIL_DEBUG') === '1') {
                            $row['email_error'] = $e->getMessage();
                        }
                    }
                } else {
                    $row['email_receipt'] = 'skipped';
                    $row['email_skipped_reason'] = 'invalid_email';
                }
            } else {
                $row['email_receipt'] = 'skipped';
                $row['email_skipped_reason'] = 'disabled';
            }

            respond($row, 201);
        }

        case 'update': {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($input['id'] ?? 0);
            if ($id <= 0) { $conn->close(); respond(['error'=>'Missing id'], 400); }

            if (!empty($input['appointment_datetime'])) {
                $newDt = trim((string)$input['appointment_datetime']);
                if ($newDt !== '') {
                    $capacity = get_slot_capacity();
                    $stmtCheck = $conn->prepare("SELECT COUNT(*) AS n FROM appointments WHERE appointment_datetime = ? AND status <> 'Cancelled' AND id <> ?");
                    if ($stmtCheck) {
                        $stmtCheck->bind_param('si', $newDt, $id);
                        $stmtCheck->execute();
                        $res = $stmtCheck->get_result();
                        $n = 0;
                        if ($res) {
                            $rowN = $res->fetch_assoc();
                            $n = (int)($rowN['n'] ?? 0);
                        }
                        if ($n >= $capacity) { $stmtCheck->close(); $conn->close(); respond(['error'=>'This time slot is already booked'], 409); }
                        $stmtCheck->close();
                    }
                }
            }

            $fields=[]; $types=''; $params=[];
            $map = [
                'name' => 's',
                'email' => 's',
                'phone' => 's',
                'appointment_datetime' => 's',
                'purpose' => 's',
                'message' => 's',
                'status' => 's',
            ];
            foreach ($map as $k=>$t) {
                if (array_key_exists($k,$input) && $input[$k] !== null && $input[$k] !== '') {
                    if ($k==='status' && !valid_status($input[$k])) { $conn->close(); respond(['error'=>'Invalid status'], 400); }
                    $fields[]="`$k`=?"; $types.=$t; $params[]=$input[$k];
                }
            }
            if (!$fields) { $conn->close(); respond(['error'=>'No fields to update'], 400); }

            $sql = "UPDATE appointments SET ".implode(',', $fields)." WHERE id = ?";
            $types.='i'; $params[]=$id;

            $stmt = $conn->prepare($sql);
            if (!$stmt) { $conn->close(); respond(['error'=>'DB prepare failed'], 500); }
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) { $stmt->close(); $conn->close(); respond(['error'=>'DB update failed'], 500); }
            $stmt->close();

            $get = $conn->prepare("SELECT id, name, email, phone, appointment_datetime, purpose, message, status, created_at
                                   FROM appointments WHERE id = ?");
            $get->bind_param('i', $id);
            $get->execute();
            $res = $get->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $get->close(); $conn->close();
            if (!$row) respond(['error'=>'Not found after update'], 404);

            // Send notification emails on status transitions
            $sendEmails = function_exists('env_flag') ? env_flag('SEND_EMAILS', false) : (getenv('SEND_EMAILS') === '1');
            if ($sendEmails && !empty($row['email']) && filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                try {
                    if (!empty($input['status'])) {
                        $newStatus = (string)$input['status'];
                        if ($newStatus === 'Confirmed') {
                            require_once __DIR__ . '/../emails/appointment_status_accept.php';
                            try { send_appointment_accepted_email($row, ['brand' => 'Appointment']); } catch (Throwable $e) {}
                        } elseif ($newStatus === 'Cancelled') {
                            require_once __DIR__ . '/../emails/appointment_status_cancel.php';
                            try { send_appointment_cancelled_email($row, ['brand' => 'Appointment']); } catch (Throwable $e) {}
                        }
                    }
                } catch (Throwable $e) { /* swallow email errors in API */ }
            }

            respond($row);
        }

        case 'delete': {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($input['id'] ?? 0);
            if ($id <= 0) { $conn->close(); respond(['error'=>'Missing id'], 400); }
            $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
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
            respond(['error'=>'Unknown or missing action. Use action=list|get|create|update|delete|free_slots|probe'], 400);
    }
} catch (Throwable $e) {
    respond(['error'=>'Server error', 'detail' => $e->getMessage()], 500);
}