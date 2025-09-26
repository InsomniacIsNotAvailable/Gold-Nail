<?php
$path = $argv[1] ?? null;
if (!$path) { echo "Usage: php backend/bom_strip.php <file>\n"; exit(1); }
$c = file_get_contents($path);
if (substr($c,0,3) === "\xEF\xBB\xBF") {
    file_put_contents($path, substr($c,3));
    echo "BOM removed: $path\n";
} else {
    echo "No BOM: $path\n";
}