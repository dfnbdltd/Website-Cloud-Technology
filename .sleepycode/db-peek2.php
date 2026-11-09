<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DomainOrder;

foreach (App\User::take(10)->get() as $u) {
    $paid = DomainOrder::where('user_id', $u->id)->where('payment_status', 'paid')->count();
    echo "user {$u->id} {$u->email} paidOrders={$paid}\n";
}

echo "--- paid orders with items ---\n";
foreach (DomainOrder::where('payment_status', 'paid')->take(6)->get() as $o) {
    echo "order {$o->id} user={$o->user_id} items=" . $o->items->count() . ' ' . $o->items->pluck('domain')->implode(',') . "\n";
}
