<?php
$root = dirname(__DIR__, 2);
$envFile = $root . DIRECTORY_SEPARATOR . 'brevoenv';

$vars = [];

if (is_readable($envFile)) {
    $raw = file_get_contents($envFile);
    if ($raw !== false) {
        if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
            $raw = substr($raw, 3);
        }
        $raw = ltrim($raw, "\u{FEFF}");
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $lines = explode("\n", $raw);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || $line[0] === ';') continue;
            $eq = strpos($line, '=');
            if ($eq === false) continue;

            $key = trim(substr($line, 0, $eq));
            $val = trim(substr($line, $eq + 1));

            if ($val !== '' && (
                ($val[0] === '"' && substr($val, -1) === '"') ||
                ($val[0] === "'" && substr($val, -1) === "'")
            )) {
                $val = substr($val, 1, -1);
            }

            $key = ltrim($key, "\xEF\xBB\xBF\u{FEFF}");

            $vars[$key] = $val;

            if (!isset($_ENV[$key]) && getenv($key) === false) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

$env = static function (string $key, $default = null) use ($vars) {
    if (array_key_exists($key, $vars)) return $vars[$key];
    $v = getenv($key);
    return ($v !== false && $v !== null && $v !== '') ? $v : $default;
};

return [
    'api_key'       => $env('BREVO_API_KEY', ''),
    'sender_email'     => getenv('BREVO_SENDER_EMAIL') ?: '',
    'sender_name'      => getenv('BREVO_SENDER_NAME') ?: '',
    'reply_to'         => getenv('BREVO_REPLY_TO') ?: '',

    'store_address'    => getenv('BREVO_STORE_ADDRESS') ?: '',
    'store_landline'   => getenv('BREVO_STORE_LANDLINE') ?: '',
    'logo_url'         => getenv('BREVO_LOGO_URL') ?: '',
    'logo_path'        => getenv('BREVO_LOGO_PATH') ?: '',

    // Public link + signing (already present if you use cancel link)
    'public_base_url'  => getenv('PUBLIC_BASE_URL') ?: '',
    'link_secret'      => getenv('LINK_SECRET') ?: '',

    // New: optional map screenshot and a Google Maps link
    'map_image_url'    => getenv('STORE_MAP_IMAGE_URL') ?: '',
    'map_image_path'   => getenv('STORE_MAP_IMAGE_PATH') ?: '',
    'maps_link'        => getenv('STORE_MAPS_LINK') ?: '',
];