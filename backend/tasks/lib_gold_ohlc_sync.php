<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
$metalCfg = require __DIR__ . '/../config/metal_price_api_config.php';

function extract_ohlc(array $payload, string $currency): ?array {
    // Prefer provider's "rate" shape you showed
    if (isset($payload['rate']) && is_array($payload['rate'])) {
        $r = $payload['rate'];
        if (isset($r['open'],$r['high'],$r['low'],$r['close'])
            && is_numeric($r['open']) && is_numeric($r['high']) && is_numeric($r['low']) && is_numeric($r['close'])) {
            return [
                'open'  => (float)$r['open'],
                'high'  => (float)$r['high'],
                'low'   => (float)$r['low'],
                'close' => (float)$r['close'],
            ];
        }
    }

    // Other shapes as fallback
    $candidates = [];
    if (isset($payload['ohlc']) && is_array($payload['ohlc'])) $candidates[] = $payload['ohlc'];
    if (isset($payload['rates']) && is_array($payload['rates'])) {
        $rates = $payload['rates'];
        if (isset($rates[$currency]) && is_array($rates[$currency])) $candidates[] = $rates[$currency];
        if (isset($rates['open'],$rates['high'],$rates['low'],$rates['close'])) $candidates[] = $rates;
        foreach ($rates as $v) {
            if (is_array($v) && isset($v['open'],$v['high'],$v['low'],$v['close'])) { $candidates[] = $v; break; }
        }
    }
    if (isset($payload['data']) && is_array($payload['data'])) $candidates[] = $payload['data'];
    $candidates[] = $payload;

    foreach ($candidates as $cand) {
        if (is_array($cand)
            && isset($cand['open'],$cand['high'],$cand['low'],$cand['close'])
            && is_numeric($cand['open']) && is_numeric($cand['high']) && is_numeric($cand['low']) && is_numeric($cand['close'])) {
            return [
                'open'  => (float)$cand['open'],
                'high'  => (float)$cand['high'],
                'low'   => (float)$cand['low'],
                'close' => (float)$cand['close'],
            ];
        }
    }

    // Deep recursive fallback
    $stack = [$payload];
    while ($stack) {
        $cur = array_pop($stack);
        if (!is_array($cur)) continue;
        if (isset($cur['open'],$cur['high'],$cur['low'],$cur['close'])
            && is_numeric($cur['open']) && is_numeric($cur['high']) && is_numeric($cur['low']) && is_numeric($cur['close'])) {
            return [
                'open'  => (float)$cur['open'],
                'high'  => (float)$cur['high'],
                'low'   => (float)$cur['low'],
                'close' => (float)$cur['close'],
            ];
        }
        foreach ($cur as $v) if (is_array($v)) $stack[] = $v;
    }
    return null;
}

function sync_ohlc_for_date(string $date, string $base = 'XAU', string $currency = 'PHP'): array {
    global $metalCfg;

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new InvalidArgumentException('Invalid date format. Use YYYY-MM-DD.');
    }

    $apiKey = (string)($metalCfg['api_key'] ?? '');
    if ($apiKey === '') throw new RuntimeException('Missing METAL_PRICE_API_KEY in .env');

    // Build OHLC endpoint
    $baseUrl = (string)($metalCfg['base_url'] ?? 'https://api.metalpriceapi.com/v1/latest');
    $endpoint = preg_replace('#/latest/?$#', '/ohlc', rtrim($baseUrl, '/')) ?: (rtrim($baseUrl, '/') . '/ohlc');
    if (!preg_match('#/ohlc$#', $endpoint)) $endpoint = rtrim($baseUrl, '/') . '/ohlc';

    $qs = http_build_query([
        'api_key'  => $apiKey,
        'base'     => $base,
        'currency' => $currency,
        'date'     => $date,
    ], '', '&', PHP_QUERY_RFC3986);

    // Fetch with a quick retry
    $timeout = max(10, (int)($metalCfg['timeout'] ?? 20));
    $attempts = 0; $body = false; $err = ''; $code = 0;
    do {
        $attempts++;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint . '?' . $qs,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($body !== false && !$err && $code < 400) break;
        usleep(300000); // 300ms backoff then retry once
    } while ($attempts < 2);

    if ($err || $code >= 400 || $body === false) {
        throw new RuntimeException($err ?: "Provider HTTP $code");
    }

    $payload = json_decode((string)$body, true);
    if (!is_array($payload)) throw new RuntimeException('Invalid JSON from provider');

    // Surface provider-declared errors
    if (array_key_exists('success', $payload) && $payload['success'] === false) {
        $msg = is_array($payload['error'] ?? null) ? (($payload['error']['message'] ?? json_encode($payload['error']))) : (string)($payload['error'] ?? 'unknown');
        throw new RuntimeException("Provider error: {$msg}");
    }

    $oh = extract_ohlc($payload, $currency);
    if ($oh === null) {
        $topKeys = implode(',', array_keys($payload));
        throw new RuntimeException("Provider payload missing OHLC (keys: $topKeys)");
    }

    // Upsert into DB
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

    $sql = "INSERT INTO gold_ohlc (`date`, `open`, `high`, `low`, `close`)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              `open`=VALUES(`open`), `high`=VALUES(`high`), `low`=VALUES(`low`), `close`=VALUES(`close`)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { $conn->close(); throw new RuntimeException('DB prepare failed'); }

    $stmt->bind_param('sdddd', $date, $oh['open'], $oh['high'], $oh['low'], $oh['close']);
    if (!$stmt->execute()) { $stmt->close(); $conn->close(); throw new RuntimeException('DB upsert failed'); }
    $stmt->close();

    $get = $conn->prepare("SELECT id, `date`, `open`, `high`, `low`, `close` FROM gold_ohlc WHERE `date` = ?");
    $get->bind_param('s', $date);
    $get->execute();
    $res = $get->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $get->close(); $conn->close();

    return ['stored' => $row, 'provider' => ['endpoint' => $endpoint, 'query' => $qs]];
}