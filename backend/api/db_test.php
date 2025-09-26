<?php
// Database connection test
header('Content-Type: text/plain');

echo "Testing database connection...\n";

// Test 1: Check if mysqli extension is loaded
if (!extension_loaded('mysqli')) {
    echo "ERROR: MySQLi extension is not loaded\n";
    exit;
}
echo "✓ MySQLi extension is loaded\n";

// Test 2: Try to connect to MySQL
try {
    $servername = "127.0.0.1";
    $username = "root";
    $password = "";
    $port = 3306;
    
    echo "Attempting to connect to MySQL at {$servername}:{$port}...\n";
    
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = mysqli_init();
    
    if (!$conn) {
        echo "ERROR: mysqli_init() failed\n";
        exit;
    }
    
    // Try to connect
    $result = mysqli_real_connect($conn, $servername, $username, $password, null, $port);
    
    if (!$result) {
        echo "ERROR: Failed to connect to MySQL: " . mysqli_connect_error() . "\n";
        echo "Error code: " . mysqli_connect_errno() . "\n";
        exit;
    }
    
    echo "✓ Connected to MySQL successfully\n";
    
    // Test 3: Check if database exists
    $db_name = 'goldnaildb';
    $result = mysqli_query($conn, "SHOW DATABASES LIKE '{$db_name}'");
    
    if (!$result) {
        echo "ERROR: Failed to query databases: " . mysqli_error($conn) . "\n";
        exit;
    }
    
    $db_exists = mysqli_num_rows($result) > 0;
    
    if (!$db_exists) {
        echo "Database '{$db_name}' does not exist. Creating it...\n";
        
        if (mysqli_query($conn, "CREATE DATABASE {$db_name} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
            echo "✓ Database '{$db_name}' created successfully\n";
        } else {
            echo "ERROR: Failed to create database: " . mysqli_error($conn) . "\n";
            exit;
        }
    } else {
        echo "✓ Database '{$db_name}' already exists\n";
    }
    
    // Test 4: Connect to the specific database
    mysqli_select_db($conn, $db_name);
    echo "✓ Connected to database '{$db_name}'\n";
    
    // Test 5: Create the gold_ohlc table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS gold_ohlc (
      id INT AUTO_INCREMENT PRIMARY KEY,
      `date` DATE NOT NULL,
      `open` DECIMAL(18,6) NOT NULL,
      `high` DECIMAL(18,6) NOT NULL,
      `low`  DECIMAL(18,6) NOT NULL,
      `close` DECIMAL(18,6) NOT NULL,
      UNIQUE KEY uq_gold_ohlc_date (`date`)
    )";
    
    if (mysqli_query($conn, $create_table_sql)) {
        echo "✓ Table 'gold_ohlc' is ready\n";
    } else {
        echo "ERROR: Failed to create table: " . mysqli_error($conn) . "\n";
    }
    
    // Test 6: Insert some sample data
    $sample_data = [
        ['2025-09-25', 4812.00, 4826.00, 4804.00, 4815.00],
        ['2025-09-26', 4820.00, 4834.00, 4812.00, 4826.00]
    ];
    
    $insert_sql = "INSERT IGNORE INTO gold_ohlc (`date`, `open`, `high`, `low`, `close`) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    
    if ($stmt) {
        foreach ($sample_data as $data) {
            mysqli_stmt_bind_param($stmt, 'sdddd', $data[0], $data[1], $data[2], $data[3], $data[4]);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
        echo "✓ Sample data inserted\n";
    }
    
    mysqli_close($conn);
    echo "\nAll tests passed! Database is ready.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>