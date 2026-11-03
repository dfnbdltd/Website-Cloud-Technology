<?php
$path = __DIR__ . '/../resources/views/partials/site-navbar.blade.php';
$raw = file_get_contents($path);

$old = 'urlencode(request()->fullUrl())';
$new = 'urlencode(request()->getRequestUri())';

$count = substr_count($raw, $old);
if ($count === 0) {
    echo "old not found (maybe already fixed)\n";
    exit(0);
}
$raw = str_replace($old, $new, $raw);
file_put_contents($path, $raw);
echo "replaced $count occurrences\n";
