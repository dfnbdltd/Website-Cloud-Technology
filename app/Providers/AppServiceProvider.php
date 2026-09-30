<?php

namespace App\Providers;

use App\Models\MailSetup;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        try {
            if ($mailSetup = MailSetup::active()) {
                $mailSetup->apply();
            }
        } catch (\Throwable $e) {
            // No database yet (e.g. during install): keep the .env mail settings.
        }
    }
}
