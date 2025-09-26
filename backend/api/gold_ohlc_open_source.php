<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../config.php';

function respond($data, int $code = 200): void { http_response_code($code); echo json_encode($data); exit; }

function ymd($s){ return preg_match('/^\d{4}-\d{2}-\d{2}$/',$s)?$s:null; }

// Simple CSV fetcher
function fetch_csv(string $url): array {
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL=>$url, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10]);
    $body = curl_exec($ch); $err=curl_error($ch); $code=(int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch);
    if ($err || $code>=400 || !$body) return [];
    $lines = array_values(array_filter(array_map('trim', explode("\n", $body))));
    $out=[]; foreach($lines as $i=>$ln){ if($ln==='') continue; $cols=str_getcsv($ln);
        $out[]=$cols; }
    return $out;
}

function parse_csv_string(string $data): array {
    $lines = array_values(array_filter(array_map('trim', explode("\n", $data))));
    $out=[]; foreach($lines as $i=>$ln){ if($ln==='') continue; $cols=str_getcsv($ln); $out[]=$cols; }
    return $out;
}

function load_daily_series_from_csv(string $path): array {
    if (!is_readable($path)) return [];
    $body = file_get_contents($path);
    if ($body === false || $body === '') return [];
    $csv = parse_csv_string($body);
    if (!$csv || count($csv) < 2) return [];
    $hdr = array_map('strtolower', $csv[0]);
    $rows = array_slice($csv, 1);
    $map = [];
    foreach ($hdr as $idx=>$name) $map[$name]=$idx;
    $out=[];
        foreach ($rows as $r) {
            $d = $r[$map['date']??0]??null;
            $o = isset($r[$map['open']??1]) ? (float)$r[$map['open']??1] : null;
            $h = isset($r[$map['high']??2]) ? (float)$r[$map['high']??2] : null;
            $l = isset($r[$map['low'] ??3]) ? (float)$r[$map['low'] ??3] : null;
            $c = isset($r[$map['close']??4]) ? (float)$r[$map['close']??4] : null;
            if (!$d || !$o || !$h || !$l || !$c) continue; // require positive values
            $out[$d] = ['open'=>$o, 'high'=>$h, 'low'=>$l, 'close'=>$c];
        }
    return $out;
}

// Try stooq public CSV mirrors; if blocked, this will return empty and we can fallback
function load_daily_series(string $symbol): array {
    // stooq direct links often are: https://stooq.com/q/d/l/?s=xauusd&i=d
    // Some setups block stooq; attempt via a known mirror pattern if needed
    $urls = [
        'https://stooq.com/q/d/l/?s=' . urlencode($symbol) . '&i=d',
        'https://stooq.pl/q/d/l/?s=' . urlencode($symbol) . '&i=d',
    ];
    foreach ($urls as $u) {
        $csv = fetch_csv($u);
        // Expect header: Date,Open,High,Low,Close or lowercase variant
        if (!$csv || count($csv) < 2) continue;
        $hdr = array_map('strtolower', $csv[0]);
        $rows = array_slice($csv, 1);
        $map = [];
        foreach ($hdr as $idx=>$name) $map[$name]=$idx;
        $out=[];
            foreach ($rows as $r) {
                $d = $r[$map['date']??0]??null;
                $o = isset($r[$map['open']??1]) ? (float)$r[$map['open']??1] : null;
                $h = isset($r[$map['high']??2]) ? (float)$r[$map['high']??2] : null;
                $l = isset($r[$map['low'] ??3]) ? (float)$r[$map['low'] ??3] : null;
                $c = isset($r[$map['close']??4]) ? (float)$r[$map['close']??4] : null;
                if (!$d || !$o || !$h || !$l || !$c) continue; // require positive values
                $out[$d] = ['open'=>$o, 'high'=>$h, 'low'=>$l, 'close'=>$c];
            }
        if ($out) return $out;
    }
    return [];
}

function php_per_gram_from_xauusd(array $xauusd, array $usdphp, string $date): ?array {
    // both are per ounce in USD and FX USD/PHP; convert to PHP/gram
    $ozToG = 31.1034768;
    $x = $xauusd[$date] ?? null; $u = $usdphp[$date] ?? null;
    if (!$x || !$u) return null;
    $mul = (float)$u['close'];
    return [
        'open'  => ($x['open']  * $mul) / $ozToG,
        'high'  => ($x['high']  * $mul) / $ozToG,
        'low'   => ($x['low']   * $mul) / $ozToG,
        'close' => ($x['close'] * $mul) / $ozToG,
    ];
}

