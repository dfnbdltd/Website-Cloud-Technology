<?php
/* TEMP: log the local dev browser straight into the throwaway row-check
   customer so the filled My Domains page can be inspected. Deleted once the
   visual check is done. */

$token = 'rowchecklogin1';

$path = __DIR__ . '/../routes/web.php';
$s = str_replace("\r\n", "\n", file_get_contents($path));

if (strpos($s, '__probe-rowcheck') === false) {
    $anchor = "// TEMP-PROBE: local-only session seed for coming-soon verification.";
    if (strpos($s, $anchor) === false) {
        fwrite(STDERR, "anchor missing\n");
        exit(1);
    }
    $inject = <<<PHP
// TEMP-PROBE: local-only sign-in for the My Domains visual check.
Route::get('/__probe-rowcheck', function () {
    Auth::loginUsingId(App\User::where('email', 'rowcheck.check@example.test')->value('id'));
    \$user = Auth::user();
    session([
        'customer' => [
            'id' => \$user->id, 'name' => \$user->name, 'email' => \$user->email,
            'initials' => 'RC', 'customer_code' => 'CT-' . \$user->id, 'status' => 'active', 'balance' => 0,
        ],
        'domain_cart' => [],
    ]);
    return redirect('/mystore/domains');
})->withoutMiddleware([\\App\\Http\\Middleware\\EnsureCustomer::class]);

PHP;
    $s = str_replace($anchor, $inject . $anchor, $s);
    file_put_contents($path, str_replace("\n", "\r\n", $s));
    echo "probe route added\n";
} else {
    echo "probe route already present\n";
}
