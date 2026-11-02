<?php
// TEMPORARY verification helper (deleted after use): a throwaway customer so
// the sidebar group-highlight scenario can be driven in the browser.
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Hash;

$email = 'highlight.check@example.test';

if (!App\User::where('email', $email)->exists()) {
    $user = new App\User();
    $user->name = 'Highlight Check';
    $user->first_name = 'Highlight';
    $user->last_name = 'Check';
    $user->email = $email;
    $user->password = Hash::make('Check#12345');
    $user->email_verified_at = now();
    $user->customer_uid = App\User::generateUid();
    $user->balance = 0;
    $user->save();
}

echo "email={$email}\npassword=Check#12345\n";
