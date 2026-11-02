<?php
// TEMPORARY verification helper (deleted after use).
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Hash;

$email = 'tree.check@example.test';

if (!App\User::where('email', $email)->exists()) {
    $user = new App\User();
    $user->name = 'Tree Check';
    $user->first_name = 'Tree';
    $user->last_name = 'Check';
    $user->email = $email;
    $user->password = Hash::make('Check#12345');
    $user->email_verified_at = now();
    $user->customer_uid = App\User::generateUid();
    $user->balance = 0;
    $user->save();
}

echo "email={$email}\npassword=Check#12345\n";
