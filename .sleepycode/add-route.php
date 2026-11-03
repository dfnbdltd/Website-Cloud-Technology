<?php
$path = __DIR__ . '/../routes/web.php';
$raw = file_get_contents($path);
$eol = (strpos($raw, "\r\n") !== false) ? "\r\n" : "\n";

if (strpos($raw, 'services.coming-soon') !== false) {
    echo "already added\n";
    exit(0);
}

$anchor = "})->name('under.construction');";

if (strpos($raw, $anchor) === false) {
    echo "ANCHOR NOT FOUND\n";
    exit(1);
}

$route = $anchor . $eol . $eol . implode($eol, [
    "// Public \"coming soon\" landing pages for services that are not live yet.",
    "// The slug is validated against config/coming_soon.php, so anything else",
    "// 404s instead of rendering an empty page. `back` carries where the visitor",
    "// came from so the primary button can return them exactly there.",
    "Route::get('/services/{service}', function (string $service) {",
    "    \$services = config('coming_soon');",
    "",
    "    abort_unless(is_array(\$services) && isset(\$services[\$service]), 404);",
    "",
    "    \$back = (string) request()->query('back', '');",
    "    // Only ever accept a local path: never redirect off-site.",
    "    \$back = str_starts_with(\$back, '/') && ! str_starts_with(\$back, '//') ? \$back : null;",
    "",
    "    return view('coming-soon', [",
    "        'slug' => \$service,",
    "        'service' => \$services[\$service],",
    "        'backUrl' => \$back,",
    "    ]);",
    "})->name('services.coming-soon');",
]);

$raw = str_replace($anchor, $route, $raw);
file_put_contents($path, $raw);
echo "route added\n";
