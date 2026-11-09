<?php
/* TEMP: local-only sign-in as the owner of item 36 so the nameserver form can
   be exercised in a real browser. Added and removed inside this task. */
$path = __DIR__ . '/../routes/web.php';
$s = str_replace("\r\n", "\n", file_get_contents($path));

if (strpos($s, '__probe-nsform') !== false) {
    echo "already present\n";
    exit(0);
}

$anchor = "// TEMP-PROBE: local-only session seed for coming-soon verification.";
if (strpos($s, $anchor) === false) {
    fwrite(STDERR, "anchor missing\n");
    exit(1);
}

$inject = <<<'PHP'
// TEMP-PROBE: local-only sign-in for the nameserver form check.
Route::get('/__probe-nsform', function () {
    $user = App\Models\DomainOrderItem::whereKey(36)->first();
    $order = $user ? $user->order : null;
    $account = $order ? App\User::find($order->user_id) : App\User::first();
    session([
        'customer' => [
            'id' => $account->id, 'name' => $account->name, 'email' => $account->email,
            'initials' => 'NS', 'customer_code' => 'CT-' . $account->id, 'status' => 'active', 'balance' => 0,
        ],
        'domain_cart' => [],
    ]);
    return redirect('/mystore/domains/36');
});

PHP;

$s = str_replace($anchor, $inject . $anchor, $s);
file_put_contents($path, str_replace("\n", "\r\n", $s));
echo "probe added\n";
