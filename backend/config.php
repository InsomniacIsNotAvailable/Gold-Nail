<?php
// Enable emails (set in multiple superglobals to be safe)
putenv('SEND_EMAILS=1');
$_ENV['SEND_EMAILS'] = '1';
$_SERVER['SEND_EMAILS'] = '1';

// Optional: surface errors from email sending in API JSON
// putenv('EMAIL_DEBUG=1'); $_ENV['EMAIL_DEBUG']='1'; $_SERVER['EMAIL_DEBUG']='1';

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
    $servername = "127.0.0.1";
    $username = "root";
    $password = "";
    $database = "goldnaildb";
    $port = 3306;

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = mysqli_init();
    if (defined('MYSQLI_OPT_INT_AND_FLOAT_NATIVE')) {
        mysqli_options($conn, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
    }
    mysqli_real_connect($conn, $servername, $username, $password, $database, $port);
    mysqli_set_charset($conn, 'utf8mb4');
    return $conn;
}

function console_log($msg) {
    echo "<script>console.log(" . json_encode($msg) . ");</script>";
}