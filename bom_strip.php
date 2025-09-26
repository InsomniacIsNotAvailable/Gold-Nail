<?php
$f = __DIR__ . DIRECTORY_SEPARATOR . 'GoldGraph.php';
$b = file_get_contents($f);
if (substr($b, 0, 3) === "\xEF\xBB\xBF") {
  file_put_contents($f, substr($b, 3));
  echo "BOM removed";
} else {
  echo "No BOM found";
}