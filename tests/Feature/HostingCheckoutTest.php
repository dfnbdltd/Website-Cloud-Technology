<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Services\HostingDomainCheckService;
use App\Services\HostingOrderService;
use App\Services\ResellerClubClient;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Hosting purchase flow: plan card → connect domain name → checkout.
 * Place order (bKash) now saves the order; see HostingBkashController for the payment flow.
 *
 * Also covers the "hide the wallet for now" switch (services.wallet_enabled)
 * and the Myself details card that sits under the Myself option.
 */
class HostingCheckoutTest extends TestCase
{
    use RefreshDatabase;

    /** The ResellerClub stand-in: domain => status, plus a log of every lookup. */
    private $rc;

    protected function setUp(): void
    {
        parent::setUp();

        // These tests are about the real prices; the ৳1 test price has its own tests below.
        config([
            'services.hosting_plan_a_test_price' => 0,
            'services.resellerclub.hosting_domain_check' => true,
        ]);
        cache()->flush();

        $this->rc = new class extends ResellerClubClient {
            /** @var array  'example.com' => 'regthroughothers' | 'available' | 'unknown' */
            public $answers = ['example.com' => 'regthroughothers'];
            public $calls = [];

            public function checkAvailability($domain, $tld)
            {
                $key = $domain . '.' . $tld;
                $this->calls[] = $key;

                if (!isset($this->answers[$key])) {
                    return null; // lookup failed
                }

                return [$key => ['status' => $this->answers[$key], 'classkey' => 'domcno']];
            }
        };
        $this->app->instance(ResellerClubClient::class, $this->rc);
    }

    private function plan($region = 'local', $type = 'shared', $name = 'Plan A')
    {
        return HostingPlan::where('region', $region)->where('type', $type)->where('name', $name)->firstOrFail();
    }

    private function connectUrl(HostingPlan $plan, $period = 'monthly')
    {
        return route('hosting.connect', [
            'region' => $plan->region,
            'type' => $plan->type,
            'plan' => $plan->id,
            'period' => $period,
        ]);
    }

    private function user($name = 'Buyer One', $verified = true, $uid = null)
    {
        $user = new User([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '-' . uniqid() . '@example.test',
            'password' => Hash::make('SecretP@ss1'),
            'phone' => '01712345678',
        ]);
        $user->forceFill([
            'email_verified_at' => $verified ? now() : null,
            'customer_uid' => $uid,
        ])->save();

        return $user;
    }

    private function customerSession(User $user, array $extra = [])
    {
        return array_merge(['customer' => [
            'id' => $user->id,
            'uid' => $user->customer_uid,
            'name' => $user->name,
            'initials' => 'BO',
            'email' => $user->email,
            'phone' => (string) $user->phone,
            'balance' => 0,
            'logo' => null,
        ]], $extra);
    }

    private function cart(HostingPlan $plan, $period = 'monthly', $domain = 'example.com')
    {
        return ['hosting_cart' => [
            'region' => $plan->region,
            'type' => $plan->type,
            'plan' => (string) $plan->id,
            'period' => $period,
            'domain' => $domain,
            'domain_checked' => true,
        ]];
    }

    // ------------------------------------------------------------ plan cards

    public function test_public_plan_buttons_open_the_connect_page_not_register()
    {
        $plan = $this->plan();

        $this->get(route('hosting.region', ['region' => 'local', 'type' => 'shared']))
            ->assertOk()
            ->assertSee(e(route('hosting.connect', ['region' => 'local', 'type' => 'shared', 'plan' => $plan->id])), false);
    }

    public function test_client_area_buy_now_opens_the_connect_page()
    {
        $plan = $this->plan();
        $user = $this->user();

        $this->withSession($this->customerSession($user))
            ->get(route('client.hosting', 'shared'))
            ->assertOk()
            ->assertSee(e($this->connectUrl($plan, 'monthly')), false)
            ->assertDontSee('Buying opens a support request');
    }

    // --------------------------------------------------------- connect page

