<?php

namespace Tests\Feature;

use App\Models\DomainOrder;
use App\Models\DomainOrderItem;
use App\Services\DomainRegistrationService;
use App\Services\ResellerClubClient;
use App\Support\ResellerClubDomainContacts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DomainRegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.resellerclub.nameservers' => ['ns1.example.test', 'ns2.example.test'],
            'services.resellerclub.reg_contact_id' => 'registration-contact',
            'services.resellerclub.admin_contact_id' => 'admin-contact',
            'services.resellerclub.tech_contact_id' => 'tech-contact',
            'services.resellerclub.billing_contact_id' => 'billing-contact',
        ]);
    }

    private function paidOrder()
    {
        $order = DomainOrder::create([
            'reference' => 'CT-REG-001',
            'total' => 1800,
            'status' => 'paid',
            'payment_method' => 'bkash',
            'payment_status' => 'paid',
            'provider' => 'bkash',
            'customer_name' => 'Test Customer',
            'customer_phone' => '01700000000',
            'customer_email' => 'customer@example.test',
        ]);
        DomainOrderItem::create([
            'domain_order_id' => $order->id,
            'domain' => 'example.com',
            'tld' => 'com',
            'years' => 1,
            'price' => 1800,
            'renewal_price' => 1800,
            'purchase_price' => 1800,
        ]);

        return $order;
    }

    public function testPaidOrderIsRegisteredOnceThroughResellerClub()
    {
        config([
            'services.resellerclub.auto_register' => true,
            'services.resellerclub.customer_id' => 'fixed-registrant',
        ]);
        config(['services.test_domains' => []]);
        $order = $this->paidOrder();

        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldReceive('isConfigured')->once()->andReturn(true);
        $resellerClub->shouldReceive('walletBalance')->once()->andReturn([
            'balance' => 20.00,
            'currency' => 'USD',
        ]);
        $resellerClub->shouldReceive('catalog')->once()->andReturn([
            'com' => ['classkey' => 'domcno'],
        ]);
        $resellerClub->shouldReceive('liveRegistrationPrice')->once()->andReturn(13.09);
        $resellerClub->shouldReceive('registerDomain')
            ->once()
            ->with('example.com', 1)
            ->andReturn(['status' => 'Success']);
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $service = $this->app->make(DomainRegistrationService::class);
        $service->registerPaidOrder($order);
        $service->registerPaidOrder($order->fresh());

        $this->assertDatabaseHas('domain_orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'status' => 'active',
        ]);
    }

    public function testFailedResellerClubRegistrationRemainsPending()
    {
        config([
            'services.resellerclub.auto_register' => true,
            'services.resellerclub.customer_id' => 'fixed-registrant',
        ]);
        config(['services.test_domains' => []]);
        $order = $this->paidOrder();

        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldReceive('isConfigured')->once()->andReturn(true);
        $resellerClub->shouldReceive('walletBalance')->once()->andReturn([
            'balance' => 20.00,
            'currency' => 'USD',
        ]);
        $resellerClub->shouldReceive('catalog')->once()->andReturn([
            'com' => ['classkey' => 'domcno'],
        ]);
        $resellerClub->shouldReceive('liveRegistrationPrice')->once()->andReturn(13.09);
        $resellerClub->shouldReceive('registerDomain')
            ->once()
            ->with('example.com', 1)
            ->andReturn(['status' => 'ERROR', 'message' => 'Insufficient funds']);
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $this->app->make(DomainRegistrationService::class)->registerPaidOrder($order);

        $this->assertDatabaseHas('domain_orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'status' => 'registration_pending',
        ]);
    }

    public function testAdminCanRetryRegistrationForPaidPendingOrder()
    {
        config([
            'services.resellerclub.customer_id' => 'fixed-registrant',
            'services.test_domains' => [],
        ]);
        Cache::put('exchange_rate_usd_bdt', [
            'rate' => 120,
            'source' => 'test',
            'updated_at' => now()->toIso8601String(),
        ], 600);
        $order = $this->paidOrder();
        $order->update(['status' => 'registration_pending']);

        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldReceive('isConfigured')->once()->andReturn(true);
        $resellerClub->shouldReceive('walletBalance')->once()->andReturn([
            'balance' => 20.00,
            'currency' => 'USD',
        ]);
        $resellerClub->shouldReceive('catalog')->once()->andReturn([
            'com' => ['classkey' => 'domcno'],
        ]);
        $resellerClub->shouldReceive('liveRegistrationPrice')->once()->andReturn(13.09);
        $resellerClub->shouldReceive('registerDomain')
            ->once()
            ->with('example.com', 1)
            ->andReturn(['status' => 'Success']);
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->get(route('admin.orders'))
            ->assertOk()
            ->assertSee('Retry registration');

        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->post(route('admin.orders.retry-registration', $order))
            ->assertRedirect(route('admin.orders'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('domain_orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'status' => 'active',
            'total' => 1800,
        ]);
        $this->assertDatabaseHas('domain_order_items', [
            'domain_order_id' => $order->id,
            'domain' => 'example.com',
            'registration_status' => 'registered',
        ]);
    }

    public function testCheckoutWalletPreflightRejectsInsufficientResellerFunds()
    {
        config([
            'services.resellerclub.auto_register' => true,
            'services.resellerclub.enabled' => true,
            'services.resellerclub.auth_userid' => 'test-reseller',
            'services.resellerclub.api_key' => 'test-key',
            'services.resellerclub.customer_id' => 'fixed-registrant',
            'services.resellerclub.price_currency' => 'USD',
            'services.test_domains' => [],
        ]);

        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldReceive('isConfigured')->once()->andReturn(true);
        $resellerClub->shouldReceive('walletBalance')->once()->andReturn([
            'balance' => 12.00,
            'currency' => 'USD',
        ]);
        $resellerClub->shouldReceive('catalog')->once()->andReturn([
            'com' => ['classkey' => 'domcno'],
        ]);
        $resellerClub->shouldReceive('liveRegistrationPrice')
            ->once()
            ->with('domcno', 1)
            ->andReturn(13.09);
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $message = $this->app->make(DomainRegistrationService::class)->walletPreflightError([
            ['domain' => 'example.com', 'years' => 1],
        ]);

        $this->assertStringContainsString('does not have enough available funds', $message);
    }

    public function testCheckoutPreflightRejectsMissingDomainNameservers()
    {
        config([
            'services.resellerclub.enabled' => true,
            'services.resellerclub.auth_userid' => 'test-reseller',
            'services.resellerclub.api_key' => 'test-key',
            'services.resellerclub.customer_id' => 'fixed-registrant',
            'services.resellerclub.nameservers' => [],
            'services.test_domains' => [],
        ]);

        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldReceive('isConfigured')->once()->andReturn(true);
        $resellerClub->shouldNotReceive('walletBalance');
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $message = $this->app->make(DomainRegistrationService::class)->walletPreflightError([
            ['domain' => 'example.com', 'years' => 1],
        ]);

        $this->assertStringContainsString('Domain nameservers are not configured', $message);
    }

    public function testCheckoutPreflightRejectsMissingDomainContactIds()
    {
        config([
            'services.resellerclub.enabled' => true,
            'services.resellerclub.auth_userid' => 'test-reseller',
            'services.resellerclub.api_key' => 'test-key',
            'services.resellerclub.customer_id' => 'fixed-registrant',
            'services.resellerclub.reg_contact_id' => null,
            'services.resellerclub.admin_contact_id' => null,
            'services.resellerclub.tech_contact_id' => null,
            'services.resellerclub.billing_contact_id' => null,
            'services.test_domains' => [],
        ]);

        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldReceive('isConfigured')->once()->andReturn(true);
        $resellerClub->shouldNotReceive('walletBalance');
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $message = $this->app->make(DomainRegistrationService::class)->walletPreflightError([
            ['domain' => 'example.com', 'years' => 1],
        ]);

        $this->assertStringContainsString('domain contact IDs are not fully configured', $message);
    }

    public function testDomainRegistrationUsesTldSpecificContactOverrides()
    {
        $contacts = [
            'reg' => 'registrant-1',
            'admin' => 'admin-1',
            'tech' => 'tech-1',
            'billing' => 'billing-1',
        ];

        $euContacts = ResellerClubDomainContacts::forDomain('example.EU', $contacts);
        $this->assertSame('-1', $euContacts['admin-contact-id']);
        $this->assertSame('-1', $euContacts['tech-contact-id']);
        $this->assertSame('-1', $euContacts['billing-contact-id']);
        $this->assertSame('registrant-1', $euContacts['reg-contact-id']);

        $frContacts = ResellerClubDomainContacts::forDomain('example.fr', $contacts);
        $this->assertSame('admin-1', $frContacts['admin-contact-id']);
        $this->assertSame('-1', $frContacts['tech-contact-id']);
        $this->assertSame('-1', $frContacts['billing-contact-id']);

        $ukContacts = ResellerClubDomainContacts::forDomain('example.uk', $contacts);
        $this->assertSame('-1', $ukContacts['admin-contact-id']);
        $this->assertSame('-1', $ukContacts['tech-contact-id']);
        $this->assertSame('-1', $ukContacts['billing-contact-id']);

        $comContacts = ResellerClubDomainContacts::forDomain('example.com', $contacts);
        $this->assertSame('admin-1', $comContacts['admin-contact-id']);
        $this->assertSame('tech-1', $comContacts['tech-contact-id']);
        $this->assertSame('billing-1', $comContacts['billing-contact-id']);
    }

    public function testTldContactOverridesAllowUnavailableRoleIds()
    {
        $this->assertNotNull(ResellerClubDomainContacts::forDomain('example.eu', [
            'reg' => 'registrant-1',
            'admin' => null,
            'tech' => null,
            'billing' => null,
        ]));
        $this->assertNull(ResellerClubDomainContacts::forDomain('example.com', [
            'reg' => 'registrant-1',
            'admin' => null,
            'tech' => null,
            'billing' => null,
        ]));
    }

    public function testCheckoutWalletPreflightAllowsSufficientResellerFunds()
    {
        config([
            'services.resellerclub.auto_register' => true,
            'services.resellerclub.enabled' => true,
            'services.resellerclub.auth_userid' => 'test-reseller',
            'services.resellerclub.api_key' => 'test-key',
            'services.resellerclub.customer_id' => 'fixed-registrant',
            'services.resellerclub.price_currency' => 'USD',
            'services.test_domains' => [],
        ]);

        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldReceive('isConfigured')->once()->andReturn(true);
        $resellerClub->shouldReceive('walletBalance')->once()->andReturn([
            'balance' => 20.00,
            'currency' => 'USD',
        ]);
        $resellerClub->shouldReceive('catalog')->once()->andReturn([
            'com' => ['classkey' => 'domcno'],
        ]);
        $resellerClub->shouldReceive('liveRegistrationPrice')
            ->once()
            ->with('domcno', 1)
            ->andReturn(13.09);
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $this->assertNull($this->app->make(DomainRegistrationService::class)->walletPreflightError([
            ['domain' => 'example.com', 'years' => 1],
        ]));
    }

    public function testResellerClubAcceptsLegacyApiKeyEnvironmentConfiguration()
    {
        config([
            'services.resellerclub.enabled' => true,
            'services.resellerclub.auth_userid' => 'test-reseller',
            'services.resellerclub.api_key' => null,
            'services.resellerclub.legacy_api_key' => 'legacy-test-key',
        ]);

        $client = new ResellerClubClient();

        $this->assertTrue($client->isConfigured());
    }

    public function testInsufficientResellerWalletStopsCheckoutBeforePaymentOrderIsCreated()
    {
        $preflight = \Mockery::mock(DomainRegistrationService::class);
        $preflight->shouldReceive('walletPreflightError')
            ->once()
            ->andReturn('The ResellerClub account wallet does not have enough available funds.');
        $this->app->instance(DomainRegistrationService::class, $preflight);

        $this->withSession([
            'domain_cart' => [
                'example.com' => [
                    'domain' => 'example.com',
                    'price' => 1800,
                    'purchase_price' => 1800,
                    'years' => 1,
                ],
            ],
        ])->from(route('domains.checkout'))->post(route('domains.place-order'), [
            'payment_method' => 'bkash',
            'purchase_for' => 'myself',
            'full_name' => 'Test Customer',
            'email' => 'customer@example.test',
            'phone' => '01700000000',
        ])->assertRedirect(route('domains.checkout'))
            ->assertSessionHasErrors('payment_method');

        $this->assertSame(0, \DB::table('domain_orders')->count());
        $this->assertSame(0, \DB::table('payments')->count());
    }
}
