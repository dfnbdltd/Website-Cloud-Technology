<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Models\HostingPlanFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hosting plans: Hosting -> Local / USA / WordPress.
 *
 * Covers the region migration, the public pages (/hosting/local/shared,
 * /hosting/usa/vps, /hosting/wordpress, old URL redirects), the navbar and
 * left rail, the client area, and the admin CMS at /admin/hosting-plans
 * (bulk price list, copy from Local, add / edit / show-hide / delete).
 */
class HostingPlanCmsTest extends TestCase
{
    use RefreshDatabase;

    private function adminSession()
    {
        return $this->withSession(['admin' => ['email' => 'admin@example.test']]);
    }

    private function clientSession()
    {
        return $this->withSession(['customer' => ['id' => 1, 'uid' => 'CT-000001', 'name' => 'Test Client', 'initials' => 'TC', 'email' => 'c@example.test']]);
    }

    private function localShared($name = 'Plan A')
    {
        return HostingPlan::where('type', 'shared')->where('region', 'local')->where('name', $name)->firstOrFail();
    }

    /** A valid add/edit form post for the card form. */
    private function cardPayload(array $overrides = [])
    {
        return array_replace_recursive([
            'region' => 'usa',
            'type' => 'shared',
            'name' => 'US Starter',
            'description' => 'For small sites.',
            'badge' => '',
            'style_class' => 'plan-circle-basic',
            'sort' => 1,
            'is_visible' => '1',
            'price' => ['monthly' => '10', 'yearly' => '100', 'twoYear' => '', 'threeYear' => ''],
            'status' => ['monthly' => 'on', 'yearly' => 'on', 'twoYear' => 'soon', 'threeYear' => 'soon'],
            'features' => ['monthly' => [
                ['line' => '10 GB NVMe storage', 'group' => 'primary', 'is_available' => '1'],
                ['line' => 'eCommerce', 'group' => 'extra'],
            ]],
        ], $overrides);
    }

    /* ------------------------------------------------------------------ */
    /* Region migration + Local pages                                      */
    /* ------------------------------------------------------------------ */

    /** @test */
    public function every_existing_plan_becomes_local_and_other_regions_start_empty()
    {
        $this->assertSame([5, 4, 5], [
            HostingPlan::where('region', 'local')->where('type', 'shared')->count(),
            HostingPlan::where('region', 'local')->where('type', 'vps')->count(),
            HostingPlan::where('region', 'local')->where('type', 'dedicated')->count(),
        ]);
        $this->assertSame(0, HostingPlan::where('region', '!=', 'local')->count());
    }

    /** @test */
    public function local_pages_render_the_seeded_cards_under_the_new_urls()
    {
        foreach (['shared' => 'Plan A', 'vps' => null, 'dedicated' => null] as $type => $needle) {
            $response = $this->get("/hosting/local/{$type}")->assertOk();
            $response->assertSee('hosting-rail');
            $response->assertDontSee('hosting-empty-pill');
            if ($needle) {
                $response->assertSee($needle);
            }
        }
    }

    /** @test */
    public function old_hosting_urls_redirect_to_the_local_pages()
    {
        foreach (['shared', 'vps', 'dedicated'] as $type) {
            $this->get("/hosting/{$type}")->assertRedirect("/hosting/local/{$type}")->assertStatus(301);
        }
    }

    /** @test */
    public function unknown_regions_and_types_are_404()
    {
        $this->get('/hosting/mars/shared')->assertNotFound();
        $this->get('/hosting/local/wordpress')->assertNotFound();
    }

    /** @test */
    public function usa_and_wordpress_show_a_coming_soon_state_until_plans_exist()
    {
        foreach (['/hosting/usa/shared', '/hosting/usa/vps', '/hosting/usa/dedicated', '/hosting/wordpress'] as $url) {
            $this->get($url)->assertOk()->assertSee('Plans coming soon');
        }
    }

