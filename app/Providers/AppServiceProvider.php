<?php

namespace App\Providers;

use App\Services\ExchangeRateService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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

    public function boot()
    {
        View::composer('partials.admin-sidebar', function ($view) {
            $view->with('exchangeRate', app(ExchangeRateService::class)->usdToBdt());
        });
    }

}
