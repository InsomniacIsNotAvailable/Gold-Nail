<?php
declare(strict_types=1);

// Minimal .env loader (no Composer dependency)
$root = dirname(__DIR__, 2);
$candidates = [
    $root . DIRECTORY_SEPARATOR . '.env',
    $root . DIRECTORY_SEPARATOR . 'ini .env', // fallback for your current file name
];
$envFile = null;
foreach ($candidates as $f) {
    if (is_readable($f)) { $envFile = $f; break; }
}

$vars = [];
if ($envFile) {
    $parsed = parse_ini_file($envFile, false, INI_SCANNER_TYPED) ?: [];
    if (is_array($parsed)) {
        $vars = $parsed;
        foreach ($parsed as $k => $v) {
            if (!isset($_ENV[$k]) && !isset($_SERVER[$k])) {
                putenv("$k=$v");
                $_ENV[$k] = $v;
                $_SERVER[$k] = $v;
            }
        }
    }
}

function env_or(array $vars, string $key, $default = null) {
    if (array_key_exists($key, $vars)) return $vars[$key];
    $v = getenv($key);
    return ($v !== false && $v !== null && $v !== '') ? $v : $default;
}

return [
    'base_url'   => env_or($vars, 'METAL_PRICE_API_BASE_URL', 'https://api.metalpriceapi.com/v1/latest'),
    'api_key'    => env_or($vars, 'METAL_PRICE_API_KEY', ''),
    'base'       => env_or($vars, 'METAL_PRICE_API_BASE', 'USD'),
    'currencies' => env_or($vars, 'METAL_PRICE_API_CURRENCIES', 'XAU'),
    'timeout'    => (int)env_or($vars, 'METAL_PRICE_API_TIMEOUT', 10),
];