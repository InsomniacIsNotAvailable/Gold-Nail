<?php
$path = $argv[1] ?? 'backend';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
foreach ($it as $f) {
    if (!$f->isFile()) continue;
    $ext = strtolower($f->getExtension());
    if (!in_array($ext, ['php','js','css','html','env','txt'])) continue;
    $h = fopen($f->getPathname(), 'rb');
    $bytes = fread($h, 3);
    fclose($h);
    if ($bytes === "\xEF\xBB\xBF") {
        echo "BOM: {$f->getPathname()}\n";
    }
}