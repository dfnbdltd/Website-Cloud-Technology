<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\User::where('email', 'rowcheck.check@example.test')->first();
$order = App\Models\DomainOrder::where('reference', 'ROWCHECK-ORDER')->first();
echo 'user=' . ($user ? $user->id : 'none') . ' order=' . ($order ? $order->id : 'none') . "\n";
if ($order) {
    echo 'items=' . $order->items->count() . ' ' . $order->items->pluck('domain')->implode(',') . "\n";
}
