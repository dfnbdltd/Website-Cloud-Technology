<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo 'orders=' . App\Models\DomainOrder::count() . ' users=' . App\User::count() . "\n";
foreach (App\Models\DomainOrder::take(10)->get() as $o) {
    echo "id={$o->id} ref={$o->reference} status={$o->status} user={$o->user_id}\n";
}
