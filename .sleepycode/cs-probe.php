<?php
$path = __DIR__ . '/../routes/web.php';
$raw = file_get_contents($path);
$eol = (strpos($raw, "\r\n") !== false) ? "\r\n" : "\n";
if (strpos($raw, '__probe-login') !== false) { echo "already injected\n"; exit(0); }
$anchor = "use App\\Http\\Controllers\\MyStoreController;" . $eol;
$route = $eol .
    "// TEMP-PROBE: local-only session seed for coming-soon verification." . $eol .
    "Route::get('/__probe-login', function () {" . $eol .
    "    session(['customer' => [" . $eol .
    "        'id' => 1, 'name' => 'Aziz Muntasir', 'email' => 'aziz@example.test'," . $eol .
    "        'initials' => 'AM', 'customer_code' => 'CT-1', 'status' => 'active', 'balance' => 0," . $eol .
    "    ]]);" . $eol .
    "    session(['domain_cart' => []]);" . $eol .
    "    return redirect('/mystore');" . $eol .
    "});" . $eol;
$raw = str_replace($anchor, $anchor . $route, $raw);
file_put_contents($path, $raw);
echo "injected\n";