    public function test_connect_page_shows_the_plan_price_and_a_disabled_continue_button()
    {
        $plan = $this->plan();

        $this->get($this->connectUrl($plan))
            ->assertOk()
            ->assertSee('Connect Domain Name')
            ->assertSee('Enter a domain name to associate with the order')
            ->assertSee('Continue to Cart')
            ->assertSee('id="hostContinue"', false)
            ->assertSee('id="hostCheck"', false)
            ->assertSee('data-check-url="' . e(route('hosting.connect.check')) . '"', false)
            ->assertSee(HostingOrderService::money($plan->price_monthly))
            ->assertSee('Local Shared Hosting');
    }

    public function test_connect_page_accepts_the_wordpress_style_period_slug()
    {
        $this->assertSame('twoYear', HostingOrderService::normalizePeriod('two-year'));
        $this->assertSame('threeYear', HostingOrderService::normalizePeriod('three-year'));
        $this->assertSame('yearly', HostingOrderService::normalizePeriod('yearly'));
        $this->assertNull(HostingOrderService::normalizePeriod('weekly'));
    }

    public function test_connect_with_an_unknown_plan_goes_back_to_the_plan_list()
    {
        $this->get(route('hosting.connect', ['region' => 'local', 'type' => 'shared', 'plan' => 999999, 'period' => 'monthly']))
            ->assertRedirect(route('hosting.region', ['region' => 'local', 'type' => 'shared']));
    }

    public function test_connect_with_a_period_the_plan_does_not_sell_goes_back()
    {
        $plan = $this->plan();
        $plan->forceFill(['term_two_year_active' => false])->save();

        $this->get($this->connectUrl($plan, 'twoYear'))
            ->assertRedirect(route('hosting.region', ['region' => 'local', 'type' => 'shared']));
    }

    // ------------------------------------------------------------ the domain

    public function test_a_valid_domain_is_cleaned_saved_and_leads_to_checkout()
    {
        $plan = $this->plan();

        $this->post(route('hosting.connect.store'), [
            'region' => 'local',
            'type' => 'shared',
            'plan' => (string) $plan->id,
            'period' => 'monthly',
            'domain' => 'https://Example.COM/some/path',
        ])->assertRedirect(route('hosting.checkout'));

        $cart = session('hosting_cart');
        $this->assertSame('example.com', $cart['domain']);
        $this->assertSame((string) $plan->id, (string) $cart['plan']);
        $this->assertSame('monthly', $cart['period']);
        // Only identifiers are kept: never a price.
        $this->assertArrayNotHasKey('price', $cart);
        $this->assertArrayNotHasKey('total', $cart);
    }

    public function test_invalid_and_wildcard_domains_are_refused()
    {
        $plan = $this->plan();

        foreach (['not a domain', 'nodot', '*.example.com', ''] as $bad) {
            $this->from($this->connectUrl($plan))
                ->post(route('hosting.connect.store'), [
                    'region' => 'local',
                    'type' => 'shared',
                    'plan' => (string) $plan->id,
                    'period' => 'monthly',
                    'domain' => $bad,
                ])->assertSessionHasErrors('domain');

            $this->assertNull(session('hosting_cart'));
        }
    }

    // --------------------------------------------------------------- checkout

    public function test_guest_checkout_asks_to_sign_in_and_returns_to_checkout()
    {
        $plan = $this->plan();

        $this->withSession($this->cart($plan))
            ->get(route('hosting.checkout'))
            ->assertOk()
            ->assertSee('Sign in to continue')
            ->assertSee(e(route('login', ['return_to' => '/hosting/checkout'])), false)
            ->assertSee(e(route('register', ['return_to' => '/hosting/checkout'])), false)
            ->assertSee('example.com')
            ->assertDontSee('Who are you buying for?');
    }

