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
/* card cell background is a white -> #fbfdff gradient; check against #fbfdff */
foreach (['#667c92', '#63798f', '#5f768c', '#5b7389', '#0f2340', '#5c7590'] as $c) {
    printf("%s : on #fff %s | on #fbfdff %s\n", $c, ratio($c, '#ffffff'), ratio($c, '#fbfdff'));
}
