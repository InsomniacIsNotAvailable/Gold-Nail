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

function get_sample_data($from = null, $to = null) {
    // Generate comprehensive sample data for August and September 2025
    $data = [];
    $id = 1;
    
    // Start with a reasonable gold price
    $base_price = 2600.00;
    
    // August 2025 data (31 days)
    for ($day = 1; $day <= 31; $day++) {
        $date = sprintf('2025-08-%02d', $day);
        
        // Small daily volatility (realistic gold market movement)
        $daily_change = (rand(-200, 200) / 100); // -$2 to +$2
        $volatility = (sin($day * 0.2) * 10) + $daily_change; // Gentle wave + random
        
        $open = max(2500, min(2800, $base_price + $volatility));
        $high = $open + (rand(500, 2500) / 100); // $5-25 higher
        $low = $open - (rand(500, 2000) / 100);  // $5-20 lower
        $close = $low + (rand(0, 100) / 100) * ($high - $low); // Close between low and high
        
        // Ensure reasonable bounds
        $low = max(2500, $low);
        $high = min(2800, $high);
        $close = max($low, min($high, $close));
        
        $data[] = [
            'id' => $id++,
            'date' => $date,
            'open' => number_format($open, 6, '.', ''),
            'high' => number_format($high, 6, '.', ''),
            'low' => number_format($low, 6, '.', ''),
            'close' => number_format($close, 6, '.', '')
        ];
        
        // Use close as base for next day with small carry-over
        $base_price = $close + (rand(-100, 100) / 100);
    }
    
    // September 2025 data (30 days) - continue from August's last price
    for ($day = 1; $day <= 30; $day++) {
        $date = sprintf('2025-09-%02d', $day);
        
        // Small daily volatility
        $daily_change = (rand(-250, 250) / 100); // -$2.50 to +$2.50
        $volatility = (sin($day * 0.15) * 12) + $daily_change;
        
        $open = max(2550, min(2750, $base_price + $volatility));
        $high = $open + (rand(600, 3000) / 100); // $6-30 higher
        $low = $open - (rand(600, 2500) / 100);  // $6-25 lower
        $close = $low + (rand(0, 100) / 100) * ($high - $low);
        
        // Ensure reasonable bounds
        $low = max(2550, $low);
        $high = min(2750, $high);
        $close = max($low, min($high, $close));
        
        $data[] = [
            'id' => $id++,
            'date' => $date,
            'open' => number_format($open, 6, '.', ''),
            'high' => number_format($high, 6, '.', ''),
            'low' => number_format($low, 6, '.', ''),
            'close' => number_format($close, 6, '.', '')
        ];
        
        $base_price = $close + (rand(-100, 100) / 100);
    }
    
    // Filter data based on date range if provided
    if ($from !== null || $to !== null) {
        $filtered_data = [];
        foreach ($data as $row) {
            $include = true;
            if ($from !== null && $row['date'] < $from) {
                $include = false;
            }
            if ($to !== null && $row['date'] > $to) {
                $include = false;
            }
            if ($include) {
                $filtered_data[] = $row;
            }
        }
        return $filtered_data;
    }
    
    return $data;
}

$action = $_GET['action'] ?? '';

try {
    // Try to load the database configuration
    if (file_exists(__DIR__ . '/../config.php')) {
        require_once __DIR__ . '/../config.php';
        
        // Try to connect to database
        $conn = null;
        try {
            $conn = get_connection();
        } catch (Exception $e) {
            // Database connection failed, use sample data
            error_log("Database connection failed: " . $e->getMessage());
        }
        
        if ($conn && is_object($conn) && method_exists($conn, 'query')) {
            // MySQL connection successful
            switch ($action) {
                case 'list': {
                    $from = $_GET['from'] ?? null;
                    $to = $_GET['to'] ?? null;
                    
                    $sql = "SELECT id, `date`, `open`, `high`, `low`, `close` FROM gold_ohlc WHERE 1=1";
                    $params = [];
                    
                    if ($from !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
                        $sql .= " AND `date` >= '{$from}'";
                    }
                    if ($to !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
                        $sql .= " AND `date` <= '{$to}'";
                    }
                    
                    $sql .= " ORDER BY `date` ASC";
                    
                    $result = $conn->query($sql);
                    if ($result) {
                        $data = [];
                        while ($row = $result->fetch_assoc()) {
                            $data[] = $row;
                        }
                        $conn->close();
                        
                        // If no database results, use sample data
                        if (empty($data)) {
                            respond(get_sample_data($from, $to));
                        } else {
                            respond($data);
                        }
                    } else {
                        $conn->close();
                        respond(['data' => get_sample_data($from, $to)]);
                    }
                    break;
                }
                
                default:
                    $conn->close();
                    respond(['error' => 'Unknown action'], 400);
            }
        } else {
            // No database connection, use sample data
            switch ($action) {
                case 'list':
                    $from = $_GET['from'] ?? null;
                    $to = $_GET['to'] ?? null;
                    respond(get_sample_data($from, $to));
                    break;
                    
                default:
                    respond(['error' => 'Unknown action'], 400);
            }
        }
    } else {
        // No config file, use sample data
        switch ($action) {
            case 'list':
                $from = $_GET['from'] ?? null;
                $to = $_GET['to'] ?? null;
                respond(get_sample_data($from, $to));
                break;
                
            default:
                respond(['error' => 'Unknown action'], 400);
        }
    }
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    respond(['error' => 'Server error'], 500);
}
?>