    public function test_signed_in_checkout_shows_myself_details_before_customer_and_a_locked_button()
    {
        $plan = $this->plan();
        $user = $this->user('Buyer One');

        $response = $this->withSession($this->customerSession($user, $this->cart($plan)))
            ->get(route('hosting.checkout'))
            ->assertOk()
            ->assertSee('Who are you buying for?')
            ->assertSee('Using your account details')
            ->assertSee('Email or ID of existing customer')
            ->assertSee('Place order')
            ->assertSee(route('hosting.place-order'), false)
            ->assertSee('Server location')
            ->assertSee(HostingOrderService::money($plan->price_monthly));

        $html = $response->getContent();
        $myself = strpos($html, 'id="sslMyself"');
        $customer = strpos($html, 'value="customer"');
        $this->assertNotFalse($myself);
        $this->assertNotFalse($customer);
        $this->assertLessThan($customer, $myself, 'Myself details must come before the Customer option.');
        $this->assertStringContainsString($user->name, substr($html, $myself, 400));
        $this->assertStringContainsString('01712345678', substr($html, $myself, 600));
    }

    public function test_checkout_never_offers_the_wallet()
    {
        $plan = $this->plan();
        $user = $this->user();

        $this->withSession($this->customerSession($user, $this->cart($plan)))
            ->get(route('hosting.checkout'))
            ->assertOk()
            ->assertSee('bKash')
            ->assertSee('Nagad')
            ->assertDontSee('Pay with wallet')
            ->assertDontSee('Wallet balance');
    }

    public function test_checkout_with_a_tampered_cart_goes_back_instead_of_showing_a_price()
    {
        $plan = $this->plan();

        $this->withSession(['hosting_cart' => [
            'region' => 'local', 'type' => 'shared', 'plan' => '424242', 'period' => 'monthly', 'domain' => 'example.com',
        ]])->get(route('hosting.checkout'))
            ->assertRedirect(route('hosting.region', ['region' => 'local', 'type' => 'shared']));

        $this->assertNull(session('hosting_cart'));
    }

    public function test_checkout_without_a_cart_goes_back_to_the_plans()
    {
        $this->get(route('hosting.checkout'))
            ->assertRedirect(route('hosting.region', ['region' => 'local', 'type' => 'shared']));
    }

    public function test_there_is_no_route_that_places_a_hosting_order_yet()
    {
        $plan = $this->plan();
        $user = $this->user();

        $this->withSession($this->customerSession($user, $this->cart($plan)))
            ->post(route('hosting.checkout'), ['purchase_for' => 'myself', 'payment_method' => 'bkash'])
            ->assertStatus(405);
    }

    // ----------------------------------------------------- buying for a customer

    public function test_customer_lookup_needs_a_signed_in_customer_and_finds_existing_ones()
    {
        $buyer = $this->user('Buyer One');
        $customer = $this->user('Existing Customer', true, 'CT-000042');

        $this->postJson(route('hosting.customer.lookup'), ['identifier' => 'CT-000042'])
            ->assertStatus(401);

        $this->withSession($this->customerSession($buyer))
            ->postJson(route('hosting.customer.lookup'), ['identifier' => 'CT-000042'])
            ->assertOk()
            ->assertJson(['verified' => true]);

        $this->withSession($this->customerSession($buyer))
            ->postJson(route('hosting.customer.lookup'), ['identifier' => 'CT-999999'])
            ->assertStatus(404);
    }

    // ---------------------------------------------------------- wallet hidden

    public function test_wallet_links_are_hidden_by_default_everywhere()
    {
        $plan = $this->plan();
        $user = $this->user();
        $session = $this->customerSession($user, $this->cart($plan));

        $this->assertFalse((bool) config('services.wallet_enabled'));

        // Account menu in the navbar.
        $this->withSession($session)->get(route('hosting.checkout'))
            ->assertOk()
            ->assertDontSee('Wallet ·')
            ->assertDontSee(route('wallet.index') . '#add-balance', false);

        // Client-area sidebar + My Store overview.
        $this->withSession($session)->get(route('mystore'))
            ->assertOk()
            ->assertDontSee('Add Balance')
            ->assertDontSee('AVAILABLE BALANCE');
    }