try {
    $input = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
    $dates = $input['dates'] ?? ($_GET['dates'] ?? []);
    if (is_string($dates)) { $dates = array_map('trim', explode(',', $dates)); }
    $dates = array_values(array_filter(array_map('ymd', (array)$dates)));
    if (!$dates) respond(['error'=>'Provide dates as ["YYYY-MM-DD", ...] or ?dates=...'], 400);

    // Optional manual inputs
    $manualXau = is_array($input['manual']['xauusd'] ?? null) ? $input['manual']['xauusd'] : [];
    $manualFx  = is_array($input['manual']['usdphp'] ?? null) ? $input['manual']['usdphp'] : [];
    $manualPhpGram = is_array($input['phpgram'] ?? null) ? $input['phpgram'] : [];

    // Optional local CSV paths
    $csvPaths = is_array($input['csvPaths'] ?? null) ? $input['csvPaths'] : [];
    $csvXauPath = is_string($csvPaths['xauusd'] ?? null) ? $csvPaths['xauusd'] : null;
    $csvFxPath  = is_string($csvPaths['usdphp'] ?? null) ? $csvPaths['usdphp'] : null;

    // Optional overwrite toggle (default true here since this is a corrective tool)
    $overwriteParam = $_GET['overwrite'] ?? $input['overwrite'] ?? 'true';
    $overwrite = filter_var($overwriteParam, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($overwrite === null) $overwrite = true;

    $xau = $csvXauPath ? load_daily_series_from_csv($csvXauPath) : load_daily_series('xauusd');
    $fx  = $csvFxPath  ? load_daily_series_from_csv($csvFxPath)  : load_daily_series('usdphp');

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

    $sql = $overwrite
        ? "INSERT INTO gold_ohlc (`date`, `open`, `high`, `low`, `close`) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `open`=VALUES(`open`),`high`=VALUES(`high`),`low`=VALUES(`low`),`close`=VALUES(`close`)"
        : "INSERT IGNORE INTO gold_ohlc (`date`, `open`, `high`, `low`, `close`) VALUES (?, ?, ?, ?, ?)";
    $insert = $conn->prepare($sql);
    if (!$insert) { $conn->close(); respond(['error'=>'DB prepare failed'], 500); }

    $ok=0; $missing=[]; $stored=[];
    foreach ($dates as $d) {
        // Priority: direct PHP/gram manual -> manual USD/oz+USDPHP -> open-source fetch
        $ohlc = null;
        if (isset($manualPhpGram[$d]) && is_array($manualPhpGram[$d])) {
            $m = $manualPhpGram[$d];
            if (isset($m['open'],$m['high'],$m['low'],$m['close'])) {
                $ohlc = [
                    'open'=>(float)$m['open'], 'high'=>(float)$m['high'],
                    'low'=>(float)$m['low'], 'close'=>(float)$m['close']
                ];
            }
        }
        if ($ohlc === null && (isset($manualXau[$d]) || isset($manualFx[$d]))) {
            $xx = isset($manualXau[$d]) && is_array($manualXau[$d]) ? $manualXau[$d] : ($xau[$d] ?? null);
            $uu = isset($manualFx[$d])  && is_array($manualFx[$d])  ? $manualFx[$d]  : ($fx[$d]  ?? null);
            if ($xx && $uu && isset($xx['open'],$xx['high'],$xx['low'],$xx['close']) && isset($uu['close'])) {
                $ohlc = [
                    'open'  => ($xx['open']  * (float)$uu['close']) / 31.1034768,
                    'high'  => ($xx['high']  * (float)$uu['close']) / 31.1034768,
                    'low'   => ($xx['low']   * (float)$uu['close']) / 31.1034768,
                    'close' => ($xx['close'] * (float)$uu['close']) / 31.1034768,
                ];
            }
        }
        if ($ohlc === null) {
            $ohlc = php_per_gram_from_xauusd($xau, $fx, $d);
        }
        if (!$ohlc) { $missing[]=$d; continue; }
        $insert->bind_param('sdddd', $d, $ohlc['open'], $ohlc['high'], $ohlc['low'], $ohlc['close']);
        if ($insert->execute()) { $ok++; $stored[$d]=$ohlc; }
    }
    $insert->close(); $conn->close();
    respond(['success'=>true,'stored'=>$ok,'overwrite'=>$overwrite,'dates'=>$dates,'missing'=>$missing,'preview'=>$stored]);
} catch (Throwable $e) {
    respond(['error'=>'Server error','detail'=>$e->getMessage()], 500);
}

?>
