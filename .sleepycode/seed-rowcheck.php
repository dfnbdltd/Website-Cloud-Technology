<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// temporary throwaway customer with a paid domain order so the page renders filled
$user = App\User::create([
    'name' => 'Row Check',
    'email' => 'rowcheck.check@example.test',
    'password' => bcrypt('Check#12345'),
    'phone' => '01700000000',
    'status' => 'active',
]);

$order = App\Models\DomainOrder::create([
    'reference' => 'ROWCHECK-ORDER',
    'user_id' => $user->id,
    'total' => 2130,
    'status' => 'active',
    'payment_method' => 'bkash',
    'payment_status' => 'paid',
    'provider' => 'bkash',
    'customer_name' => $user->name,
    'customer_phone' => $user->phone,
    'customer_email' => $user->email,
    'created_at' => '2026-11-06 10:00:00',
    'updated_at' => '2026-11-06 10:00:00',
]);

$order->items()->create([
    'domain' => 'testcloudbd.com',
    'tld' => 'com',
    'years' => 1,
    'price' => 2130,
    'renewal_price' => 2130,
    'order_type' => 'registration',
    'registration_status' => 'registered',
]);

$order->items()->create([
    'domain' => 'second-row.net',
    'tld' => 'net',
    'years' => 2,
    'price' => 3200,
    'renewal_price' => 1600,
    'order_type' => 'registration',
    'registration_status' => 'registered',
]);

App\Models\Payment::create([
    'user_id' => $user->id,
    'domain_order_id' => $order->id,
    'amount' => 2130,
    'method' => 'bkash',
    'status' => 'completed',
    'reference' => 'ROWCHECK-PAY',
]);

echo "user={$user->id} order={$order->id} email={$user->email}\n";
