<?php
/* Throwaway transfer order so the transfer card's inline facts can be checked. */
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\User::where('email', 'rowcheck.check@example.test')->firstOrFail();

$order = App\Models\DomainOrder::create([
    'reference' => 'ROWCHECK-TRANSFER',
    'user_id' => $user->id,
    'total' => 1600,
    'status' => 'transfer_pending',
    'payment_method' => 'bkash',
    'payment_status' => 'paid',
    'provider' => 'bkash',
    'customer_name' => $user->name,
    'customer_phone' => '01700000000',
    'customer_email' => $user->email,
]);

$order->items()->create([
    'domain' => 'incoming-domain.com',
    'tld' => 'com',
    'years' => 1,
    'price' => 1600,
    'renewal_price' => 1600,
    'order_type' => 'transfer',
    'registration_status' => 'pending',
]);

echo "transfer order={$order->id}\n";
