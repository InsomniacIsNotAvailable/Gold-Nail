<?php
// Alternative configuration using SQLite for development
// This can be used when MySQL is not available

// Enable emails (set in multiple superglobals to be safe)
putenv('SEND_EMAILS=1');
$_ENV['SEND_EMAILS'] = '1';
$_SERVER['SEND_EMAILS'] = '1';

function env_flag(string $key, bool $default = false): bool {
    $v = getenv($key);
    if ($v === false || $v === '') {
        $v = $_SERVER[$key] ?? $_ENV[$key] ?? null;
    }
    if ($v === null) return $default;
    $s = strtolower((string)$v);
    return in_array($s, ['1','true','on','yes'], true);
}

function get_connection() {
    // First try MySQL
    try {
        $servername = "127.0.0.1";
        $username = "root";
        $password = "";
        $database = "goldnaildb";
        $port = 3306;

        // Check if MySQLi extension is loaded
        if (extension_loaded('mysqli')) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = mysqli_init();
            if (defined('MYSQLI_OPT_INT_AND_FLOAT_NATIVE')) {
                mysqli_options($conn, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
            }
            
            // Test connection
            @mysqli_real_connect($conn, $servername, $username, $password, $database, $port);
            mysqli_set_charset($conn, 'utf8mb4');
            return $conn;
        }
    } catch (Exception $e) {
        // MySQL connection failed, fall back to SQLite
    }
    
    // Fallback to SQLite
    return get_sqlite_connection();
}

function get_sqlite_connection() {
    $db_path = __DIR__ . '/goldnail.sqlite';
    
    try {
        $pdo = new PDO('sqlite:' . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create the gold_ohlc table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS gold_ohlc (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            date DATE NOT NULL UNIQUE,
            open DECIMAL(18,6) NOT NULL,
            high DECIMAL(18,6) NOT NULL,
            low DECIMAL(18,6) NOT NULL,
            close DECIMAL(18,6) NOT NULL
        )");
        
        // Insert some sample data
        $sample_data = [
            ['2025-09-25', 2650.00, 2670.00, 2640.00, 2665.00],
            ['2025-09-26', 2665.00, 2680.00, 2655.00, 2675.00]
        ];
        
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO gold_ohlc (date, open, high, low, close) VALUES (?, ?, ?, ?, ?)");
        foreach ($sample_data as $data) {
            $stmt->execute($data);
        }
        
        return $pdo;
    } catch (Exception $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

function console_log($msg) {
    echo "<script>console.log(" . json_encode($msg) . ");</script>";
}
?>