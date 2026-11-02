<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminApiConnectionsTest extends TestCase
{
    public function testApiConnectionsPageRequiresAdminLogin()
    {
        $this->get(route('admin.apis'))->assertRedirect(route('admin.login'));
    }

    public function testAdminCanSeeApiEndpointsAndCredentialConfigurationWithoutSecrets()
    {
        Cache::put('exchange_rate_usd_bdt', [
            'rate' => 124.15,
            'source' => 'live',
            'updated_at' => now()->toIso8601String(),
        ], 600);

        config([
            'services.resellerclub.enabled' => true,
            'services.resellerclub.auth_userid' => 'reseller-user-id',
            'services.resellerclub.api_key' => 'reseller-secret-value',
            'services.resellerclub.reseller_id' => 'reseller-account-id',
            'services.resellerclub.customer_id' => 'registrant-customer-id',
            'services.bkash.app_key' => 'bkash-app-key-secret',
            'services.bkash.app_secret' => 'bkash-app-secret-value',
            'services.bkash.username' => 'bkash-username',
            'services.bkash.password' => 'bkash-password-secret',
            'services.recaptcha.secret' => 'recaptcha-secret-value',
        ]);

        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->get(route('admin.apis'))
            ->assertOk()
            ->assertSee('API connections')
            ->assertSee('API connections', false)
            ->assertSee('admin-exchange-rate')
            ->assertSee('1 USD = ৳124.15')
            ->assertSee('ResellerClub')
            ->assertSee('/products/customer-price.json')
            ->assertSee('/billing/reseller-balance.json')
            ->assertSee('/domains/available.json')
            ->assertSee('/domains/register.json')
            ->assertSee('/checkout/payment/create')
            ->assertSee('RESELLERCLUB_HTTP_API_KEY')
            ->assertSee('Automatic domain registration')
            ->assertSee('Configured')
            ->assertDontSee('reseller-user-id')
            ->assertDontSee('reseller-secret-value')
            ->assertDontSee('reseller-account-id')
            ->assertDontSee('registrant-customer-id')
            ->assertDontSee('bkash-app-key-secret')
            ->assertDontSee('bkash-app-secret-value')
            ->assertDontSee('bkash-username')
            ->assertDontSee('bkash-password-secret')
            ->assertDontSee('recaptcha-secret-value');
    }
}
