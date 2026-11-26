<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\HostingOrder;
use App\Models\HostingPlan;
use App\Models\Invoice;
use App\Services\HostingOrderService;
use App\Services\ResellerClubClient;
use App\Support\StoreInventory;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Hosting flow phases 0-5: plan label + snapshot, connect-page modes, billing
 * dropdown, invoice term, sidebar count, admin badge / confirmed-by / admins table.
 */
class HostingFlowTest extends TestCase
{
    use RefreshDatabase;

    private $rc;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.hosting_plan_a_test_price' => 0,
            'services.resellerclub.hosting_domain_check' => true,
            'services.admin.email' => 'env-admin@example.test',
            'services.admin.password' => 'EnvSecret1',
        ]);
        cache()->flush();

        $this->rc = new class extends ResellerClubClient {
            public $answers = ['example.com' => 'regthroughothers', 'free-name.com' => 'available'];
            public $transferable = ['move-me.com' => true, 'locked.com' => false];

            public function checkAvailability($domain, $tld)
            {
                $key = $domain . '.' . $tld;

                return isset($this->answers[$key]) ? [$key => ['status' => $this->answers[$key], 'classkey' => 'domcno']] : null;
            }

            public function catalog(array $classkeys = [])
            {
                return ['com' => ['price' => 1200.0]];
            }

            public function validateDomainTransferIn($domain)
            {
                return $this->transferable[$domain] ?? null;
            }

            public function liveTransferPrice($tld)
            {
                return 1100.0;
            }
        };
        $this->app->instance(ResellerClubClient::class, $this->rc);
    }

    private function plan()
    {
        return HostingPlan::where('region', 'local')->where('type', 'shared')->where('name', 'Plan A')->firstOrFail();
    }

    private function user($name = 'Buyer One')
    {
        $user = new User(['name' => $name, 'email' => uniqid('b') . '@example.test', 'password' => Hash::make('SecretP@ss1'), 'phone' => '01712345678']);
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function custSession(User $user, array $extra = [])
    {
        return array_merge(['customer' => ['id' => $user->id, 'uid' => null, 'name' => $user->name, 'initials' => 'BO', 'email' => $user->email, 'phone' => '01712345678', 'balance' => 0, 'logo' => null]], $extra);
    }

    private function cart($domain = 'example.com', $period = 'monthly')
    {
        $plan = $this->plan();

        return ['hosting_cart' => ['region' => 'local', 'type' => 'shared', 'plan' => (string) $plan->id, 'period' => $period, 'domain' => $domain, 'domain_checked' => true]];
    }

    private function paidOrder(User $user, array $extra = [])
    {
        $order = app(HostingOrderService::class)->place(
            app(HostingOrderService::class)->quote('local', 'shared', $this->plan()->id, 'monthly'),
            'example.com', $user, $user, 'bkash'
        );
        $order->forceFill(array_merge(['payment_status' => 'paid', 'status' => HostingOrder::STATUS_PAID], $extra))->save();

        return $order->fresh();
    }

    // ------------------------------------------------------------ phase 0

    public function test_plan_label_is_built_from_the_plans_storage_feature_and_saved_at_purchase()
    {
        $quote = app(HostingOrderService::class)->quote('local', 'shared', $this->plan()->id, 'monthly');
        $this->assertMatchesRegularExpression('/^Plan A \(\d+(\.\d+)? (GB|TB|MB) \| (NVMe|SSD) Storage\)$/', $quote['plan_label']);

        $order = $this->paidOrder($this->user());
        $this->assertSame($quote['plan_specs'], $order->plan_specs);
        $this->assertSame($quote['plan_label'], $order->planLabel());

        // An old order with no snapshot shows the plan name only.
        $order->forceFill(['plan_specs' => null])->save();
        $this->assertSame('Plan A', $order->fresh()->planLabel());
    }

    public function test_label_falls_back_to_the_plan_name_when_no_storage_feature_exists()
    {
        $card = ['name' => 'Plan Z', 'feature_sets' => ['monthly' => ['primary' => [['lead' => '3', 'name' => 'websites', 'available' => true]]]]];
        $this->assertSame('Plan Z', HostingOrderService::planLabel($card, 'monthly'));
    }

    // ------------------------------------------------------------ phase 1

    public function test_connect_page_has_the_mode_dropdown_defaulting_to_own_domain_and_plan_details()
    {
        $plan = $this->plan();
        $html = $this->get(route('hosting.connect', ['region' => 'local', 'type' => 'shared', 'plan' => $plan->id, 'period' => 'monthly']))
            ->assertOk()->getContent();

        $this->assertStringContainsString('id="hostMode"', $html);
        $this->assertStringContainsString('<option value="own" selected>', $html);
        $this->assertStringContainsString('Register a new domain', $html);
        $this->assertStringContainsString('Transfer a domain', $html);
        $this->assertStringNotContainsString('Pay with bKash or Nagad', $html);
        $this->assertStringContainsString('host-highlights', $html);
    }

    public function test_register_mode_needs_a_free_name_and_shows_the_price()
    {
        $ok = $this->postJson(route('hosting.connect.check'), ['domain' => 'free-name.com', 'mode' => 'register'])->assertOk()->json();
        $this->assertTrue($ok['ok']);
        $this->assertEquals(1200, $ok['price']);

        $taken = $this->postJson(route('hosting.connect.check'), ['domain' => 'example.com', 'mode' => 'register'])->json();
        $this->assertFalse($taken['ok']);
    }

    public function test_transfer_mode_validates_eligibility()
    {
        $this->assertTrue($this->postJson(route('hosting.connect.check'), ['domain' => 'move-me.com', 'mode' => 'transfer'])->json('ok'));
        $this->assertFalse($this->postJson(route('hosting.connect.check'), ['domain' => 'locked.com', 'mode' => 'transfer'])->json('ok'));
    }

    public function test_default_mode_is_unchanged_and_an_unknown_mode_is_rejected()
    {
        $this->assertTrue($this->postJson(route('hosting.connect.check'), ['domain' => 'example.com'])->json('ok'));
        $this->postJson(route('hosting.connect.check'), ['domain' => 'example.com', 'mode' => 'hack'])->assertStatus(422);
    }

    public function test_register_mode_in_store_domain_is_checked_again_on_the_server()
    {
        $plan = $this->plan();
        $this->post(route('hosting.connect.store'), [
            'region' => 'local', 'type' => 'shared', 'plan' => (string) $plan->id, 'period' => 'monthly',
            'domain' => 'example.com', 'mode' => 'register', // taken: refused even if the browser showed a tick
        ])->assertSessionHasErrors('domain');
        $this->assertNull(session('hosting_cart.pending_domain'));
    }

    public function test_checkout_resumes_after_the_domain_is_registered_and_refuses_before()
    {
        $plan = $this->plan();
        $cart = $this->cart('free-name.com');
        $cart['hosting_cart']['domain_checked'] = false;
        $cart['hosting_cart']['pending_domain'] = ['mode' => 'register', 'domain' => 'free-name.com'];
        $user = $this->user();

        // Still unregistered (never paid): back to connect.
        $this->withSession($this->custSession($user, $cart))->get(route('hosting.checkout'))->assertRedirect();
        $this->assertFalse(session('hosting_cart.domain_checked'));

        // Registered now: checkout opens and the pending flag is gone.
        $this->rc->answers['free-name.com'] = 'regthroughus';
        $this->withSession($this->custSession($user, $cart))->get(route('hosting.checkout'))->assertOk();
        $this->assertTrue(session('hosting_cart.domain_checked'));
        $this->assertNull(session('hosting_cart.pending_domain'));
    }

    // ------------------------------------------------------------ phase 2

    public function test_checkout_has_the_billing_dropdown_label_and_embedded_terms()
    {
        $user = $this->user();
        $html = $this->withSession($this->custSession($user, $this->cart()))->get(route('hosting.checkout'))->assertOk()->getContent();

        $this->assertStringContainsString('id="hostPeriod"', $html);
        $this->assertStringContainsString('id="hostTerms"', $html);
        $this->assertStringContainsString('sslx-change', $html);
        $this->assertMatchesRegularExpression('/Plan A \(\d+(\.\d+)? (GB|TB|MB) \| (NVMe|SSD) Storage\)/', $html);
    }

    public function test_period_endpoint_keeps_the_cart_in_sync_and_never_trusts_the_browser()
    {
        $user = $this->user();
        $response = $this->withSession($this->custSession($user, $this->cart()))
            ->postJson(route('hosting.checkout.period'), ['period' => 'yearly', 'price' => 1]);

        if ($response->status() === 422) {
            $this->markTestSkipped('The seeded plan sells no yearly term.');
        }
        $response->assertOk();
        $this->assertSame('yearly', session('hosting_cart.period'));
        $this->assertNotEquals('1', $response->json('total_text'));

        $this->withSession($this->custSession($user, $this->cart()))
            ->postJson(route('hosting.checkout.period'), ['period' => 'bogus'])->assertStatus(422);
    }

    // ------------------------------------------------------------ phase 3

    public function test_invoice_term_label_and_plan_label_line()
    {
        $order = $this->paidOrder($this->user());
        $invoice = Invoice::where('source_type', HostingOrder::class)->where('source_id', $order->id)->firstOrFail();
        $item = $invoice->items()->first();

        $this->assertSame('Monthly', $item->period_label);
        $this->assertSame('Term', $invoice->fresh('items')->termHeading());
        $this->assertSame($order->planLabel(), $invoice->itemSubtitle($item));
    }

    // ------------------------------------------------------------ phase 4

    public function test_sidebar_count_counts_only_paid_not_cancelled_orders_of_that_customer()
    {
        $user = $this->user();
        $other = $this->user('Other Person');
        $this->assertSame(0, StoreInventory::hostingCountForUser($user->id));

        $this->paidOrder($user);
        $cancelled = $this->paidOrder($user);
        $cancelled->forceFill(['status' => HostingOrder::STATUS_CANCELLED])->save();
        $this->paidOrder($other);

        $this->assertSame(1, StoreInventory::hostingCountForUser($user->id));
        $this->assertSame(0, StoreInventory::hostingCountForUser(null));
    }

    // ------------------------------------------------------------ phase 5

    private function admin(array $extra = [])
    {
        return $this->withSession(['admin' => array_merge(['id' => null, 'email' => 'admin@example.test'], $extra)]);
    }

    public function test_admin_badge_counts_unseen_paid_orders_and_opening_the_list_clears_it()
    {
        $this->paidOrder($this->user());
        $this->paidOrder($this->user('Second Buyer'));
        $this->assertSame(2, HostingOrder::adminUnseenCount());

        $html = $this->admin()->get(route('admin.hosting.orders'))->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, 'class="ho-new">NEW'));
        $this->assertSame(0, HostingOrder::adminUnseenCount());

        $second = $this->admin()->get(route('admin.hosting.orders'))->getContent();
        $this->assertSame(0, substr_count($second, 'class="ho-new">NEW'));
    }

    public function test_the_badge_shows_on_other_admin_pages_until_the_list_is_opened()
    {
        $this->paidOrder($this->user());
        $this->admin()->get(route('admin.dashboard'))->assertSee('class="admin-badge"', false);
    }

    public function test_mark_as_hosted_records_who_confirmed_it_and_only_admin_screens_show_it()
    {
        $order = $this->paidOrder($this->user());
        $admin = Admin::create(['name' => 'Ops', 'email' => 'ops@example.test', 'password' => Hash::make('x'), 'is_active' => 1]);

        $this->admin(['id' => $admin->id, 'email' => $admin->email])
            ->post(route('admin.hosting.orders.hosted', $order->id))->assertRedirect();

        $order = $order->fresh();
        $this->assertSame($admin->id, (int) $order->hosted_by_admin_id);
        $this->assertSame('ops@example.test', $order->hosted_by_admin_email);

        $this->admin()->get(route('admin.hosting.orders'))->assertSee('ops@example.test');
        $this->withSession($this->custSession($order->owner))->get(route('hosting.order', $order->reference))->assertDontSee('ops@example.test');
    }

    public function test_admin_logs_in_from_the_admins_table_and_the_env_admin_still_works()
    {
        Admin::create(['name' => 'Ops', 'email' => 'ops@example.test', 'password' => Hash::make('TablePass1'), 'is_active' => 1]);
        Admin::create(['name' => 'Off', 'email' => 'off@example.test', 'password' => Hash::make('TablePass1'), 'is_active' => 0]);

        $this->post(route('admin.login.submit'), ['email' => 'ops@example.test', 'password' => 'TablePass1'])->assertRedirect(route('admin.dashboard'));
        $this->assertSame('ops@example.test', session('admin.email'));
        $this->assertNotNull(session('admin.id'));

        $this->flushSession();
        $this->post(route('admin.login.submit'), ['email' => 'off@example.test', 'password' => 'TablePass1'])->assertSessionHasErrors('email');

        $this->flushSession();
        $this->post(route('admin.login.submit'), ['email' => 'env-admin@example.test', 'password' => 'EnvSecret1'])->assertRedirect(route('admin.dashboard'));
        $this->assertNull(session('admin.id'));
        $this->assertSame('env-admin@example.test', session('admin.email'));
    }
}
