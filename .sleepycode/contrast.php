<?php
function lum($hex) {
    $hex = ltrim($hex, '#');
    $c = array_map(function ($v) { return hexdec($v) / 255; }, str_split($hex, 2));
    $lin = array_map(function ($v) { return $v <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4); }, $c);
    return 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
}
function ratio($a, $b) {
    $la = lum($a); $lb = lum($b);
    return round((max($la, $lb) + 0.05) / (min($la, $lb) + 0.05), 2);
}
foreach (['#8ea2b9', '#8da0b5', '#7a8ea5', '#74899f', '#6f8497', '#687d92', '#a9bccf', '#93a7bc', '#8698ab', '#0f2340'] as $c) {
    printf("%s on white = %s\n", $c, ratio($c, '#ffffff'));
}
