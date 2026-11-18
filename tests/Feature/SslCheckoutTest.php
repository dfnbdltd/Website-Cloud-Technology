<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\SslOrder;
use App\Services\ExchangeRateService;
use App\Services\ResellerClubClient;
use App\Services\SslOrderService;
use App\Services\SslPlanService;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * SSL purchase flow: Buy Now → connect domain → checkout → saved order.
 *
 * Prices come from the stubbed ResellerClub list below (USD per month) at a
 * fixed ৳120 rate, so Positive SSL is round(0.79 × 12 × 120) = ৳1,138 a year.
 */
class SslCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.resellerclub.ssl_live_pricing' => true, 'services.ssl_checkout_enabled' => true]);
        cache()->forget('resellerclub.pricing.v2');
        cache()->forget('resellerclub.reseller_cost_pricing.v1');
        SslPlanService::resetSchemaCache();

        $this->app->instance(ExchangeRateService::class, new class extends ExchangeRateService {
            public function usdToBdt()
            {
                return ['rate' => 120.0, 'source' => 'fallback', 'updated_at' => null];
            }
        });

        $this->app->instance(ResellerClubClient::class, new class extends ResellerClubClient {
            public function isConfigured()
            {
                return true;
            }

            public function pricing()
            {
                return ['sslcert' => ['plans' => [
                    '299' => ['add' => ['12' => 4], 'renew' => ['12' => 4]],
                    '300' => ['add' => ['12' => 0.79], 'renew' => ['12' => 1.99]],
                    '301' => ['add' => ['12' => 5.99], 'renew' => ['12' => 5.99]],
                    '672' => ['add' => ['12' => 13, '24' => 10], 'renew' => ['12' => 17, '24' => 17]],
                ]]];
            }

            public function resellerPricing()
            {
                return ['sslcert' => ['plans' => [
                    '300' => ['add' => ['12' => 0.5], 'renew' => ['12' => 1.5]],
                ]]];
            }
        });
    }

    private function user($name = 'Buyer One', $verified = true, $uid = null)
    {
        $user = new User([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '-' . uniqid() . '@example.test',
            'password' => Hash::make('SecretP@ss1'),
        ]);
        $user->forceFill([
            'email_verified_at' => $verified ? now() : null,
            'customer_uid' => $uid,
        ])->save();

        return $user;
    }

    private function customerSession(User $user)
    {
        return ['customer' => [
            'id' => $user->id,
            'uid' => $user->customer_uid,
            'name' => $user->name,
            'initials' => 'BO',
            'email' => $user->email,
            'phone' => '',
            'balance' => 0,
            'logo' => null,
            'plan' => 'BDIX Premium',
        ]];
    }

    private function cart($plan = 'positive-ssl', $years = 1, $domain = 'example.com')
    {
        return ['ssl_cart' => ['plan' => $plan, 'years' => $years, 'domain' => $domain]];
    }

    // ------------------------------------------------------------ plan cards

    public function test_buy_now_links_into_the_connect_page_for_that_plan()
    {
        $this->get(route('services.coming-soon', 'ssl-certificate'))
            ->assertOk()
            ->assertSee('ssl/connect?plan=positive-ssl', false)
            // The card button is a plain link now; it must not open the "coming soon" dialog.
            // (The shared coming-soon modal script is on every page, so only the card is checked.)
            ->assertSee('<a class="ssl-buy" href=', false)
            ->assertDontSee('<button class="ssl-buy', false);
    }

    // --------------------------------------------------------- connect page

    public function test_connect_page_is_public_and_shows_the_taka_price()
    {
        $this->get(route('ssl.connect', ['plan' => 'positive-ssl', 'term' => 1]))
            ->assertOk()
            ->assertSee('Which website is this certificate for?')
            ->assertSee('Positive SSL')
            ->assertSee('৳1,138')
            ->assertSee('Continue to Cart')
            ->assertSee('disabled', false);
    }

    public function test_connect_page_for_an_unknown_plan_or_term_goes_back_to_the_plans()
    {
        $this->get(route('ssl.connect', ['plan' => 'nope', 'term' => 1]))
            ->assertRedirect(route('services.coming-soon', 'ssl-certificate'))
            ->assertSessionHasErrors('ssl');

        // Positive SSL has no 2-year price from ResellerClub.
        $this->get(route('ssl.connect', ['plan' => 'positive-ssl', 'term' => 2]))
            ->assertRedirect(route('services.coming-soon', 'ssl-certificate'));
    }

    public function test_a_valid_domain_is_saved_and_continues_to_checkout()
    {
        $this->post(route('ssl.connect.store'), ['plan' => 'positive-ssl', 'term' => 1, 'domain' => 'https://Example.COM/'])
            ->assertRedirect(route('ssl.checkout'))
            ->assertSessionHas('ssl_cart', ['plan' => 'positive-ssl', 'years' => 1, 'domain' => 'example.com']);
    }

    public function test_an_invalid_domain_is_rejected()
    {
        foreach (['', 'localhost', 'not a domain', 'exa_mple.com', '-bad.com', 'example'] as $bad) {
            $this->from(route('ssl.connect', ['plan' => 'positive-ssl']))
                ->post(route('ssl.connect.store'), ['plan' => 'positive-ssl', 'term' => 1, 'domain' => $bad])
                ->assertSessionHasErrors('domain')
                ->assertSessionMissing('ssl_cart');
        }
    }

    public function test_wildcard_plans_issue_for_the_star_name_and_other_plans_refuse_it()
    {
        $this->post(route('ssl.connect.store'), ['plan' => 'positive-ssl-wildcard', 'term' => 1, 'domain' => 'example.com'])
            ->assertSessionHas('ssl_cart.domain', '*.example.com');

        $this->post(route('ssl.connect.store'), ['plan' => 'positive-ssl-wildcard', 'term' => 1, 'domain' => '*.example.com'])
            ->assertSessionHas('ssl_cart.domain', '*.example.com');

        $this->post(route('ssl.connect.store'), ['plan' => 'positive-ssl', 'term' => 1, 'domain' => '*.example.com'])
            ->assertSessionHasErrors('domain');
    }

    public function test_domain_normalisation()
    {
        $this->assertSame('example.com', SslOrderService::normalizeDomain(' Example.com. '));
        $this->assertSame('www.example.com', SslOrderService::normalizeDomain('http://www.example.com/path?x=1'));
        $this->assertSame('shop.example.co.uk', SslOrderService::normalizeDomain('shop.example.co.uk'));
        $this->assertNull(SslOrderService::normalizeDomain('example'));
        $this->assertNull(SslOrderService::normalizeDomain('192.168.0.1'));
        $this->assertNull(SslOrderService::normalizeDomain(['example.com']));
        $this->assertSame('*.example.com', SslOrderService::normalizeDomain('example.com', true));
    }

    // ------------------------------------------------------------- checkout

    public function test_checkout_without_a_selection_goes_back_to_the_plans()
    {
        $this->get(route('ssl.checkout'))
            ->assertRedirect(route('services.coming-soon', 'ssl-certificate'));
    }

    public function test_a_guest_sees_the_taka_price_and_login_links_that_return_to_checkout()
    {
        $this->withSession($this->cart())
            ->get(route('ssl.checkout'))
            ->assertOk()
            ->assertSee('Positive SSL')
            ->assertSee('example.com')
            ->assertSee('৳1,138')
            ->assertSee('Log in to continue')
            ->assertSee('return_to=%2Fssl%2Fcheckout', false)
            ->assertDontSee('Who are you buying for?');
    }

    public function test_a_signed_in_customer_chooses_who_it_is_for_and_bkash_or_nagad()
    {
        $user = $this->user();

        $this->withSession($this->customerSession($user) + $this->cart())
            ->get(route('ssl.checkout'))
            ->assertOk()
            ->assertSee('Who are you buying for?')
            ->assertSee($user->email)
            ->assertSee('Email or ID of existing customer')
            ->assertSee('bKash')
            ->assertSee('Nagad')
            ->assertDontSee('Bank transfer')
            ->assertSee('Place order');
    }

    public function test_a_guest_login_without_return_to_still_returns_to_the_ssl_checkout()
    {
        $this->withSession($this->cart())
            ->get(route('login'))
            ->assertSessionHas('url.intended', '/ssl/checkout');
    }

    // ------------------------------------------------------- customer lookup

    public function test_customer_lookup_finds_verified_customers_by_email_or_id_only()
    {
        $buyer = $this->user();
        $customer = $this->user('Real Customer', true, 'CT-000042');
        $unverified = $this->user('Pending Person', false);
        $session = $this->customerSession($buyer);

        $this->withSession($session)->postJson(route('ssl.customer.lookup'), ['identifier' => $customer->email])
            ->assertOk()->assertJson(['verified' => true]);

        $this->withSession($session)->postJson(route('ssl.customer.lookup'), ['identifier' => 'CT-000042'])
            ->assertOk()->assertJson(['verified' => true]);

        $this->withSession($session)->postJson(route('ssl.customer.lookup'), ['identifier' => 'nobody@example.test'])
            ->assertStatus(404)->assertJsonFragment(['message' => 'Customer does not exist. Check the email or customer ID and try again.']);

        $this->withSession($session)->postJson(route('ssl.customer.lookup'), ['identifier' => $unverified->email])
            ->assertStatus(404);
    }

    public function test_customer_lookup_needs_a_signed_in_customer()
    {
        $this->postJson(route('ssl.customer.lookup'), ['identifier' => 'a@b.test'])->assertStatus(401);
    }

    // ----------------------------------------------------------- placing it

    public function test_placing_an_order_for_myself_saves_the_order_and_an_unpaid_invoice()
    {
        $user = $this->user();

        $response = $this->withSession($this->customerSession($user) + $this->cart())
            ->post(route('ssl.place-order'), ['purchase_for' => 'myself', 'payment_method' => 'nagad']);

        $order = SslOrder::first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('ssl.order', $order->reference))
            ->assertSessionMissing('ssl_cart');

        $this->assertSame($user->id, (int) $order->user_id);
        $this->assertSame($user->id, (int) $order->placed_by_user_id);
        $this->assertSame('Positive SSL', $order->plan_name);
        $this->assertSame(300, (int) $order->resellerclub_plan_id);
        $this->assertSame('example.com', $order->domain);
        $this->assertSame(1138.0, $order->total);
        // Cost 0.50 USD/month × 12 × ৳120 — frozen with the rate that was used.
        $this->assertSame(720.0, $order->purchase_price);
        $this->assertSame(120.0, $order->exchange_rate);
        $this->assertSame('payment_pending', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('nagad', $order->payment_method);
        $this->assertStringStartsWith('SSL-', $order->reference);

        $invoice = Invoice::where('source_type', SslOrder::class)->where('source_id', $order->id)->first();
        $this->assertNotNull($invoice);
        $this->assertSame('unpaid', $invoice->status);
        $this->assertSame($user->id, (int) $invoice->user_id);
        $this->assertSame(1138.0, $invoice->total);
        $item = $invoice->items()->first();
        $this->assertSame('ssl-certificate', $item->item_type);
        $this->assertSame(1138.0, $item->line_total);
        $this->assertSame(720.0, $item->purchase_price);
    }

    public function test_the_price_is_taken_from_the_server_not_from_the_request()
    {
        $user = $this->user();

        $this->withSession($this->customerSession($user) + $this->cart())
            ->post(route('ssl.place-order'), [
                'purchase_for' => 'myself',
                'payment_method' => 'bkash',
                'total' => 1,
                'price' => 1,
            ]);

        $this->assertSame(1138.0, SslOrder::first()->total);
    }

    public function test_placing_an_order_for_an_existing_customer_assigns_it_to_them()
    {
        $buyer = $this->user();
        $customer = $this->user('Real Customer', true, 'CT-000042');

        $this->withSession($this->customerSession($buyer) + $this->cart())
            ->post(route('ssl.place-order'), [
                'purchase_for' => 'customer',
                'customer_identifier' => 'CT-000042',
                'payment_method' => 'bkash',
            ]);

        $order = SslOrder::first();
        $this->assertSame($customer->id, (int) $order->user_id);
        $this->assertSame($buyer->id, (int) $order->placed_by_user_id);
        $this->assertSame($customer->email, $order->customer_email);
        $this->assertSame($customer->id, (int) Invoice::first()->user_id);
    }

    public function test_a_customer_that_does_not_exist_blocks_the_order()
    {
        $buyer = $this->user();

        $this->withSession($this->customerSession($buyer) + $this->cart())
            ->post(route('ssl.place-order'), [
                'purchase_for' => 'customer',
                'customer_identifier' => 'ghost@example.test',
                'payment_method' => 'bkash',
            ])
            ->assertSessionHasErrors('customer_identifier');

        $this->assertSame(0, SslOrder::count());
        $this->assertSame(0, Invoice::count());
    }

    public function test_only_bkash_and_nagad_are_accepted()
    {
        $user = $this->user();

        foreach (['bank', 'wallet', '', 'paypal'] as $method) {
            $this->withSession($this->customerSession($user) + $this->cart())
                ->post(route('ssl.place-order'), ['purchase_for' => 'myself', 'payment_method' => $method])
                ->assertSessionHasErrors('payment_method');
        }

        $this->assertSame(0, SslOrder::count());
    }

    public function test_a_guest_cannot_place_an_order()
    {
        $this->withSession($this->cart())
            ->post(route('ssl.place-order'), ['purchase_for' => 'myself', 'payment_method' => 'bkash'])
            ->assertRedirect();

        $this->assertSame(0, SslOrder::count());
    }

    public function test_placing_an_order_without_a_selection_creates_nothing()
    {
        $user = $this->user();

        $this->withSession($this->customerSession($user))
            ->post(route('ssl.place-order'), ['purchase_for' => 'myself', 'payment_method' => 'bkash'])
            ->assertRedirect(route('services.coming-soon', 'ssl-certificate'));

        $this->assertSame(0, SslOrder::count());
    }

    // ---------------------------------------------------------- order page

    public function test_the_order_page_shows_the_saved_order_to_its_owner_and_buyer_only()
    {
        $buyer = $this->user();
        $customer = $this->user('Real Customer', true, 'CT-000042');
        $stranger = $this->user('Stranger');

        $this->withSession($this->customerSession($buyer) + $this->cart())
            ->post(route('ssl.place-order'), [
                'purchase_for' => 'customer',
                'customer_identifier' => $customer->email,
                'payment_method' => 'bkash',
            ]);
        $order = SslOrder::first();

        foreach ([$buyer, $customer] as $allowed) {
            $this->withSession($this->customerSession($allowed))
                ->get(route('ssl.order', $order->reference))
                ->assertOk()
                ->assertSee($order->reference)
                ->assertSee('Positive SSL')
                ->assertSee('example.com')
                ->assertSee('৳1,138')
                ->assertSee('Waiting for payment');
        }

        $this->withSession($this->customerSession($stranger))
            ->get(route('ssl.order', $order->reference))
            ->assertNotFound();

        // A guest is sent to sign in first (drop the stranger's session from the request above).
        $this->flushSession();
        $this->get(route('ssl.order', $order->reference))->assertRedirect();
    }

    public function test_the_probe_login_routes_no_longer_exist()
    {
        $this->get('/__probe-login')->assertNotFound();
        $this->get('/__probe-rowcheck')->assertNotFound();
    }

    public function test_while_ssl_ordering_is_closed_place_order_creates_nothing()
    {
        config(['services.ssl_checkout_enabled' => false]);
        $buyer = $this->user('Buyer One');

        $this->withSession(array_merge($this->customerSession($buyer), $this->cart()))
            ->from(route('ssl.checkout'))
            ->post(route('ssl.place-order'), ['purchase_for' => 'myself', 'payment_method' => 'bkash'])
            ->assertRedirect(route('ssl.checkout'))
            ->assertSessionHasErrors('payment');

        $this->assertSame(0, \App\Models\SslOrder::count());
    }
}