    public function test_wallet_comes_back_when_the_switch_is_turned_on()
    {
        config(['services.wallet_enabled' => true]);

        $user = $this->user();

        $this->withSession($this->customerSession($user))->get(route('mystore'))
            ->assertOk()
            ->assertSee('Add Balance')
            ->assertSee('AVAILABLE BALANCE');
    }

    public function test_domain_checkout_has_no_wallet_option_and_refuses_a_wallet_post()
    {
        config(['services.test_domains' => ['example.com' => ['price' => 1200, 'renewal' => 1200]]]);

        $user = $this->user();
        $user->forceFill(['balance' => 5000])->save();
        $session = $this->customerSession($user, ['domain_cart' => ['example.com' => [
            'domain' => 'example.com',
            'order_type' => 'registration',
            'price' => 1200,
            'purchase_price' => null,
            'first_year_price' => 1200,
            'renewal_price' => 1200,
            'years' => 1,
        ]]]);

        $this->withSession($session)->get(route('domains.checkout'))
            ->assertOk()
            ->assertDontSee('Wallet balance')
            ->assertSee('Using your account details');

        $this->withSession($session)->post(route('domains.place-order'), [
            'purchase_for' => 'myself',
            'payment_method' => 'wallet',
        ])->assertSessionHasErrors('payment_method');

        $this->assertEquals(5000.0, (float) $user->fresh()->balance);
    }

    public function test_domain_checkout_shows_the_myself_card_before_the_customer_option()
    {
        config(['services.test_domains' => ['example.com' => ['price' => 1200, 'renewal' => 1200]]]);

        $user = $this->user('Buyer One');
        $session = $this->customerSession($user, ['domain_cart' => ['example.com' => [
            'domain' => 'example.com',
            'order_type' => 'registration',
            'price' => 1200,
            'purchase_price' => null,
            'first_year_price' => 1200,
            'renewal_price' => 1200,
            'years' => 1,
        ]]]);

        $html = $this->withSession($session)->get(route('domains.checkout'))->assertOk()->getContent();

        $summary = strpos($html, 'id="checkoutAccountSummary"');
        $customer = strpos($html, 'value="customer"');
        $this->assertNotFalse($summary);
        $this->assertLessThan($customer, $summary, 'The Myself details card must sit before the Customer option.');
    }
    // ------------------------------------------- domain must be registered

    private function storeDomain(HostingPlan $plan, $domain, $period = 'monthly')
    {
        return $this->from($this->connectUrl($plan, $period))
            ->post(route('hosting.connect.store'), [
                'region' => 'local',
                'type' => 'shared',
                'plan' => (string) $plan->id,
                'period' => $period,
                'domain' => $domain,
            ]);
    }

