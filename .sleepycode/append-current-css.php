<?php
// TEMPORARY helper (deleted after use): append the "collapsed current group"
// styles to sidebar-nav.css once, preserving CRLF.
$target = __DIR__ . '/../public/css/sidebar-nav.css';
$block = file_get_contents(__DIR__ . '/subnav-current.css');

$css = str_replace("\r\n", "\n", file_get_contents($target));
$block = str_replace("\r\n", "\n", $block);

if (strpos($css, 'Collapsed group that still owns the current page') !== false) {
    fwrite(STDERR, "block already present\n");
    exit(1);
}

$css = rtrim($css) . "\n" . $block;
file_put_contents($target, str_replace("\n", "\r\n", $css));
echo 'bytes=', strlen($css), PHP_EOL;
