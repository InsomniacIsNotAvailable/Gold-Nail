<?php
$f = __DIR__ . DIRECTORY_SEPARATOR . 'GoldGraph.php';
$h = fopen($f, 'rb');
$bytes = bin2hex(fread($h, 3));
fclose($h);
echo $bytes === 'efbbbf' ? "BOM present" : "No BOM";