    public function test_check_says_ok_for_a_registered_domain()
    {
        $this->postJson(route('hosting.connect.check'), ['domain' => 'Example.com'])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'state' => 'ok',
                'domain' => 'example.com',
                'registrable' => 'example.com',
            ]);

        $this->assertSame(['example.com'], $this->rc->calls);
    }

    public function test_check_treats_regthroughus_as_registered_too()
    {
        $this->rc->answers['ours.com'] = 'regthroughus';

        $this->postJson(route('hosting.connect.check'), ['domain' => 'ours.com'])
            ->assertOk()
            ->assertJson(['ok' => true, 'state' => 'ok']);
    }

    public function test_check_refuses_a_domain_that_is_not_registered_and_points_to_registration()
    {
        $this->rc->answers['free-name.com'] = 'available';

        $this->postJson(route('hosting.connect.check'), ['domain' => 'free-name.com'])
            ->assertOk()
            ->assertJson([
                'ok' => false,
                'state' => 'available',
                'register_url' => route('domains.index', ['domain' => 'free-name.com']),
            ]);
    }

    public function test_check_refuses_when_resellerclub_cannot_be_read()
    {
        // 'nolookup.com' is not in the stand-in's answers: the API "failed".
        $this->postJson(route('hosting.connect.check'), ['domain' => 'nolookup.com'])
            ->assertOk()
            ->assertJson(['ok' => false, 'state' => 'unverified']);

        $this->rc->answers['weird.com'] = 'unknown';
        $this->postJson(route('hosting.connect.check'), ['domain' => 'weird.com'])
            ->assertOk()
            ->assertJson(['ok' => false, 'state' => 'unverified']);
    }

    public function test_check_refuses_an_invalid_domain_without_calling_resellerclub()
    {
        foreach (['not a domain', 'nodot', '*.example.com'] as $bad) {
            $this->postJson(route('hosting.connect.check'), ['domain' => $bad])
                ->assertOk()
                ->assertJson(['ok' => false, 'state' => 'invalid']);
        }

        $this->postJson(route('hosting.connect.check'), ['domain' => ''])->assertStatus(422);
        $this->assertSame([], $this->rc->calls);
    }

    public function test_a_failed_lookup_is_not_remembered_but_a_clear_answer_is()
    {
        // Failed lookup: asked again next time.
        $this->postJson(route('hosting.connect.check'), ['domain' => 'later.com'])->assertJson(['ok' => false]);
        $this->rc->answers['later.com'] = 'regthroughothers';
        $this->postJson(route('hosting.connect.check'), ['domain' => 'later.com'])->assertJson(['ok' => true]);
        $this->assertSame(['later.com', 'later.com'], $this->rc->calls);

        // Clear answer: the second request is served from the cache.
        $this->postJson(route('hosting.connect.check'), ['domain' => 'later.com'])->assertJson(['ok' => true]);
        $this->assertSame(['later.com', 'later.com'], $this->rc->calls);
    }

    public function test_a_subdomain_is_checked_by_its_registrable_name_but_kept_as_typed()
    {
        $plan = $this->plan();

        $this->storeDomain($plan, 'www.example.com')->assertRedirect(route('hosting.checkout'));

        $this->assertSame(['example.com'], $this->rc->calls);
        $this->assertSame('www.example.com', session('hosting_cart')['domain']);
    }

    public function test_two_part_suffixes_are_looked_up_whole()
    {
        $this->assertSame(['name' => 'example', 'tld' => 'com'], HostingDomainCheckService::registrableParts('www.shop.example.com'));
        $this->assertSame(['name' => 'example', 'tld' => 'com.bd'], HostingDomainCheckService::registrableParts('blog.example.com.bd'));
        $this->assertSame(['name' => 'example', 'tld' => 'com.bd'], HostingDomainCheckService::registrableParts('example.com.bd'));
        $this->assertNull(HostingDomainCheckService::registrableParts('com.bd'));
        $this->assertNull(HostingDomainCheckService::registrableParts('localhost'));
    }

    public function test_submitting_a_domain_that_is_not_registered_is_refused_by_the_server()
    {
        $plan = $this->plan();
        $this->rc->answers['free-name.com'] = 'available';

        $this->storeDomain($plan, 'free-name.com')->assertSessionHasErrors('domain');

        $this->assertNull(session('hosting_cart'));
    }

    public function test_submitting_when_the_lookup_fails_is_refused_by_the_server()
    {
        $plan = $this->plan();

        $this->storeDomain($plan, 'nolookup.com')->assertSessionHasErrors('domain');

        $this->assertNull(session('hosting_cart'));
    }

    public function test_a_registered_domain_is_saved_as_checked()
    {
        $plan = $this->plan();

        $this->storeDomain($plan, 'example.com')->assertRedirect(route('hosting.checkout'));

        $this->assertTrue(session('hosting_cart')['domain_checked']);
    }

    public function test_checkout_sends_an_unchecked_cart_back_to_the_connect_page()
    {
        $plan = $this->plan();
        $cart = $this->cart($plan);
        unset($cart['hosting_cart']['domain_checked']);

        $this->withSession($cart)
            ->get(route('hosting.checkout'))
            ->assertRedirect(route('hosting.connect', [
                'region' => 'local',
                'type' => 'shared',
                'plan' => (string) $plan->id,
                'period' => 'monthly',
            ]));
    }

    public function test_connect_page_keeps_the_tick_for_a_domain_already_checked_for_this_plan()
    {
        $plan = $this->plan();

        $this->withSession($this->cart($plan))
            ->get($this->connectUrl($plan))
            ->assertOk()
            ->assertSee('data-verified="1"', false)
            ->assertSee('value="example.com"', false);

        // A different plan period is a different choice: not pre-ticked.
        $this->withSession($this->cart($plan))
            ->get($this->connectUrl($plan, 'yearly'))
            ->assertOk()
            ->assertSee('data-verified="0"', false);
    }

    public function test_the_domain_check_can_be_switched_off_without_a_deploy()
    {
        config(['services.resellerclub.hosting_domain_check' => false]);
        $plan = $this->plan();

        $this->storeDomain($plan, 'anything-at-all.com')->assertRedirect(route('hosting.checkout'));

        $this->assertSame([], $this->rc->calls);
    }

    public function test_check_endpoint_is_throttled()
    {
        for ($i = 0; $i < 20; $i++) {
            $this->postJson(route('hosting.connect.check'), ['domain' => 'example.com'])->assertOk();
        }

        $this->postJson(route('hosting.connect.check'), ['domain' => 'example.com'])->assertStatus(429);
    }

    // ----------------------------------------------- Plan A test price (৳1)

    public function test_local_shared_plan_a_costs_one_taka_while_the_test_price_is_on()
    {
        config(['services.hosting_plan_a_test_price' => 1]);
        $plan = $this->plan('local', 'shared', 'Plan A');
        $orders = app(HostingOrderService::class);

        foreach (['monthly', 'yearly', 'twoYear', 'threeYear'] as $period) {
            $quote = $orders->quote('local', 'shared', (string) $plan->id, $period);
            if ($quote === null) {
                continue; // a period this plan does not sell stays unsold
            }
            $this->assertEquals(1, $quote['total'], $period);
        }

        $this->assertNotNull($orders->quote('local', 'shared', (string) $plan->id, 'monthly'));
        // The real price in the database is untouched.
        $this->assertEquals(300, $plan->fresh()->price_monthly);
    }

    public function test_plan_a_test_price_shows_on_the_public_page_the_connect_page_and_checkout()
    {
        config(['services.hosting_plan_a_test_price' => 1]);
        $plan = $this->plan();
        $user = $this->user();

        $this->get(route('hosting.region', ['region' => 'local', 'type' => 'shared']))
            ->assertOk()
            ->assertSee('data-monthly="1"', false);

        $this->get($this->connectUrl($plan))
            ->assertOk()
            ->assertSee('৳1</strong>', false);

        $this->withSession($this->customerSession($user, $this->cart($plan)))
            ->get(route('hosting.checkout'))
            ->assertOk()
            ->assertDontSee('৳300');
    }

    public function test_other_plans_and_other_sections_keep_their_real_price_under_the_test_price()
    {
        config(['services.hosting_plan_a_test_price' => 1]);
        $planB = $this->plan('local', 'shared', 'Plan B');
        $vpsA = $this->plan('local', 'vps', 'Plan A');
        $orders = app(HostingOrderService::class);

        $this->assertEquals($planB->price_monthly, $orders->quote('local', 'shared', (string) $planB->id, 'monthly')['total']);
        $this->assertEquals($vpsA->price_monthly, $orders->quote('local', 'vps', (string) $vpsA->id, 'monthly')['total']);
    }

    public function test_setting_the_test_price_to_zero_brings_the_real_price_back()
    {
        config(['services.hosting_plan_a_test_price' => 0]);
        $plan = $this->plan();

        $this->assertEquals(
            $plan->price_monthly,
            app(HostingOrderService::class)->quote('local', 'shared', (string) $plan->id, 'monthly')['total']
        );
    }
}
