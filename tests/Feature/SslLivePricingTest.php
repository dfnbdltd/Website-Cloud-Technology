<?php

namespace Tests\Feature;

use App\Models\SslPlan;
use App\Services\ExchangeRateService;
use App\Services\ResellerClubClient;
use App\Services\SslPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Live SSL pricing, customer side.
 *
 * The acceptance numbers come from the implementation brief (section 8): with
 * the live account's prices and a 120 BDT/USD rate, the four cards must read
 * ৳95 / ৳480 / ৳719 / ৳1,560 per month. The rate is stubbed so these assertions
 * do not depend on the live exchange-rate API.
 *
 * The other half of these tests matters as much: whatever goes wrong with
 * ResellerClub, the page must fall back to the typed BDT prices instead of
 * erroring, and the customer-facing array must never carry cost or profit.
 */
class SslLivePricingTest extends TestCase
{
    use RefreshDatabase;

    /** The outcome of the stub, set per test. */
    private $selling = [];

    private $cost = [];

    private $configured = true;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.resellerclub.ssl_live_pricing' => true]);

        // Fixed rate: the assertions below are the brief's 120 BDT figure.
        $this->app->instance(ExchangeRateService::class, $this->rateStub(120.0));

        $this->selling = $this->liveSellingPrices();
        $this->cost = $this->liveCostPrices();
        $this->app->instance(ResellerClubClient::class, $this->clientStub());
    }

    // ── fixtures ──────────────────────────────────────────────────────────

    private function liveSellingPrices()
    {
        return [
            '299' => ['renew' => ['12' => 4], 'add' => ['12' => 4]],
            '300' => ['renew' => ['12' => 1.99], 'add' => ['12' => 0.79]],
            '301' => ['renew' => ['12' => 5.99], 'add' => ['12' => 5.99]],
            '672' => ['renew' => ['12' => 17, '24' => 17], 'add' => ['12' => 13, '24' => 10]],
        ];
    }

    private function liveCostPrices()
    {
        return [
            '299' => ['renew' => ['12' => 3.33, '24' => 3.33], 'add' => ['12' => 3.33, '24' => 3.33]],
            '300' => ['renew' => ['12' => 1.66, '24' => 1.66], 'add' => ['12' => 0.66, '24' => 1.16]],
            '301' => ['renew' => ['12' => 4.99, '24' => 4.99], 'add' => ['12' => 4.99, '24' => 4.99]],
            '672' => ['renew' => ['12' => 14.16, '24' => 14.16], 'add' => ['12' => 10.83, '24' => 8.33]],
        ];
    }

    /** Counts calls so the customer path can be proven to skip the cost endpoint. */
    private function clientStub()
    {
        return new class($this) extends ResellerClubClient {
            private $owner;

            public $sellingCalls = 0;

            public $costCalls = 0;

            public function __construct($owner)
            {
                $this->owner = $owner;
            }

            public function isConfigured()
            {
                return $this->owner->configuredFlag();
            }

            public function pricing()
            {
                $this->sellingCalls++;

                return ['sslcert' => ['plans' => $this->owner->sellingPrices()]];
            }

            public function resellerPricing()
            {
                $this->costCalls++;

                return ['sslcert' => ['plans' => $this->owner->costPrices()]];
            }
        };
    }

    private function rateStub($rate)
    {
        return new class($rate) extends ExchangeRateService {
            private $rate;

            public function __construct($rate)
            {
                $this->rate = $rate;
            }

            public function usdToBdt()
            {
                return ['rate' => $this->rate, 'source' => 'fallback', 'updated_at' => null];
            }
        };
    }

    // Accessors used by the anonymous class above (it cannot read $this). ──
    public function configuredFlag()
    {
        return $this->configured;
    }

    public function sellingPrices()
    {
        return $this->selling;
    }

    public function costPrices()
    {
        return $this->cost;
    }

    // ── helpers ───────────────────────────────────────────────────────────

    private function resolved()
    {
        return collect(app(SslPlanService::class)->plans())->keyBy('key');
    }

    // ── the acceptance table (brief section 8) ────────────────────────────

    /** @test */
    public function the_four_cards_show_the_live_bdt_prices_from_the_brief()
    {
        $plans = $this->resolved();

        // positive-ssl (300): $0.79/mo -> ৳95/mo, ৳1,138/yr, renews $1.99 -> ৳239
        $this->assertSame(95.0, (float) $plans['positive-ssl']['default']['per_month']);
        $this->assertSame(1138.0, (float) $plans['positive-ssl']['default']['total']);
        $this->assertSame(239.0, (float) $plans['positive-ssl']['renewal']);

        // comodo-ssl (299): $4.00/mo -> ৳480/mo, ৳5,760/yr
        $this->assertSame(480.0, (float) $plans['comodo-ssl']['default']['per_month']);
        $this->assertSame(5760.0, (float) $plans['comodo-ssl']['default']['total']);
        $this->assertSame(480.0, (float) $plans['comodo-ssl']['renewal']);

        // positive-ssl-wildcard (301): $5.99/mo -> ৳719/mo, ৳8,626/yr
        $this->assertSame(719.0, (float) $plans['positive-ssl-wildcard']['default']['per_month']);
        $this->assertSame(8626.0, (float) $plans['positive-ssl-wildcard']['default']['total']);
        $this->assertSame(719.0, (float) $plans['positive-ssl-wildcard']['renewal']);

        // ev-ssl (672): $13/mo -> ৳1,560/mo, ৳18,720/yr, renews $17 -> ৳2,040
        $this->assertSame(1560.0, (float) $plans['ev-ssl']['default']['per_month']);
        $this->assertSame(18720.0, (float) $plans['ev-ssl']['default']['total']);
        $this->assertSame(2040.0, (float) $plans['ev-ssl']['renewal']);
    }

    /** @test */
    public function only_ev_offers_a_two_year_term_and_its_savings_are_from_api_prices()
    {
        $plans = $this->resolved();

        // 299/300/301 sell no 24-month tenure, so the card offers 1 year only.
        foreach (['positive-ssl', 'comodo-ssl', 'positive-ssl-wildcard'] as $key) {
            $this->assertSame([1], array_keys($plans[$key]['terms']), $key . ' should offer 1 year only');
        }

        // EV does: $10/mo for 24 months -> ৳28,800, vs ৳37,440 at the 1-year
        // rate, so it saves ৳8,640.
        $this->assertSame([1, 2], array_keys($plans['ev-ssl']['terms']));
        $this->assertSame(28800.0, (float) $plans['ev-ssl']['terms'][2]['total']);
        $this->assertSame(8640.0, (float) $plans['ev-ssl']['terms'][2]['savings']);
        $this->assertSame(1200.0, (float) $plans['ev-ssl']['terms'][2]['per_month']);
    }

    /** @test */
    public function the_three_year_term_never_appears_in_live_mode()
    {
        foreach ($this->resolved() as $key => $plan) {
            $this->assertArrayNotHasKey(3, $plan['terms'], $key . ' must not offer a 3-year term');
            $this->assertSame('resellerclub', $plan['source']);
        }
    }

    /** @test */
    public function the_default_term_is_always_the_one_year_entry()
    {
        foreach ($this->resolved() as $key => $plan) {
            $this->assertSame(1, $plan['default']['years'], $key);
            $this->assertSame($plan['terms'][1], $plan['default'], $key);
        }
    }

    // ── data separation ───────────────────────────────────────────────────

    /** @test */
    public function the_customer_array_never_carries_cost_or_profit()
    {
        foreach ($this->resolved() as $key => $plan) {
            foreach (['cost', 'profit', 'profit_amount', 'profit_percent', 'plan_id', 'resellerclub_plan_id'] as $forbidden) {
                $this->assertArrayNotHasKey($forbidden, $plan, $key . ' leaked ' . $forbidden);
            }

            foreach ($plan['terms'] as $term) {
                foreach (['cost', 'profit', 'profit_amount', 'profit_percent'] as $forbidden) {
                    $this->assertArrayNotHasKey($forbidden, $term);
                }
            }
        }
    }

    /** @test */
    public function the_customer_page_renders_bdt_and_hides_the_supplier()
    {
        $response = $this->get('/services/ssl-certificate');

        $response->assertOk()
            // The ৳ glyph is a separate element from the figure, so assert the
            // figure and the total as rendered.
            ->assertSee('data-ssl-amount>95<', false)
            ->assertSee('5,760')
            ->assertSee('18,720')
            ->assertSee('28,800')
            ->assertSee('Renews at ৳239/mo')
            ->assertDontSee('3 Years')
            ->assertDontSee('ResellerClub')
            ->assertDontSee('sslcert')
            ->assertDontSee('profit')
            ->assertDontSee('profit_percent');
    }

    /** @test */
    public function the_client_area_page_shows_the_same_live_prices()
    {
        $this->withSession([
            'customer' => [
                'id' => 1, 'uid' => 'CT-000001', 'name' => 'Live Price Client',
                'initials' => 'LP', 'email' => 'live@example.test', 'balance' => 0,
            ],
        ])->get('/client/coming-soon/ssl-certificate')
            ->assertOk()
            ->assertSee('data-ssl-amount>95<', false)
            ->assertSee('৳239/mo')
            ->assertDontSee('ResellerClub');
    }

    // ── endpoint discipline & performance ─────────────────────────────────

    /** @test */
    public function the_customer_path_fetches_selling_prices_once_and_never_the_cost_list()
    {
        app(SslPlanService::class)->plans();
        app(SslPlanService::class)->plans();

        $client = app(ResellerClubClient::class);

        $this->assertSame(0, $client->costCalls, 'the customer path must not call resellerPricing()');
        $this->assertSame(2, $client->sellingCalls, 'one pricing() call per plans() call');
    }

    // ── fallbacks ─────────────────────────────────────────────────────────

    /** @test */
    public function the_kill_switch_restores_the_typed_prices()
    {
        config(['services.resellerclub.ssl_live_pricing' => false]);

        $plan = $this->resolved()['comodo-ssl'];

        $this->assertSame('local', $plan['source']);
        $this->assertSame(399.0, (float) $plan['default']['per_month']);
        $this->assertSame([1, 2, 3], array_keys($plan['terms']));
    }

    /** @test */
    public function an_unlinked_plan_keeps_its_typed_price_while_others_stay_live()
    {
        SslPlan::where('plan_key', 'comodo-ssl')->first()->update(['resellerclub_plan_id' => null]);

        $plans = $this->resolved();

        $this->assertSame('local', $plans['comodo-ssl']['source']);
        $this->assertSame(399.0, (float) $plans['comodo-ssl']['default']['per_month']);
        $this->assertSame('resellerclub', $plans['ev-ssl']['source']);
    }

    /** @test */
    public function resellerclub_being_unreachable_falls_back_without_erroring()
    {
        $this->selling = [];
        $this->cost = [];

        $response = $this->get('/services/ssl-certificate');

        $response->assertOk();
        $this->assertSame('local', $this->resolved()['ev-ssl']['source']);
        $this->assertSame(1299.0, (float) $this->resolved()['ev-ssl']['default']['per_month']);
    }

    /** @test */
    public function an_unconfigured_integration_never_calls_the_api()
    {
        $this->configured = false;

        $plans = $this->resolved();

        $this->assertSame('local', $plans['ev-ssl']['source']);
        $this->assertSame(0, app(ResellerClubClient::class)->sellingCalls);
    }

    /** @test */
    public function a_plan_linked_to_an_id_the_api_does_not_return_keeps_its_typed_price()
    {
        SslPlan::where('plan_key', 'ev-ssl')->first()->update(['resellerclub_plan_id' => 999]);

        $plans = $this->resolved();

        $this->assertSame('local', $plans['ev-ssl']['source']);
        $this->assertSame(1299.0, (float) $plans['ev-ssl']['default']['per_month']);
    }

    /** @test */
    public function a_plan_with_only_a_twelve_month_selling_price_falls_back_consistently()
    {
        // Selling price exists but is zero/garbage -> not a usable live price.
        $this->selling['672'] = ['renew' => ['12' => 17], 'add' => ['12' => 0]];

        $this->assertSame('local', $this->resolved()['ev-ssl']['source']);
    }

    /** @test */
    public function an_unusable_exchange_rate_falls_back_to_typed_prices()
    {
        $this->app->instance(ExchangeRateService::class, $this->rateStub(0));

        $plans = $this->resolved();

        // A zero rate must never produce a ৳0 certificate.
        $this->assertSame('local', $plans['comodo-ssl']['source']);
        $this->assertSame(399.0, (float) $plans['comodo-ssl']['default']['per_month']);
    }

    // ── the typed path is unchanged ───────────────────────────────────────

    /** @test */
    public function resolve_without_live_data_is_identical_to_the_pre_live_behaviour()
    {
        $plan = SslPlan::where('plan_key', 'positive-ssl')->first();
        $plan->update([
            'monthly_price' => 100,
            'discount_one_year' => 0,
            'discount_two_year' => 10,
            'discount_three_year' => 20,
        ]);

        $resolved = app(SslPlanService::class)->resolve($plan->fresh());

        // Same expectations as SslPlanCmsTest::term_discounts_produce_the_expected_bdt_totals
        $this->assertSame(1200.0, (float) $resolved['terms'][1]['total']);
        $this->assertSame(2160.0, (float) $resolved['terms'][2]['total']);
        $this->assertSame(2880.0, (float) $resolved['terms'][3]['total']);
        $this->assertSame(1, $resolved['default']['years']);
        $this->assertSame('local', $resolved['source']);
    }

    // ── admin surface ─────────────────────────────────────────────────────

    /** @test */
    public function the_admin_page_shows_reseller_price_profit_revenue_and_selling_per_plan()
    {
        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->get('/admin/ssl-plans')
            ->assertOk()
            ->assertSee('Reseller price')
            ->assertSee('Profit %')
            ->assertSee('Revenue')
            ->assertSee('Selling price')
            // Cost, profit % and selling for the Premium/Standard plans.
            ->assertSee('$3.33')
            ->assertSee('20.12%')
            ->assertSee('19.7%')
            ->assertSee('$4.99')
            // The BDT figure the customer will actually pay.
            ->assertSee('Customer pays')
            ->assertSee('5,760');
    }

    /** @test */
    public function the_admin_page_no_longer_has_the_status_banner_or_refresh_link()
    {
        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->get('/admin/ssl-plans')
            ->assertOk()
            ->assertDontSee('Refresh from ResellerClub')
            ->assertDontSee('ResellerClub is not configured')
            ->assertDontSee('Live pricing active')
            ->assertDontSee('refresh=1', false);

        // Also gone when the integration is off.
        $this->configured = false;
        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->get('/admin/ssl-plans')
            ->assertOk()
            ->assertDontSee('ResellerClub is not configured')
            ->assertDontSee('Refresh from ResellerClub');
    }

    /** @test */
    public function the_admin_page_explains_a_plan_that_resellerclub_did_not_price()
    {
        $this->selling = [];
        $this->cost = [];

        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->get('/admin/ssl-plans')
            ->assertOk()
            ->assertSee('No price returned for plan ID 300')
            ->assertDontSee('20.12%');
    }

    /** @test */
    public function the_admin_page_still_prices_plans_when_only_some_answer()
    {
        // Only EV answers, so the page is genuinely live for one plan.
        $this->selling = ['672' => $this->selling['672']];
        $this->cost = ['672' => $this->cost['672']];

        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->get('/admin/ssl-plans')
            ->assertOk()
            ->assertSee('No price returned for plan ID 300')
            ->assertSee('20.04%');
    }

    /** @test */
    public function the_admin_page_reads_fresh_prices_on_every_load()
    {
        cache()->put('resellerclub.pricing.v2', ['stale' => true], 300);
        cache()->put('resellerclub.reseller_cost_pricing.v1', ['stale' => true], 300);

        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->get('/admin/ssl-plans')
            ->assertOk();

        $this->assertNull(cache()->get('resellerclub.pricing.v2'));
        $this->assertNull(cache()->get('resellerclub.reseller_cost_pricing.v1'));
    }

    /** @test */
    public function a_guest_cannot_open_the_ssl_plan_list()
    {
        $this->get('/admin/ssl-plans')->assertRedirect('/admin/login');
    }

    /** @test */
    public function the_admin_form_has_no_price_fields_and_requires_a_resellerclub_plan_id()
    {
        $plan = SslPlan::where('plan_key', 'positive-ssl')->first();
        $this->assertSame(300, $plan->resellerclubPlanId());

        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->get("/admin/ssl-plans/{$plan->id}/edit")
            ->assertOk()
            ->assertSee('name="resellerclub_plan_id"', false)
            ->assertSee('value="300"', false)
            ->assertSee('name="features[0][text]"', false)
            ->assertDontSee('name="monthly_price"', false)
            ->assertDontSee('name="renewal_price"', false)
            ->assertDontSee('name="discount_one_year"', false)
            ->assertDontSee('name="discount_two_year"', false)
            ->assertDontSee('name="discount_three_year"', false)
            ->assertDontSee('Fallback monthly price');

        $payload = [
            'plan_key' => 'positive-ssl',
            'name' => $plan->name,
            'tagline' => (string) $plan->tagline,
            'sort' => (string) $plan->sort,
            'is_visible' => '1',
            'features' => [['text' => 'Domain Validation']],
        ];

        // Re-link to a different plan ID.
        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->post("/admin/ssl-plans/{$plan->id}", array_merge($payload, ['resellerclub_plan_id' => '672']))
            ->assertRedirect('/admin/ssl-plans');
        $this->assertSame(672, $plan->fresh()->resellerclubPlanId());

        // The link is required now: an empty value is rejected, not "unlinked".
        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->from("/admin/ssl-plans/{$plan->id}/edit")
            ->post("/admin/ssl-plans/{$plan->id}", array_merge($payload, ['resellerclub_plan_id' => '']))
            ->assertSessionHasErrors('resellerclub_plan_id');
        $this->assertSame(672, $plan->fresh()->resellerclubPlanId());
    }

    /** @test */
    public function posted_price_fields_are_ignored_so_the_admin_cannot_override_resellerclub()
    {
        $plan = SslPlan::where('plan_key', 'positive-ssl')->first();
        $before = [(float) $plan->monthly_price, (float) $plan->renewal_price, (float) $plan->discount_two_year];

        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->post("/admin/ssl-plans/{$plan->id}", [
                'plan_key' => 'positive-ssl',
                'name' => 'Renamed SSL',
                'is_visible' => '1',
                'resellerclub_plan_id' => '300',
                'monthly_price' => '1',
                'renewal_price' => '1',
                'discount_two_year' => '90',
            ])
            ->assertRedirect('/admin/ssl-plans');

        $fresh = $plan->fresh();
        $this->assertSame('Renamed SSL', $fresh->name);
        $this->assertSame($before, [(float) $fresh->monthly_price, (float) $fresh->renewal_price, (float) $fresh->discount_two_year]);

        // The customer card still shows the ResellerClub price, not the posted one.
        $row = collect(app(SslPlanService::class)->plans())->firstWhere('key', 'positive-ssl');
        $this->assertSame('resellerclub', $row['source']);
    }

    /** @test */
    public function a_non_numeric_plan_id_is_rejected()
    {
        $plan = SslPlan::where('plan_key', 'positive-ssl')->first();

        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->from("/admin/ssl-plans/{$plan->id}/edit")
            ->post("/admin/ssl-plans/{$plan->id}", [
                'plan_key' => 'positive-ssl',
                'name' => $plan->name,
                'resellerclub_plan_id' => 'not-a-number',
            ])
            ->assertSessionHasErrors('resellerclub_plan_id');

        $this->assertSame(300, $plan->fresh()->resellerclubPlanId());
    }

    /** @test */
    public function an_unpriced_plan_with_no_saved_fallback_is_hidden_from_customers()
    {
        $this->selling = [];
        $this->cost = [];
        SslPlan::where('plan_key', 'positive-ssl')->update(['monthly_price' => 0, 'renewal_price' => 0]);

        $keys = collect(app(SslPlanService::class)->plans())->pluck('key')->all();

        $this->assertNotContains('positive-ssl', $keys);
        $this->assertContains('comodo-ssl', $keys); // others keep their saved fallback
    }

    /** @test */
    public function the_api_page_registers_ssl_pricing_without_calling_resellerclub()
    {
        $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->get('/admin/apis')
            ->assertOk()
            ->assertSee('RESELLERCLUB_SSL_LIVE_PRICING')
            ->assertSee('4 of 4 plans linked')
            ->assertSee('and SSL certificate selling prices (sslcert plans).');

        // The page must stay side-effect free: no price call, no rate call.
        $this->assertSame(0, app(ResellerClubClient::class)->sellingCalls);
        $this->assertSame(0, app(ResellerClubClient::class)->costCalls);
    }

    // ── schema ────────────────────────────────────────────────────────────

    /** @test */
    public function the_migration_links_the_four_seeded_plans()
    {
        $this->assertTrue(Schema::hasColumn('ssl_plans', 'resellerclub_plan_id'));

        $expected = [
            'positive-ssl' => 300,
            'comodo-ssl' => 299,
            'positive-ssl-wildcard' => 301,
            'ev-ssl' => 672,
        ];

        foreach ($expected as $key => $planId) {
            $this->assertSame(
                $planId,
                SslPlan::where('plan_key', $key)->first()->resellerclubPlanId(),
                $key . ' should be linked to ResellerClub plan ' . $planId
            );
        }
    }
}
