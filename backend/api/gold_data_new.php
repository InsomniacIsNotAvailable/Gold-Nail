<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function respond($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function get_realistic_gold_data($from = null, $to = null) {
    $data = [];
    $id = 1;
    
    // Start with realistic gold price around $2650
    $base_price = 2650.00;
    
    // August 2025 data (31 days)
    for ($day = 1; $day <= 31; $day++) {
        $date = sprintf('2025-08-%02d', $day);
        
        // Realistic daily gold price movements
        $daily_change = mt_rand(-150, 150) / 100; // -$1.50 to +$1.50
        $open = $base_price + $daily_change;
        
        // Intraday volatility
        $daily_range = mt_rand(800, 2000) / 100; // $8-20 daily range
        $high = $open + ($daily_range * 0.6);
        $low = $open - ($daily_range * 0.4);
        
        // Close somewhere in the range
        $close_position = mt_rand(0, 100) / 100;
        $close = $low + ($high - $low) * $close_position;
        
        // Keep prices in reasonable bounds
        $open = max(2550, min(2750, $open));
        $high = max(2550, min(2750, $high));
        $low = max(2550, min(2750, $low));
        $close = max(2550, min(2750, $close));
        
        // Ensure OHLC logic is correct
        $high = max($open, $close, $high);
        $low = min($open, $close, $low);
        
        $data[] = [
            'id' => $id++,
            'date' => $date,
            'open' => number_format($open, 6, '.', ''),
            'high' => number_format($high, 6, '.', ''),
            'low' => number_format($low, 6, '.', ''),
            'close' => number_format($close, 6, '.', '')
        ];
        
        $base_price = $close; // Next day starts from previous close
    }
    
    // September 2025 data (30 days)
    for ($day = 1; $day <= 30; $day++) {
        $date = sprintf('2025-09-%02d', $day);
        
        $daily_change = mt_rand(-180, 180) / 100; // -$1.80 to +$1.80
        $open = $base_price + $daily_change;
        
        $daily_range = mt_rand(900, 2200) / 100; // $9-22 daily range
        $high = $open + ($daily_range * 0.6);
        $low = $open - ($daily_range * 0.4);
        
        $close_position = mt_rand(0, 100) / 100;
        $close = $low + ($high - $low) * $close_position;
        
        // Keep prices in reasonable bounds
        $open = max(2580, min(2720, $open));
        $high = max(2580, min(2720, $high));
        $low = max(2580, min(2720, $low));
        $close = max(2580, min(2720, $close));
        
        // Ensure OHLC logic
        $high = max($open, $close, $high);
        $low = min($open, $close, $low);
        
        $data[] = [
            'id' => $id++,
            'date' => $date,
            'open' => number_format($open, 6, '.', ''),
            'high' => number_format($high, 6, '.', ''),
            'low' => number_format($low, 6, '.', ''),
            'close' => number_format($close, 6, '.', '')
        ];
        
        $base_price = $close;
    }
    
    // Filter by date range
    if ($from !== null || $to !== null) {
        $filtered_data = [];
        foreach ($data as $row) {
            $include = true;
            if ($from !== null && $row['date'] < $from) $include = false;
            if ($to !== null && $row['date'] > $to) $include = false;
            if ($include) $filtered_data[] = $row;
        }
        return $filtered_data;
    }
    
    return $data;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list': {
            $from = $_GET['from'] ?? null;
            $to = $_GET['to'] ?? null;
            respond(['data' => get_realistic_gold_data($from, $to)]);
            break;
        }
        
        default:
            respond(['error' => 'Unknown action'], 400);
    }
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    respond(['error' => 'Server error'], 500);
}
?>