    /** @test */
    public function every_public_hosting_page_has_the_hosting_menu_and_the_rail()
    {
        foreach (['/hosting/local/shared', '/hosting/usa/vps', '/hosting/wordpress'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('site-nav-hosting', $html);
            $this->assertStringContainsString('Local Hosting', $html);
            $this->assertStringContainsString('USA Hosting', $html);
            $this->assertStringContainsString('WordPress Hosting', $html);
            $this->assertStringContainsString('class="hosting-rail', $html);
        }
    }

    /* ------------------------------------------------------------------ */
    /* USA + WordPress with plans                                          */
    /* ------------------------------------------------------------------ */

    /** @test */
    public function a_visible_usa_card_shows_on_the_usa_page_only()
    {
        $this->adminSession()->post('/admin/hosting-plans', $this->cardPayload())->assertRedirect('/admin/hosting-plans/usa/shared');

        $this->get('/hosting/usa/shared')->assertOk()->assertSee('US Starter')->assertDontSee('Plans coming soon');
        $this->get('/hosting/local/shared')->assertDontSee('US Starter');
        $this->get('/hosting/usa/vps')->assertDontSee('US Starter');
    }

    /** @test */
    public function wordpress_page_shows_cards_with_prices_tabs_and_coming_soon_terms()
    {
        $this->adminSession()->post('/admin/hosting-plans', $this->cardPayload([
            'region' => 'wordpress', 'type' => 'wordpress', 'name' => 'WP Basic',
            'price' => ['monthly' => '350', 'yearly' => '3500'],
        ]))->assertRedirect('/admin/hosting-plans/wordpress/wordpress');

        $html = $this->get('/hosting/wordpress')->assertOk()->assertSee('WP Basic')->getContent();
        $this->assertStringContainsString('wp-card', $html);
        $this->assertStringContainsString('350', $html);
        $this->assertStringContainsString('data-period="monthly"', $html);
        $this->assertStringContainsString('data-period="yearly"', $html);
        $this->assertStringContainsString('Coming soon', $html);   // 2 / 3 year are not on sale
        $this->assertStringContainsString('10 GB', $html);
    }

    /** @test */
    public function a_hidden_card_is_not_public()
    {
        $this->adminSession()->post('/admin/hosting-plans', $this->cardPayload(['is_visible' => null]));
        $this->assertFalse(HostingPlan::where('name', 'US Starter')->firstOrFail()->is_visible);
        $this->get('/hosting/usa/shared')->assertDontSee('US Starter');
    }

    /* ------------------------------------------------------------------ */
    /* Admin                                                               */
    /* ------------------------------------------------------------------ */

    /** @test */
    public function guests_cannot_use_the_admin_hosting_pages()
    {
        $this->get('/admin/hosting-plans')->assertRedirect('/admin/login');
        $this->get('/admin/hosting-plans/usa/vps')->assertRedirect('/admin/login');
        $this->get('/admin/hosting-plans/create/usa/shared')->assertRedirect('/admin/login');
        $this->get('/admin/hosting-plans/1/edit')->assertRedirect('/admin/login');
        $this->post('/admin/hosting-plans/local/shared/save')->assertRedirect('/admin/login');
        $this->post('/admin/hosting-plans/usa/shared/copy')->assertRedirect('/admin/login');
        $this->post('/admin/hosting-plans')->assertRedirect('/admin/login');
        $this->post('/admin/hosting-plans/1')->assertRedirect('/admin/login');
        $this->post('/admin/hosting-plans/1/delete')->assertRedirect('/admin/login');
    }

    /** @test */
    public function admin_list_pages_render_for_every_section()
    {
        $this->adminSession()->get('/admin/hosting-plans')->assertOk()->assertSee('Plan A')->assertSee('Local Hosting')->assertSee('USA Hosting')->assertSee('WordPress Hosting');
        $this->adminSession()->get('/admin/hosting-plans/local/vps')->assertOk();
        $this->adminSession()->get('/admin/hosting-plans/usa/dedicated')->assertOk()->assertSee('Copy from Local Dedicated');
        $this->adminSession()->get('/admin/hosting-plans/wordpress')->assertOk()->assertSee('Copy from Local Shared');
        $this->adminSession()->get('/admin/hosting-plans/create/wordpress/wordpress')->assertOk();
        $this->adminSession()->get('/admin/hosting-plans/create/usa/vps')->assertOk()->assertSee('plan-circle-basic');
        $this->adminSession()->get('/admin/hosting-plans/' . $this->localShared()->id . '/edit')->assertOk()->assertSee('NVMe storage');
    }

    /** @test */
    public function bulk_save_updates_prices_for_every_card_in_the_section()
    {
        $a = $this->localShared('Plan A');
        $b = $this->localShared('Plan B');
        $oldYearlyB = $b->price_yearly;

        $this->adminSession()->post('/admin/hosting-plans/local/shared/save', ['rows' => [
            $a->id => [
                'price' => ['monthly' => '499', 'yearly' => '4990', 'twoYear' => '', 'threeYear' => ''],
            ],
        ]])->assertRedirect('/admin/hosting-plans/local/shared');

        $a->refresh();
        $this->assertEquals(499, $a->price_monthly);
        $this->assertTrue($a->term_monthly_active);
        $this->assertTrue($a->is_visible);
        $this->assertFalse($a->term_two_year_active);
        $this->assertTrue($a->term_two_year_visible);
        $this->assertFalse($a->term_three_year_active);
        $this->assertTrue($a->term_three_year_visible);
        // The card that was not in the form is untouched.
        $this->assertEquals($oldYearlyB, $b->fresh()->price_yearly);
    }

    /** @test */
    public function bulk_save_only_touches_cards_of_its_own_section()
    {
        $local = $this->localShared('Plan A');
        $this->adminSession()->post('/admin/hosting-plans/usa/shared/save', ['rows' => [
            $local->id => ['sort' => 99, 'visible' => '1', 'price' => ['monthly' => '1'], 'status' => ['monthly' => 'on']],
        ]])->assertRedirect();
        $this->assertNotSame(99, $local->fresh()->sort);
    }

    /** @test */
    public function copy_from_local_fills_an_empty_section_with_hidden_cards_and_features()
    {
        $this->adminSession()->post('/admin/hosting-plans/usa/shared/copy')->assertRedirect('/admin/hosting-plans/usa/shared');

        $copies = HostingPlan::where('region', 'usa')->where('type', 'shared')->get();
        $this->assertCount(5, $copies);
        $this->assertSame(0, $copies->where('is_visible', true)->count());
        $this->assertGreaterThan(0, $copies->first()->features()->count());
        $this->assertSame(5, HostingPlan::where('region', 'local')->where('type', 'shared')->count());

        // A second copy does nothing.
        $this->adminSession()->post('/admin/hosting-plans/usa/shared/copy')->assertSessionHas('error');
        $this->assertSame(5, HostingPlan::where('region', 'usa')->where('type', 'shared')->count());

        // WordPress copies Local Shared.
        $this->adminSession()->post('/admin/hosting-plans/wordpress/wordpress/copy');
        $this->assertSame(5, HostingPlan::where('region', 'wordpress')->where('type', 'wordpress')->count());
        $this->get('/hosting/wordpress')->assertSee('Plans coming soon'); // all hidden
    }

    /** @test */
    public function copy_never_runs_for_local()
    {
        $this->adminSession()->post('/admin/hosting-plans/local/shared/copy')->assertSessionHas('error');
        $this->assertSame(5, HostingPlan::where('region', 'local')->where('type', 'shared')->count());
    }

    /** @test */
    public function adding_a_card_saves_prices_status_and_feature_rows()
    {
        $this->adminSession()->post('/admin/hosting-plans', $this->cardPayload())->assertSessionHasNoErrors();

        $plan = HostingPlan::where('name', 'US Starter')->firstOrFail();
        $this->assertSame('usa', $plan->region);
        $this->assertSame('shared', $plan->type);
        $this->assertEquals(10, $plan->price_monthly);
        $this->assertTrue($plan->term_monthly_active);
        $this->assertFalse($plan->term_two_year_active);
        $this->assertSame(2, $plan->features()->where('billing_term', 'monthly')->count());
        $this->assertFalse((bool) $plan->features()->where('name', 'eCommerce')->first()->is_available);
    }

    /** @test */
    public function the_card_form_rejects_a_bad_section_and_a_missing_name()
    {
        $this->adminSession()->post('/admin/hosting-plans', $this->cardPayload(['type' => 'wordpress']))->assertSessionHasErrors('type');
        $this->adminSession()->post('/admin/hosting-plans', $this->cardPayload(['region' => 'mars']))->assertSessionHasErrors('region');
        $this->adminSession()->post('/admin/hosting-plans', $this->cardPayload(['name' => '']))->assertSessionHasErrors('name');
        $this->assertSame(0, HostingPlan::where('region', '!=', 'local')->count());
    }

    /** @test */
    public function each_billing_term_keeps_its_own_features_and_same_as_monthly_inherits()
    {
        $this->adminSession()->post('/admin/hosting-plans', $this->cardPayload(['features' => [
            'yearly' => [['line' => '50 GB NVMe storage', 'group' => 'primary', 'is_available' => '1']],
        ]]));
        $plan = HostingPlan::where('name', 'US Starter')->firstOrFail();

        $this->assertSame(1, $plan->features()->where('billing_term', 'yearly')->count());
        $this->assertSame('50 GB NVMe storage', $plan->features()->where('billing_term', 'yearly')->first()->value_monthly);

        // Ticking "Same as Monthly" removes the yearly rows: the page inherits Monthly.
        $this->adminSession()->post("/admin/hosting-plans/{$plan->id}", $this->cardPayload([
            'same' => ['yearly' => '1'],
            'features' => ['yearly' => [['line' => '50 GB NVMe storage', 'group' => 'primary', 'is_available' => '1']]],
        ]));
        $this->assertSame(0, $plan->features()->where('billing_term', 'yearly')->count());
        $this->assertSame(2, $plan->features()->where('billing_term', 'monthly')->count());
    }

    /** @test */
    public function editing_a_card_keeps_its_section_and_updates_the_public_page()
    {
        $plan = $this->localShared('Plan A');

        $this->adminSession()->post("/admin/hosting-plans/{$plan->id}", $this->cardPayload([
            'region' => 'local', 'type' => 'shared', 'name' => 'Plan A Renamed',
            'price' => ['monthly' => '555'],
        ]))->assertRedirect('/admin/hosting-plans/local/shared');

        $this->assertSame('local', $plan->fresh()->region);
        $html = $this->get('/hosting/local/shared')->getContent();
        $this->assertStringContainsString('Plan A Renamed', $html);
        $this->assertStringContainsString('data-monthly="555"', $html);
    }

    /** @test */
    public function a_term_name_that_equals_the_main_name_is_stored_as_inherited()
    {
        $this->adminSession()->post('/admin/hosting-plans', $this->cardPayload(['names' => ['yearly' => 'US Starter', 'twoYear' => 'US Starter 2Y']]));
        $plan = HostingPlan::where('name', 'US Starter')->firstOrFail();
        $this->assertNull($plan->name_yearly);
        $this->assertSame('US Starter 2Y', $plan->name_two_year);
    }

    /** @test */
    public function admin_can_show_hide_and_delete_a_card()
    {
        $plan = $this->localShared('Plan B');

        $this->adminSession()->post("/admin/hosting-plans/{$plan->id}/visibility")->assertRedirect('/admin/hosting-plans/local/shared');
        $this->assertFalse($plan->fresh()->is_visible);
        $this->get('/hosting/local/shared')->assertDontSee('Plan B');

        $this->adminSession()->post("/admin/hosting-plans/{$plan->id}/delete")->assertRedirect('/admin/hosting-plans/local/shared');
        $this->assertNull(HostingPlan::find($plan->id));
        $this->assertSame(0, HostingPlanFeature::where('hosting_plan_id', $plan->id)->count());
    }

    /* ------------------------------------------------------------------ */
    /* Client area                                                         */
    /* ------------------------------------------------------------------ */

    /** @test */
    public function client_area_lists_local_usa_and_wordpress_plans()
    {
        $this->clientSession()->get('/store/hosting/shared')->assertOk()->assertSee('Plan E')->assertSee('/hosting/local/shared');
        $this->clientSession()->get('/store/hosting/shared?region=usa')->assertOk();
        $this->clientSession()->get('/store/hosting/wordpress')->assertOk();
        $this->clientSession()->get('/store/hosting/dedicated')->assertOk();
    }

    /** @test */
    public function service_returns_legacy_cards_for_empty_local_and_nothing_for_empty_usa()
    {
        HostingPlan::query()->delete();
        $service = app(\App\Services\HostingPlanService::class);
        $this->assertSame([], $service->plans('shared', 'usa'));
        $this->assertSame([], $service->plans('wordpress', 'wordpress'));
        $this->assertNotEmpty($service->plans('shared', 'local'));
    }
}
