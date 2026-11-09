<?php
$im = imagecreatefrompng(__DIR__ . '/shots/inline-desktop.png');
$w = imagesx($im);
$h = imagesy($im);
echo "img {$w}x{$h}\n";
foreach ([100, 400, 800, 1200, 1600, 1900] as $y) {
    $row = [];
    foreach ([60, 380, 900, 1400, 1860] as $x) {
        $c = imagecolorsforindex($im, imagecolorat($im, $x, $y));
        $row[] = sprintf("%4d: #%02x%02x%02x", $x, $c['red'], $c['green'], $c['blue']);
    }
    echo "y={$y}  " . implode('  ', $row) . "\n";
}
