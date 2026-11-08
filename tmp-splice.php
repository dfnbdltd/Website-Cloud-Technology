<?php
function crlf($text)
{
    return str_replace("\n", "\r\n", str_replace("\r\n", "\n", $text));
}

$bladePath = 'resources/views/store/domain-details.blade.php';
$lines = file($bladePath);

$markup = crlf(rtrim(file_get_contents('tmp-ns-markup.blade.php')) . "\n");
$script = crlf(rtrim(file_get_contents('tmp-ns-script.js')) . "\n");

$before = implode('', array_slice($lines, 0, 37));
$middle = implode('', array_slice($lines, 213, 21));
$tail = implode('', array_slice($lines, 458));

file_put_contents($bladePath, $before . $markup . $middle . $script . $tail);

$cssPath = 'public/css/store.css';
$cssLines = file($cssPath);

$block = crlf(rtrim(file_get_contents('tmp-ns.css')) . "\n");
$media = crlf(rtrim(file_get_contents('tmp-ns-media.css')) . "\n");

$cssBefore = implode('', array_slice($cssLines, 0, 182));
$cssMiddle = implode('', array_slice($cssLines, 336, 32));
$cssTail = implode('', array_slice($cssLines, 372));

file_put_contents($cssPath, $cssBefore . $block . $cssMiddle . $media . $cssTail);

echo "spliced\n";
echo 'blade lines: ' . count(file($bladePath)) . PHP_EOL;
echo 'css lines: ' . count(file($cssPath)) . PHP_EOL;
