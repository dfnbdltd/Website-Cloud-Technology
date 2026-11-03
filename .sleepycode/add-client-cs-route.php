<?php
$path = __DIR__ . '/../routes/web.php';
$raw = file_get_contents($path);
$eol = (strpos($raw, "\r\n") !== false) ? "\r\n" : "\n";

if (strpos($raw, "client.coming-soon") !== false) {
    echo "already added\n";
    exit(0);
}

$anchor = "Route::get('/dashboard', [CustomerController::class, 'dashboard'])->name('dashboard');";

$block = implode($eol, [
    "    // Client-area \"coming soon\" page: sidebar service rows navigate here",
    "    // (the public navbar/footer keep using /services/{service} instead).",
    "    Route::get('/client/coming-soon/{service}', function (string \$service) {",
    "        \$services = config('coming_soon');",
    "        abort_unless(is_array(\$services) && isset(\$services[\$service]), 404);",
    "",
    "        \$back = (string) request()->query('back', '');",
    "        \$back = str_starts_with(\$back, '/') && ! str_starts_with(\$back, '//') ? \$back : null;",
    "",
    "        return view('store.coming-soon', [",
    "            'slug' => \$service,",
    "            'service' => \$services[\$service],",
    "            'backUrl' => \$back,",
    "            'navActive' => \$service,",
    "        ]);",
    "    })->name('client.coming-soon');",
]);

$replacement = $anchor . $eol . $block . $eol;

if (strpos($raw, $anchor) === false) {
    echo "anchor not found\n";
    exit(1);
}

$raw = str_replace($anchor, $replacement, $raw);
file_put_contents($path, $raw);
echo "route added\n";
