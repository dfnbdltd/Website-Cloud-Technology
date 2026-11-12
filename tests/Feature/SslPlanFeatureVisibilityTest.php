<?php

namespace Tests\Feature;

use App\Models\SslPlan;
use App\Models\SslPlanFeature;
use App\Services\ExchangeRateService;
use App\Services\ResellerClubClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Per-feature hide/show and delete on SSL plan cards.
 *
 * Two distinct ideas, and the tests exist to keep them apart:
 *
 *   hidden   the admin turned the row off; the customer card does not print it
 *   unavailable  the row renders with a red cross ("not included")
 *
 * A single eye toggle in the editor drives `shown_to_customers`, and the same
 * save keeps `is_available` in step with it so nothing reading the old column
 * can disagree.
 */
class SslPlanFeatureVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.resellerclub.ssl_live_pricing' => true]);

        // The ResellerClub price list is cached for 5 minutes and the cache is
        // NOT reset by RefreshDatabase, so a stub from an earlier test would
        // leak into this one and quietly drive the page from stale prices.
        cache()->forget('resellerclub.pricing.v2');
        cache()->forget('resellerclub.reseller_cost_pricing.v1');

        // Same idea for the service's static "are the tables there?" memo:
        // a test that drops the tables must not leave later tests on the
        // config fallback.
        \App\Services\SslPlanService::resetSchemaCache();

        $this->app->instance(ExchangeRateService::class, new class extends ExchangeRateService {
            public function usdToBdt()
            {
                return ['rate' => 120.0, 'source' => 'fallback', 'updated_at' => null];
            }
        });

        // Prices are irrelevant here but the cards need to render as live.
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
                return ['sslcert' => ['plans' => []]];
            }
        });
    }

    private function admin()
    {
        return $this->withSession(['admin' => ['email' => 'admin@example.test']]);
    }

    private function positiveSsl()
    {
        return SslPlan::where('plan_key', 'positive-ssl')->firstOrFail();
    }

    private function payload(SslPlan $plan, array $features)
    {
        return [
            'plan_key' => $plan->plan_key,
            'name' => $plan->name,
            'tagline' => (string) $plan->tagline,
            'sort' => (string) $plan->sort,
            'is_visible' => '1',
            'resellerclub_plan_id' => (string) $plan->resellerclubPlanId(),
            'features' => $features,
        ];
    }

    /** @test */
    public function the_column_exists_and_every_existing_feature_still_shows()
    {
        $this->assertTrue(Schema::hasColumn('ssl_plan_features', 'shown_to_customers'));

        // The migration must not have changed what customers see today.
        $this->assertSame(0, SslPlanFeature::where('shown_to_customers', false)->count());
        $this->assertSame(32, SslPlanFeature::count());
    }

    /** @test */
    public function hiding_a_feature_keeps_the_row_and_stops_it_printing_on_the_card()
    {
        $plan = $this->positiveSsl();

        // Use text unique to this plan: the seed shares feature wording across
        // plans, so asserting on "1 Sub-domain" would also match other cards.
        $this->admin()->post("/admin/ssl-plans/{$plan->id}", $this->payload($plan, [
            ['text' => 'UNIQUE-KEEP-ME', 'shown' => '1', 'shown_marker' => '1'],
            ['text' => 'UNIQUE-HIDE-ME', 'shown_marker' => '1'],
        ]))->assertRedirect('/admin/ssl-plans');

        // Row still stored (hiding is not deleting) but flagged both ways.
        $hidden = $plan->fresh()->features()->where('feature', 'UNIQUE-HIDE-ME')->first();
        $this->assertNotNull($hidden, 'a hidden feature must stay in the database');
        $this->assertFalse($hidden->isShownToCustomers());
        $this->assertFalse((bool) $hidden->is_available, 'is_available must follow the eye toggle');

        // The customer page prints the shown one and not the hidden one.
        $this->get('/services/ssl-certificate')
            ->assertOk()
            ->assertSee('UNIQUE-KEEP-ME')
            ->assertDontSee('UNIQUE-HIDE-ME');
    }

    /** @test */
    public function showing_a_hidden_feature_brings_it_back()
    {
        $plan = $this->positiveSsl();
        $row = $plan->features()->orderBy('sort')->first();

        // Give the row text no other card uses, then hide it directly.
        $row->forceFill([
            'feature' => 'UNIQUE-TOGGLE-ME',
            'shown_to_customers' => false,
            'is_available' => false,
        ])->save();

        $this->get('/services/ssl-certificate')->assertDontSee('UNIQUE-TOGGLE-ME');

        // Save with the eye checked again.
        $this->admin()->post("/admin/ssl-plans/{$plan->id}", $this->payload($plan, [
            ['text' => 'UNIQUE-TOGGLE-ME', 'shown' => '1'],
        ]))->assertRedirect('/admin/ssl-plans');

        $row = $plan->fresh()->features()->where('feature', 'UNIQUE-TOGGLE-ME')->first();
        $this->assertTrue($row->isShownToCustomers());
        $this->assertTrue((bool) $row->is_available);

        $this->get('/services/ssl-certificate')->assertSee('UNIQUE-TOGGLE-ME');
    }

    /** @test */
    public function deleting_a_feature_removes_it_from_the_database_and_the_card()
    {
        $plan = $this->positiveSsl();

        $this->admin()->post("/admin/ssl-plans/{$plan->id}", $this->payload($plan, [
            ['text' => 'UNIQUE-DELETE-ME', 'shown' => '1'],
        ]))->assertRedirect('/admin/ssl-plans');

        $doomed = $plan->fresh()->features()->where('feature', 'UNIQUE-DELETE-ME')->firstOrFail();
        $this->get('/services/ssl-certificate')->assertSee('UNIQUE-DELETE-ME');

        // Deleting in the editor simply omits the row from the POST.
        $this->admin()->post("/admin/ssl-plans/{$plan->id}", $this->payload($plan, [
            ['text' => 'UNIQUE-KEEP-AFTER-DELETE', 'shown' => '1'],
        ]))->assertRedirect('/admin/ssl-plans');

        $this->assertDatabaseMissing('ssl_plan_features', ['id' => $doomed->id]);
        $this->assertSame(1, $plan->fresh()->features()->count());

        $this->get('/services/ssl-certificate')
            ->assertDontSee('UNIQUE-DELETE-ME')
            ->assertSee('UNIQUE-KEEP-AFTER-DELETE');
    }

    /** @test */
    public function a_blank_text_row_is_dropped_even_when_it_is_marked_shown()
    {
        $plan = $this->positiveSsl();
        $before = $plan->features()->count();

        $this->admin()->post("/admin/ssl-plans/{$plan->id}", $this->payload($plan, [
            ['text' => 'Kept feature', 'shown' => '1'],
            ['text' => '   ', 'shown' => '1'],
            ['text' => 'Never posted as shown'],
        ]))->assertRedirect('/admin/ssl-plans');

        $this->assertSame(2, $plan->fresh()->features()->count());
        $this->assertNotNull($plan->fresh()->features()->where('feature', 'Kept feature')->first());
    }

    /** @test */
    public function an_explicitly_unchecked_eye_hides_the_feature()
    {
        $plan = $this->positiveSsl();

        // The editor always posts the marker; an unchecked eye posts no `shown`.
        $this->admin()->post("/admin/ssl-plans/{$plan->id}", $this->payload($plan, [
            ['text' => 'MARKER-ONLY-HIDDEN', 'shown_marker' => '1'],
        ]))->assertRedirect('/admin/ssl-plans');

        $row = $plan->fresh()->features()->first();
        $this->assertSame('MARKER-ONLY-HIDDEN', $row->feature);
        $this->assertFalse($row->isShownToCustomers());

        $this->get('/services/ssl-certificate')->assertDontSee('MARKER-ONLY-HIDDEN');
    }

    /** @test */
    public function a_feature_posted_without_any_visibility_info_stays_visible()
    {
        $plan = $this->positiveSsl();

        // No `shown` and no marker: an older form or a script. This has to keep
        // working, because before the visibility column every feature showed.
        $this->admin()->post("/admin/ssl-plans/{$plan->id}", $this->payload($plan, [
            ['text' => 'NO-FLAG-KEEP-VISIBLE'],
        ]))->assertRedirect('/admin/ssl-plans');

        $row = $plan->fresh()->features()->first();
        $this->assertSame('NO-FLAG-KEEP-VISIBLE', $row->feature);
        $this->assertTrue($row->isShownToCustomers());
        $this->assertTrue((bool) $row->is_available);

        $this->get('/services/ssl-certificate')->assertSee('NO-FLAG-KEEP-VISIBLE');
    }

    /** @test */
    public function the_two_feature_lists_stay_separate_a_hidden_row_is_not_reported_as_unavailable()
    {
        $plan = $this->positiveSsl();

        $this->admin()->post("/admin/ssl-plans/{$plan->id}", $this->payload($plan, [
            ['text' => 'Shown included', 'shown' => '1', 'shown_marker' => '1'],
            // Marker without `shown` = the eye was switched off.
            ['text' => 'Shown excluded', 'shown_marker' => '1'],
        ]));

        $cards = collect(app(\App\Services\SslPlanService::class)->plans())->keyBy('key');
        $features = collect($cards['positive-ssl']['features'])->keyBy('text');

        // Included: shown + available.
        $this->assertTrue($features['Shown included']['shown']);
        $this->assertTrue($features['Shown included']['available']);

        // Missing the eye: the card drops it entirely rather than drawing a
        // cross. (The row itself is still stored: see the admin test below.)
        $this->assertFalse(isset($features['Shown excluded']));
        $this->assertSame(1, $plan->fresh()->features()->where('shown_to_customers', false)->count());
    }

    /** @test */
    public function the_service_drops_hidden_rows_from_the_card_array()
    {
        $plan = $this->positiveSsl();
        $hidden = $plan->features()->orderBy('sort')->first();
        $hidden->forceFill(['shown_to_customers' => false])->save();

        $card = collect(app(\App\Services\SslPlanService::class)->plans())->keyBy('key')['positive-ssl'];

        // The card array carries only what should print, so the partial stays
        // a plain loop. The admin editor reads the model, not this array.
        $this->assertCount($plan->fresh()->features()->count() - 1, $card['features']);
        $this->assertNotContains($hidden->feature, array_column($card['features'], 'text'));
    }

    /** @test */
    public function the_legacy_config_fallback_treats_every_feature_as_shown()
    {
        // Drop both SSL tables so SslPlanService takes the config/ssl_plans.php
        // path, and reset its memoised schema answer so the drop is noticed.
        Schema::drop('ssl_plan_features');
        Schema::drop('ssl_plans');
        \App\Services\SslPlanService::resetSchemaCache();

        try {
            $cards = app(\App\Services\SslPlanService::class)->plans();
            $this->assertNotEmpty($cards, 'the config fallback must still return plans');

            foreach ($cards as $card) {
                $this->assertNotEmpty($card['features']);
                foreach ($card['features'] as $feature) {
                    $this->assertTrue($feature['shown'], 'fallback features must default to shown');
                }
            }
        } finally {
            // Leave the memo clean even if an assertion above failed, so this
            // test cannot poison the rest of the suite.
            \App\Services\SslPlanService::resetSchemaCache();
        }
    }

    /** @test */
    public function the_admin_editor_renders_one_toggle_per_feature_with_the_right_state()
    {
        $plan = $this->positiveSsl();
        $plan->features()->orderBy('sort')->first()
            ->forceFill(['shown_to_customers' => false])->save();

        $response = $this->admin()->get("/admin/ssl-plans/{$plan->id}/edit")->assertOk();
        $html = $response->getContent();

        // One checkbox per stored feature, counted by input name: the
        // "ssl-shown" class also appears once in the inline JS that builds a row.
        $count = $plan->fresh()->features()->count();
        $this->assertSame($count, preg_match_all('/name="features\[\d+\]\[shown\]"/', $html));

        // The hidden row is painted as hidden, so the state is visible in a list.
        $this->assertStringContainsString('is-hidden-feature', $html);

        // First row is the hidden one, so its checkbox carries no `checked`.
        preg_match('/name="features\[0\]\[shown\]"[^>]*>/', $html, $first);
        $this->assertNotEmpty($first, 'the first feature row must render a visibility toggle');
        $this->assertStringNotContainsString('checked', $first[0]);

        // The second row is still shown, so it does.
        preg_match('/name="features\[1\]\[shown\]"[^>]*>/', $html, $second);
        $this->assertNotEmpty($second);
        $this->assertStringContainsString('checked', $second[0]);
    }

    /** @test */
    public function a_new_plan_can_be_created_with_a_mix_of_shown_and_hidden_features()
    {
        $this->admin()->post('/admin/ssl-plans', [
            'plan_key' => 'mixed-ssl',
            'name' => 'UNIQUE-MIXED-PLAN',
            'resellerclub_plan_id' => '301',
            'sort' => '9',
            'is_visible' => '1',
            'features' => [
                ['text' => 'UNIQUE-VISIBLE-ONE', 'shown' => '1', 'shown_marker' => '1'],
                ['text' => 'UNIQUE-HIDDEN-ONE', 'shown_marker' => '1'],
            ],
        ])->assertRedirect('/admin/ssl-plans');

        $plan = SslPlan::where('plan_key', 'mixed-ssl')->firstOrFail();
        $rows = $plan->features()->get()->keyBy('feature');

        $this->assertTrue($rows['UNIQUE-VISIBLE-ONE']->isShownToCustomers());
        $this->assertFalse($rows['UNIQUE-HIDDEN-ONE']->isShownToCustomers());

        // The plan name is the reliable proof the card rendered: feature text
        // can also appear on a plan sharing the same ResellerClub ID.
        $this->get('/services/ssl-certificate')
            ->assertSee('UNIQUE-MIXED-PLAN')
            ->assertSee('UNIQUE-VISIBLE-ONE')
            ->assertDontSee('UNIQUE-HIDDEN-ONE');
    }

    /** @test */
    public function hiding_the_whole_plan_still_hides_it_regardless_of_its_features()
    {
        $plan = $this->positiveSsl();

        // Give the plan a name and a feature that appear nowhere else, so the
        // assertions cannot match the navbar, the coming-soon dialog or a
        // neighbouring card's feature list.
        $plan->update(['name' => 'UNIQUE-PLAN-NAME']);
        $this->admin()->post("/admin/ssl-plans/{$plan->id}", $this->payload($plan->fresh(), [
            ['text' => 'UNIQUE-PLAN-FEATURE', 'shown' => '1'],
        ]))->assertRedirect('/admin/ssl-plans');

        // Sanity: the save really took effect before the visibility assertion.
        $this->assertSame('UNIQUE-PLAN-NAME', $plan->fresh()->name);
        $this->assertSame(1, $plan->fresh()->features()->count());

        $this->get('/services/ssl-certificate')
            ->assertSee('UNIQUE-PLAN-NAME')
            ->assertSee('UNIQUE-PLAN-FEATURE');

        // Hide the whole plan.
        $this->admin()->post("/admin/ssl-plans/{$plan->id}/visibility")->assertRedirect('/admin/ssl-plans');

        $this->get('/services/ssl-certificate')
            ->assertDontSee('UNIQUE-PLAN-NAME')
            ->assertDontSee('UNIQUE-PLAN-FEATURE');

        // Showing it again restores the card and its features.
        $this->admin()->post("/admin/ssl-plans/{$plan->id}/visibility")->assertRedirect('/admin/ssl-plans');

        $this->get('/services/ssl-certificate')
            ->assertSee('UNIQUE-PLAN-NAME')
            ->assertSee('UNIQUE-PLAN-FEATURE');
    }

    /** @test */
    public function a_guest_cannot_change_feature_visibility()
    {
        $plan = $this->positiveSsl();

        $this->post("/admin/ssl-plans/{$plan->id}", $this->payload($plan, [
            ['text' => 'Injected by guest', 'shown' => '1'],
        ]))->assertRedirect('/admin/login');

        $this->assertSame(0, SslPlanFeature::where('feature', 'Injected by guest')->count());
    }
}
