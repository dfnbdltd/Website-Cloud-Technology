<?php
/* TEMP: find a user that owns a registered domain item, and give the local dev
   browser a session for them. Removed after the visual check. */
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DomainOrder;
use App\Models\DomainOrderItem;

$item = DomainOrderItem::where('registration_status', 'registered')
    ->whereHas('order', function ($q) {
        $q->where('payment_status', 'paid')->where('status', 'active')->whereNotNull('user_id');
    })
    ->first();

if (!$item) {
    echo "no registered item\n";
    exit(0);
}

$order = $item->order;
echo "item={$item->id} domain={$item->domain} user={$order->user_id} reference={$order->reference}\n";
echo "manage_url=/mystore/domains/{$item->id}\n";
