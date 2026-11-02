<?php

namespace Tests\Feature;

use App\Services\ResellerClubClient;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DomainTermPricingTest extends TestCase
{
    use RefreshDatabase;

    private function customerSession()
    {
        $user = User::create([
            'name' => 'Test Customer',
            'email' => 'customer@example.test',
            'password' => Hash::make('SecretP@ss1'),
            'email_verified_at' => now(),
        ]);

        return ['customer' => [
            'id' => $user->id,
            'uid' => 'CT-1',
            'name' => 'Test Customer',
            'initials' => 'TC',
            'email' => 'customer@example.test',
            'phone' => '01700000000',
            'balance' => 0,
            'logo' => null,
            'plan' => 'BDIX Premium',
        ]];
    }

    public function testCustomerCanSelectApiPricedRegistrationTerm()
    {
        $catalog = [
            'com' => [
                'classkey' => 'domcno',
                'price' => 1214,
                'renewal' => 1399,
                'year_prices' => [1 => 1214, 2 => 2500, 3 => 3899],
            ],
        ];
        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldReceive('catalog')->times(3)->andReturn($catalog);
        $resellerClub->shouldReceive('isConfigured')->once()->andReturn(false);
        $resellerClub->shouldReceive('checkAvailability')
            ->once()
            ->with('brand', 'com')
            ->andReturn(['brand.com' => ['status' => 'available', 'classkey' => 'domcno']]);
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $session = $this->customerSession();
        $this->withSession($session)->post(route('domains.search'), ['domain' => 'brand.com'])
            ->assertRedirect(route('domains.index', ['domain' => 'brand.com']));

        $this->withSession($session)->get(route('domains.index', ['domain' => 'brand.com']))
            ->assertStatus(200)
            ->assertSee('data-sidebar-label="Domain Registration"')
            ->assertDontSee('client-domain-link')
            ->assertSee('/first year')
            ->assertSee('Renews ৳ 1,399/year')
            ->assertSee('No. of years')
            ->assertSee('2 years · ৳ 2,500')
            ->assertSee('3 years · ৳ 3,899')
            ->assertSee("years === 1 ? '/first year' : '/' + years + ' years'");

        $this->withSession($session)->post(route('domains.cart'), ['domain' => 'brand.com', 'years' => 2])
            ->assertRedirect();

        $cart = session('domain_cart');
        $this->assertSame(2, $cart['brand.com']['years']);
        $this->assertSame(1250.0, $cart['brand.com']['price']);
        $this->assertSame(1250.0, $cart['brand.com']['purchase_price']);
        $this->assertSame(1214.0, $cart['brand.com']['first_year_price']);
        $this->assertSame(1399.0, $cart['brand.com']['renewal_price']);

        $this->withSession(array_merge($session, ['domain_cart' => $cart]))->get(route('cart.index'))
            ->assertStatus(200)
            ->assertSee('৳ 2,500')
            ->assertSee('First year ৳ 1,214')
            ->assertSee('1,399/year');

        $this->withSession(array_merge($session, ['domain_cart' => $cart]))->withoutExceptionHandling()->get(route('domains.checkout'))
            ->assertStatus(200)
            ->assertSee('Total due')
            ->assertSee('৳ 2,500')
            ->assertSee('renews ৳ 1,399/year');
    }

    public function testUnsupportedRegistrationTermIsRejected()
    {
        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldReceive('catalog')->once()->andReturn([
            'com' => [
                'classkey' => 'domcno',
                'price' => 1214,
                'renewal' => 1399,
                'year_prices' => [1 => 1214],
            ],
        ]);
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $this->withSession($this->customerSession())->from(route('domains.index'))->post(route('domains.cart'), [
            'domain' => 'brand.com',
            'years' => 2,
        ])->assertRedirect(route('domains.index'))
            ->assertSessionHasErrors('domain');
    }

    public function testYearSelectorRemainsVisibleWhenAvailabilityLookupIsUnknown()
    {
        $resellerClub = \Mockery::mock(ResellerClubClient::class);
        $resellerClub->shouldReceive('catalog')->times(2)->andReturn([
            'com' => [
                'classkey' => 'domcno',
                'price' => 1214,
                'renewal' => 1399,
                'year_prices' => [1 => 1214, 2 => 2500],
            ],
        ]);
        $resellerClub->shouldReceive('isConfigured')->once()->andReturn(false);
        $resellerClub->shouldReceive('checkAvailability')->once()->andReturn(null);
        $this->app->instance(ResellerClubClient::class, $resellerClub);

        $session = $this->customerSession();
        $this->withSession($session)->post(route('domains.search'), ['domain' => 'brand.com'])
            ->assertRedirect(route('domains.index', ['domain' => 'brand.com']));

        $this->withSession($session)->get(route('domains.index', ['domain' => 'brand.com']))
            ->assertOk()
            ->assertSee('No. of years')
            ->assertSee('2 years · ৳ 2,500')
            ->assertSee('Could not check availability')
            ->assertSee('Not verified')
            ->assertSee('Pricing is still available.');
    }

    public function testCatalogUsesApiTermPricesAndApiRenewalRate()
    {
        config([
            'services.resellerclub.enabled' => true,
            'services.resellerclub.auth_userid' => 'test-user',
            'services.resellerclub.api_key' => 'test-key',
            'services.resellerclub.price_currency' => 'BDT',
        ]);
        Cache::put('resellerclub.pricing.v2', [
            'domcno' => [
                'addnewdomain' => ['1' => 1214, '2' => 2399],
                'renewdomain' => ['1' => 1399],
            ],
        ], 60);

        $plan = (new ResellerClubClient())->catalog(['domcno'])['com'];

        $this->assertSame(1214.0, $plan['price']);
        $this->assertSame(1399.0, $plan['renewal']);
        $this->assertSame(2399.0, $plan['year_prices'][2]);
        $this->assertSame(4012.0, $plan['year_prices'][3]);
    }
}
