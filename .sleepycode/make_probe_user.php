<?php
/**
 * Temp helper (project convention): create a throwaway verified customer,
 * print its email so the browser can log in and check the client-area
 * variant of the restyled domain search bar. Caller deletes the user after.
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Hash;

$probe = 'searchbar.check@example.test';
$user = App\User::firstOrCreate(
    ['email' => $probe],
    [
        'name' => 'Search Bar Check',
        'password' => Hash::make('Check#12345'),
        'email_verified_at' => now(),
    ]
);
echo $probe . "\n";
