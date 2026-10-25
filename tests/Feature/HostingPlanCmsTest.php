<?php

namespace Tests\Feature;

use App\Models\HostingPlan;
use App\Models\HostingPlanFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CMS hosting plans: migration seeding, service fallback, the public pages
 * (shared / vps / dedicated) and the client-area compare page reading the
 * same CMS data, and the admin CRUD at /admin/hosting-plans (prices,
 * per-feature available/unavailable, visibility toggle, delete).
 */
class HostingPlanCmsTest extends TestCase
{
    use RefreshDatabase;

    /* ------------------------------------------------------------------ */
    /* Seeding + public pages                                              */
    /* ------------------------------------------------------------------ */

    /** @test */
    public function migration_seeds_the_three_hosting_types()
    {
        $this->assertSame([5, 4, 5], [
            HostingPlan::where('type', 'shared')->count(),
            HostingPlan::where('type', 'vps')->count(),
            HostingPlan::where('type', 'dedicated')->count(),
        ]);

        // Every shared card: 15 Monthly rows + 15 Yearly rows (the yearly
        // mirror the pre-CMS storage created, differ lines preserved).
        $planA = HostingPlan::where('type', 'shared')->where('name', 'Plan A')->first();
        $this->assertSame(30, $planA->features()->count());
        $this->assertSame(15, $planA->features()->where('billing_term', 'monthly')->count());
        $this->assertSame(15, $planA->features()->where('billing_term', 'yearly')->count());

        // 2/3-year terms ship inactive ("Coming soon"), monthly/yearly active.
        $this->assertFalse($planA->term_two_year_active);
        $this->assertFalse($planA->term_three_year_active);
        $this->assertTrue($planA->term_monthly_active);
    }

    /** @test */
    public function shared_page_renders_seeded_plans_with_availability_states()
    {
        $response = $this->get('/hosting/shared');
        $response->assertOk();
        $content = $response->getContent();

        // Plans A/B show the crossed "eCommerce" text… initial markup plus
        // the per-term feature-set templates (Monthly/Yearly own rows, 2/3
        // Years inherit Monthly = 5 occurrences per crossed card).
        $this->assertSame(10, substr_count($content, '>eCommerce<'));
        // …while C/D/E show the enabled "E-commerce" row (CMS-driven; the old
        // JS plan-letter override is gone).
        $this->assertSame(15, substr_count($content, '>E-commerce<'));

        // The red-cross class and price data attributes are present.
        $this->assertStringContainsString('feature-unavailable', $content);
        $this->assertStringContainsString('data-monthly="300"', $content);

        // Period-toggle hook keeps its data-feature attribute (initial
        // markup + one occurrence per per-term template, 5 cards total).
        $this->assertSame(25, substr_count($content, 'data-feature="ecommerce"'));
    }

    /** @test */
    public function billing_tabs_carry_cms_coming_soon_flags()
    {
        $content = $this->get('/hosting/shared')->getContent();

        // 4 tabs: monthly + yearly active, 2/3-year "Coming soon".
        $this->assertSame(2, substr_count($content, 'data-soon="false"'));
        $this->assertSame(2, substr_count($content, 'data-soon="true"'));

        $vps = $this->get('/hosting/vps')->getContent();
        $this->assertStringContainsString('data-period="threeYear" data-soon="true"', $vps);
    }

    /** @test */
    public function dedicated_page_renders_ecommerce_unavailable_like_before()
    {
        $content = $this->get('/hosting/dedicated')->getContent();

        // Dedicated's old JS never enabled ecommerce — still crossed
        // (initial markup + 4 per-term templates per crossed card).
        $this->assertSame(25, substr_count($content, '>eCommerce<'));
        $this->assertStringContainsString('data-period="twoYear" data-soon="true"', $content);
    }

    /** @test */
    public function vps_page_renders_seeded_plans()
    {
        $content = $this->get('/hosting/vps')->getContent();

        $this->assertStringContainsString('data-monthly="2000"', $content);
        $this->assertStringContainsString('Root access', $content);
        $this->assertSame(0, substr_count($content, '>eCommerce<'));

        // VPS ships no crossed rows (the marker class never lands on an <li>).
        $this->assertSame(0, substr_count($content, 'class="feature-unavailable"'));
    }

    /** @test */
    public function client_area_reads_the_same_cms_data()
    {
        $session = ['customer' => ['id' => 1, 'uid' => 'CT-000001', 'name' => 'Test Client', 'initials' => 'TC', 'email' => 'c@example.test']];

        $this->withSession($session)->get('/store/hosting/shared')
            ->assertOk()
            ->assertSee('Plan E');

        $content = $this->withSession($session)->get('/store/hosting/dedicated')->getContent();

        // Same plan data + billing-term flags reach the compare page.
        $this->assertStringContainsString('data-two-year="6000"', $content);
        $this->assertStringContainsString('data-soon="true"', $content);
    }

    /* ------------------------------------------------------------------ */
    /* Admin CRUD                                                          */
    /* ------------------------------------------------------------------ */

    private function adminSession()
    {
        return $this->withSession(['admin' => ['email' => 'admin@example.test']]);
    }

    private function planPayload(HostingPlan $plan, array $overrides = [])
    {
        return array_merge([
            'type' => $plan->type,
            'name' => $plan->name,
            'description' => $plan->description,
            'badge' => $plan->badge,
            'style_class' => $plan->style_class,
            'sort' => $plan->sort,
            'is_visible' => '1',
            'price_monthly' => (string) $plan->price_monthly,
            'price_yearly' => (string) $plan->price_yearly,
            'price_two_year' => (string) $plan->price_two_year,
            'price_three_year' => (string) $plan->price_three_year,
            // Each card owns its billing terms (unticked ones render as
            // "Coming soon" on that card only).
            'term_monthly_active' => '1',
            'term_yearly_active' => '1',
        ], $overrides);
    }

    /** @test */
    public function editing_a_cards_details_never_touches_prices_or_other_terms()
    {
        // VPS -> 2 Years -> edit Plan A (the card save posts NO prices/terms).
        $planA = HostingPlan::where('type', 'vps')->where('name', 'Plan A')->first();
        $before = $planA->only([
            'price_monthly', 'price_yearly', 'price_two_year', 'price_three_year',
            'term_monthly_active', 'term_yearly_active', 'term_two_year_active', 'term_three_year_active',
        ]);

        $this->adminSession()
            ->post("/admin/hosting-plans/{$planA->id}", [
                'type' => 'vps',
                'name' => 'Plan A renamed',
                'description' => 'Updated copy.',
                'badge' => 'Cheapest',
                'style_class' => 'plan-circle-basic',
                'sort' => '0',
                'is_visible' => '1',
                // Just a feature edit — every card x term must stay isolated.
                'features' => [
                    ['group' => 'primary', 'line_monthly' => '2 Core CPU', 'line_yearly' => '', 'feature_key' => '', 'is_available' => '1'],
                ],
            ])
            ->assertRedirect('/admin/hosting-plans/vps/monthly');

        $this->assertSame($before, $planA->fresh()->only([
            'price_monthly', 'price_yearly', 'price_two_year', 'price_three_year',
            'term_monthly_active', 'term_yearly_active', 'term_two_year_active', 'term_three_year_active',
        ]));
        $this->assertSame('Plan A renamed', $planA->fresh()->name);
    }

    /** @test */
    public function saving_a_card_returns_the_admin_to_the_term_view_they_came_from()
    {
        $plan = HostingPlan::where('type', 'vps')->where('name', 'Plan A')->first();

        $this->adminSession()
            ->post("/admin/hosting-plans/{$plan->id}", [
                'type' => 'vps',
                'name' => 'Plan A',
                'badge' => 'Cheapest',
                'style_class' => 'plan-circle-basic',
                'sort' => '0',
                'is_visible' => '1',
                'return_term' => 'twoYear',
                'features' => [],
            ])
            ->assertRedirect('/admin/hosting-plans/vps/twoYear');
    }

    /** @test */
    public function saving_a_card_from_a_term_view_updates_only_that_terms_price_and_switch()
    {
        // VPS Plan A through its 2 Years Edit page.
        $planA = HostingPlan::where('type', 'vps')->where('name', 'Plan A')->first();
        $before = $planA->only([
            'name', 'price_monthly', 'price_yearly', 'price_three_year',
            'term_monthly_active', 'term_yearly_active', 'term_three_year_active',
        ]);

        $this->adminSession()
            ->post("/admin/hosting-plans/{$planA->id}", [
                'type' => 'vps',
                'name' => 'Plan A',
                'badge' => 'Cheapest',
                'style_class' => 'plan-circle-basic',
                'sort' => '0',
                'is_visible' => '1',
                'return_term' => 'twoYear',
                // The 2 Years pricing section of the form:
                'price_two_year' => '44000',
                'term_two_year_active' => '1',
                'features' => [],
            ])
            ->assertRedirect('/admin/hosting-plans/vps/twoYear');

        $after = $planA->fresh();
        $this->assertSame(44000.0, (float) $after->price_two_year);
        $this->assertTrue((bool) $after->term_two_year_active);

        // Everything else — card fields, other terms — untouched.
        $this->assertSame($before, $after->only([
            'name', 'price_monthly', 'price_yearly', 'price_three_year',
            'term_monthly_active', 'term_yearly_active', 'term_three_year_active',
        ]));
    }

    /** @test */
    public function editing_a_cards_yearly_features_never_touches_monthly_features()
    {
        // VPS Plan A's seed ships without separate yearly rows (2/3-year
        // inherit Monthly at read time). Editing its Yearly features must
        // materialise a Yearly set and leave Monthly byte-identical.
        $planA = HostingPlan::where('type', 'vps')->where('name', 'Plan A')->first();
        $before = $planA->features()->where('billing_term', 'monthly')->orderBy('id')
            ->get(['feature_group', 'name', 'value_monthly', 'feature_key', 'is_available'])
            ->toArray();

        $this->adminSession()
            ->post("/admin/hosting-plans/{$planA->id}", $this->planPayload($planA, [
                // Arrived from the Yearly list section (like the Edit link does).
                'return_term' => 'yearly',
                'features_term' => 'yearly',
                'features' => [
                    ['group' => 'primary', 'line' => '900 GB transactional bandwidth', 'feature_key' => '', 'is_available' => '1'],
                ],
            ]))
            ->assertRedirect('/admin/hosting-plans/vps/yearly');

        $this->assertSame($before, $planA->features()->where('billing_term', 'monthly')->orderBy('id')
            ->get(['feature_group', 'name', 'value_monthly', 'feature_key', 'is_available'])
            ->toArray());

        $yearly = $planA->features()->where('billing_term', 'yearly')->orderBy('id')->get();
        $this->assertSame('900 GB transactional bandwidth', (string) $yearly->firstWhere('feature_group', 'primary')->value_monthly);
        $this->assertSame(1, $yearly->where('feature_group', 'primary')->count());

        // The page keeps serving the untouched Monthly markup while the
        // Yearly template carries the new line.
        $content = $this->get('/hosting/vps')->getContent();
        $this->assertSame(0, substr_count($content, 'data-monthly="900 GB"'));
        $this->assertSame(1, substr_count($content, '>900 GB</strong> transactional bandwidth'));
        // Plan A and Plan B both ship the "200 GB" Monthly line.
        $this->assertSame(2, substr_count($content, 'data-monthly="200 GB"'));
    }

    /** @test */
    public function a_term_without_saved_features_prefills_the_monthly_rows()
    {
        $planA = HostingPlan::where('type', 'vps')->where('name', 'Plan A')->first();

        $content = $this->adminSession()->get("/admin/hosting-plans/{$planA->id}/edit?term=twoYear")
            ->assertOk()
            ->getContent();

        // The section is scoped to the term and says it inherits: the shown
        // rows are Monthly's (the admin saves to give 2 Years its own set).
        $this->assertStringContainsString('Features &mdash; 2 Years', $content);
        $this->assertStringContainsString('no separate', $content);
        $this->assertStringContainsString('name="features_term" value="two_year"', $content);
        $this->assertStringContainsString('200 GB transactional bandwidth', $content);
    }

    /** @test */
    public function public_pages_ship_one_feature_template_per_billing_term()
    {
        $shared = $this->get('/hosting/shared')->getContent();
        // 5 cards x 4 terms.
        $this->assertSame(20, substr_count($shared, '<template class="feature-set"'));

        $vps = $this->get('/hosting/vps')->getContent();
        // 4 cards x 4 terms.
        $this->assertSame(16, substr_count($vps, '<template class="feature-set"'));
        $this->assertStringContainsString('data-term="twoYear"', $vps);
        $this->assertStringContainsString('data-term="threeYear"', $vps);
    }

    /** @test */
    public function the_admin_form_removes_a_features_rows_for_only_that_term()
    {
        // Editing the Monthly set of a card that HAS a yearly set must
        // delete/recreate ONLY the monthly rows.
        $plan = HostingPlan::where('type', 'shared')->where('name', 'Plan A')->first();
        $oldMonthly = $plan->features()->where('billing_term', 'monthly')->orderBy('id')->get('name')->pluck('name')->implode('|');
        $this->assertSame(30, $plan->features()->count());

        $this->adminSession()
            ->post("/admin/hosting-plans/{$plan->id}", $this->planPayload($plan, [
                'features_term' => 'monthly',
                'features' => [
                    ['group' => 'primary', 'line' => '10 GB NVMe storage', 'feature_key' => '', 'is_available' => '1'],
                ],
            ]))
            ->assertRedirect('/admin/hosting-plans/shared/monthly');

        $plan->refresh();
        $this->assertSame(15 + 1, $plan->features()->count()); // yearly set untouched
        $this->assertSame(1, $plan->features()->where('billing_term', 'monthly')->count());
        $this->assertSame(15, $plan->features()->where('billing_term', 'yearly')->count());
    }

    /** @test */
    public function renaming_a_card_for_one_billing_term_leaves_other_terms_alone()
    {
        // The exact admin flow: Shared -> 2 Years -> Edit Plan A -> rename
        // to "Plan X". Only the Shared 2 YEARS view becomes Plan X.
        $planA = HostingPlan::where('type', 'shared')->where('name', 'Plan A')->first();

        $this->adminSession()
            ->post("/admin/hosting-plans/{$planA->id}", $this->planPayload($planA, [
                'return_term' => 'twoYear',
                'features_term' => 'two_year',
                'name' => 'Plan X',
            ]))
            ->assertRedirect('/admin/hosting-plans/shared/twoYear');

        // DB: Monthly keeps the canonical name; only 2 Years got its own.
        $planA->refresh();
        $this->assertSame('Plan A', $planA->name);
        $this->assertSame('Plan X', $planA->name_two_year);
        $this->assertNull($planA->name_yearly);
        $this->assertNull($planA->name_three_year);

        // Public page: initial markup (Monthly) still says Plan A, the
        // 2 Years name travels as data-two-year-name.
        $content = $this->get('/hosting/shared')->getContent();
        $this->assertStringContainsString('>Plan A</h3>', $content);
        $this->assertSame(1, substr_count($content, 'data-two-year-name="Plan X"'));
        $this->assertSame(0, substr_count($content, '>Plan X<'));
        $this->assertSame(0, substr_count($content, 'data-monthly-name="Plan X"'));

        // Admin list: the 2 Years view shows the term name, Monthly doesn't.
        $listTwoYear = $this->adminSession()->get('/admin/hosting-plans/shared/twoYear')->getContent();
        $this->assertStringContainsString('Plan X', $listTwoYear);
        $listMonthly = $this->adminSession()->get('/admin/hosting-plans/shared/monthly')->getContent();
        $this->assertStringContainsString('Plan A', $listMonthly);
        $this->assertStringNotContainsString('Plan X', $listMonthly);

        // Edit pages prefill the term's own name.
        $editTwoYear = $this->adminSession()->get("/admin/hosting-plans/{$planA->id}/edit?term=twoYear")->getContent();
        $this->assertStringContainsString('value="Plan X"', $editTwoYear);
        $editMonthly = $this->adminSession()->get("/admin/hosting-plans/{$planA->id}/edit?term=monthly")->getContent();
        $this->assertStringContainsString('value="Plan A"', $editMonthly);

        // Renaming Monthly now must not overwrite the explicit 2 Years name.
        $this->adminSession()
            ->post("/admin/hosting-plans/{$planA->id}", $this->planPayload($planA, [
                'return_term' => 'monthly',
                'features_term' => 'monthly',
                'name' => 'Plan Z',
            ]))
            ->assertRedirect('/admin/hosting-plans/shared/monthly');

        $planA->refresh();
        $this->assertSame('Plan Z', $planA->name);
        $this->assertSame('Plan X', $planA->name_two_year);
    }

    /** @test */
    public function a_card_without_a_term_name_inherits_the_monthly_name_everywhere()
    {
        // Yearly was never renamed -> the page still offers Plan A's name
        // for Yearly (inheritance, like the feature rows).
        $content = $this->get('/hosting/shared')->getContent();
        $this->assertSame(5, substr_count($content, 'data-yearly-name="Plan '));
    }

    /** @test */
    public function guests_cannot_open_the_admin_plans_ui()
    {
        $this->get('/admin/hosting-plans')->assertRedirect('/admin/login');
        $this->get('/admin/hosting-plans/create/shared')->assertRedirect('/admin/login');
        $this->post('/admin/hosting-plans/1')->assertRedirect('/admin/login');
    }

    /** @test */
    public function admin_can_edit_a_price_and_see_it_on_public_page()
    {
        $plan = HostingPlan::where('type', 'shared')->where('name', 'Plan A')->first();

        $this->adminSession()
            ->post("/admin/hosting-plans/{$plan->id}", $this->planPayload($plan, [
                'price_monthly' => '499',
                'price_yearly' => '4990',
            ]))
            ->assertRedirect();

        $content = $this->get('/hosting/shared')->getContent();
        $this->assertStringContainsString('data-monthly="499"', $content);
        $this->assertStringNotContainsString('data-yearly="3000"', $content); // Plan A's old yearly price is gone
    }

    /** @test */
    public function admin_availability_toggle_flips_the_cross_icon()
    {
        $plan = HostingPlan::where('type', 'vps')->where('name', 'Plan A')->first();
        $content = $this->get('/hosting/vps')->getContent();
        $this->assertSame(0, substr_count($content, 'class="feature-unavailable"'));

        $features = [];
        foreach ($plan->features()->where('billing_term', 'monthly')->orderBy('id')->get() as $feature) {
            $features[] = [
                'group' => $feature->feature_group,
                // One full line per period ("2 Core CPU"), matching the form.
                'line_monthly' => $feature->value_monthly,
                'line_yearly' => $feature->value_yearly,
                'feature_key' => $feature->feature_key,
                // Root access goes off; everything else stays available.
                'is_available' => $feature->name === 'Root access' ? false : true,
            ];
        }

        $this->adminSession()
            ->post("/admin/hosting-plans/{$plan->id}", $this->planPayload($plan, ['features' => $features]))
            ->assertRedirect();

        $content = $this->get('/hosting/vps')->getContent();
        // Only the Root access row is crossed, on this term's own set plus
        // the Monthly template and the inheriting 2/3-year templates.
        $this->assertSame(4, substr_count($content, 'class="feature-unavailable"'));
        $this->assertStringContainsString('>Root access</li>', $content);
    }

    /** @test */
    public function enabling_a_term_on_one_card_makes_it_available_for_the_whole_type()
    {
        // Per-card terms: switch 2 Years ON for ONE vps card only.
        $plan = HostingPlan::where('type', 'vps')->where('name', 'Plan A')->first();
        $this->adminSession()
            ->post("/admin/hosting-plans/{$plan->id}", $this->planPayload($plan, [
                'term_two_year_active' => '1',
            ]))
            ->assertRedirect();

        // The tab becomes available because ONE visible card offers it…
        $content = $this->get('/hosting/vps')->getContent();
        $this->assertStringContainsString('data-period="twoYear" data-soon="false"', $content);
        $this->assertStringContainsString('data-period="threeYear" data-soon="true"', $content);

        // …while the other cards keep flagging it as unavailable themselves.
        // Only the edited card says data-soon-twoyear="false" (mixed state).
        $this->assertSame(1, substr_count($content, 'data-soon-twoyear="false"'));
        $this->assertSame(3, substr_count($content, 'data-soon-twoyear="true"'));
        $this->assertTrue($plan->fresh()->term_two_year_active);
    }

    /** @test */
    public function a_card_without_a_term_renders_coming_soon_while_others_show_prices()
    {
        // Untick Monthly on one shared card only (per-card billing terms).
        $plan = HostingPlan::where('type', 'shared')->where('name', 'Plan E')->first();
        $this->adminSession()
            ->post("/admin/hosting-plans/{$plan->id}", $this->planPayload($plan, [
                'term_monthly_active' => '0',
            ]))
            ->assertRedirect();

        $content = $this->get('/hosting/shared')->getContent();
        // Shared's tabs print one attribute per line — collapse whitespace
        // so tab attributes can be matched together.
        $content = preg_replace('/\s+/', ' ', $content);

        // The Monthly tab stays available — the other four cards offer it.
        $this->assertStringContainsString('data-period="monthly" data-soon="false"', $content);

        // Card E renders its own "Coming soon" price + unavailable state…
        $this->assertStringContainsString('class="plan-card is-coming-soon"', $content);
        // …and only E carries data-soon-monthly="true" (the others keep prices).
        $this->assertSame(1, substr_count($content, 'data-soon-monthly="true"'));
        $this->assertSame(4, substr_count($content, 'data-soon-monthly="false"'));
    }

    /** @test */
    public function admin_can_add_a_new_plan_card_and_hide_it()
    {
        $this->adminSession()->post('/admin/hosting-plans', [
            'type' => 'shared',
            'name' => 'Plan F',
            'description' => 'For huge platforms.',
            'badge' => '',
            'style_class' => 'plan-circle-enterprise',
            'sort' => 6,
            'is_visible' => '1',
            'price_monthly' => '5000',
            'price_yearly' => '50000',
        ])->assertRedirect();

        $this->assertStringContainsString('Plan F', $this->get('/hosting/shared')->getContent());

        // Hide it: card disappears from the public page but stays in the CMS.
        $plan = HostingPlan::where('type', 'shared')->where('name', 'Plan F')->first();
        $this->adminSession()->post("/admin/hosting-plans/{$plan->id}/visibility")->assertRedirect();

        $this->assertStringContainsString('Plan F', $this->get('/hosting/shared')->getContent());
        $this->assertFalse($plan->fresh()->term_monthly_visible);
        $this->assertTrue($plan->fresh()->term_yearly_visible);
    }

    /** @test */
    public function hiding_from_a_term_view_keeps_other_terms_visible()
    {
        // Toggled from the 2 Years list view: the redirect returns to the
        // SAME term view (it used to jump to Monthly, which made the row
        // look untouched).
        $planA = HostingPlan::where('type', 'shared')->where('name', 'Plan A')->first();

        $this->adminSession()
            ->post("/admin/hosting-plans/{$planA->id}/visibility", ['return_term' => 'twoYear'])
            ->assertRedirect('/admin/hosting-plans/shared/twoYear');
        $this->assertFalse($planA->fresh()->term_two_year_visible);
        $this->assertTrue($planA->fresh()->term_monthly_visible);

        // The selected term drops the card, while the monthly term keeps it.
        $shared = $this->get('/hosting/shared')->getContent();
        $this->assertStringContainsString('data-visible-twoyear="false"', $shared);
        $this->assertStringContainsString('data-visible-monthly="true"', $shared);
        $this->assertStringContainsString('>Plan B</h3>', $shared); // other cards unaffected
        $this->assertSame(1, substr_count($this->get('/hosting/vps')->getContent(), '>Plan A</h3>'));
        $session = ['customer' => ['id' => 1, 'uid' => 'CT-000001', 'name' => 'Test Client', 'initials' => 'TC', 'email' => 'c@example.test']];
        $compare = $this->withSession($session)->get('/store/hosting/shared')->getContent();
        $this->assertStringContainsString('data-visible-twoyear="false"', $compare);

        // ...while the admin list keeps it, clearly marked hidden.
        $list = $this->adminSession()->get('/admin/hosting-plans/shared/twoYear')->getContent();
        $this->assertStringContainsString('Plan A', $list);
        $this->assertStringContainsString('is-hidden-card', $list);
        $this->assertStringContainsString('>Hidden<', $list);

        // Show restores it on the public page and keeps the term view.
        $this->adminSession()
            ->post("/admin/hosting-plans/{$planA->id}/visibility", ['return_term' => 'twoYear'])
            ->assertRedirect('/admin/hosting-plans/shared/twoYear');
        $this->assertTrue($planA->fresh()->term_two_year_visible);
        $this->assertStringContainsString('>Plan A</h3>', $this->get('/hosting/shared')->getContent());
    }

    /** @test */
    public function card_save_does_not_restore_a_hidden_term()
    {
        $planA = HostingPlan::where('type', 'shared')->where('name', 'Plan A')->first();
        $this->adminSession()->post("/admin/hosting-plans/{$planA->id}/visibility");
        $this->assertFalse($planA->fresh()->term_monthly_visible);

        // Card-form save WITHOUT the "visible" switch (unchecked box posts
        // nothing) must keep it hidden — not resurrect it.
        $this->adminSession()
            ->post("/admin/hosting-plans/{$planA->id}", $this->planPayload($planA, [
                'is_visible' => null,
                'features_term' => 'monthly',
                'return_term' => 'monthly',
            ]));
        $this->assertFalse($planA->fresh()->term_monthly_visible);

        // The per-term price/state save from the list page must not touch it.
        $this->adminSession()->post('/admin/hosting-plans/shared/monthly/save', [
            'prices' => [$planA->id => '350'],
        ]);
        $this->assertFalse($planA->fresh()->term_monthly_visible);
        $this->assertSame(350.0, (float) $planA->fresh()->price_monthly);

        // The Edit page is the scoped way back.
        $this->adminSession()
            ->post("/admin/hosting-plans/{$planA->id}", $this->planPayload($planA, [
                'features_term' => 'monthly',
                'return_term' => 'monthly',
                'term_monthly_visible' => '1',
            ]));
        $this->assertTrue($planA->fresh()->term_monthly_visible);
    }

    /** @test */
    public function the_list_no_longer_shows_a_per_term_state_column()
    {
        // The per-term Active/"Coming soon" switch lives on the card's Edit
        // page now; the list carries it no more.
        $content = $this->adminSession()->get('/admin/hosting-plans/shared/twoYear')->getContent();

        $this->assertStringContainsString('2 Years price', $content);
        $this->assertStringContainsString('>Visible<', $content); // this term is visible
        $this->assertStringNotContainsString('>Active<', $content);
        $this->assertStringNotContainsString('>Coming soon<', $content);
    }

    /** @test */
    public function admin_can_delete_a_plan_card()
    {
        $plan = HostingPlan::where('type', 'shared')->where('name', 'Plan E')->first();

        $this->adminSession()->post("/admin/hosting-plans/{$plan->id}/delete")->assertRedirect();

        $this->assertDatabaseMissing('hosting_plans', ['id' => $plan->id]);
        $this->assertDatabaseMissing('hosting_plan_features', ['hosting_plan_id' => $plan->id]);
        $this->assertStringNotContainsString('Plan E', $this->get('/hosting/shared')->getContent());
    }

    /** @test */
    public function admin_list_and_form_pages_render()
    {
        $plan = HostingPlan::where('type', 'shared')->where('name', 'Plan A')->first();

        $this->adminSession()->get('/admin/hosting-plans/shared')
            ->assertOk()
            ->assertSee('Plan A')
            ->assertSee('Monthly price');

        // The per-term switcher: 2 Years view shows that term only (editing
        // + saving happens inside the card's Edit page; the price column is
        // the only per-term living in the list — no separate state column).
        $twoYearsContent = $this->adminSession()->get('/admin/hosting-plans/shared/twoYear')
            ->assertOk()
            ->assertSee('2 Years price')
            ->getContent();
        $this->assertStringNotContainsString('form="row-', $twoYearsContent);

        // The edit form opened from that view is scoped to the term: only its
        // price + Active switch, card fields and features.
        $editContent = $this->adminSession()->get("/admin/hosting-plans/{$plan->id}/edit?term=twoYear")
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('2 Years price &amp; billing', $editContent);
        $this->assertStringContainsString('name="price_two_year"', $editContent);
        $this->assertStringContainsString('name="term_two_year_active"', $editContent);
        $this->assertStringNotContainsString('name="price_monthly"', $editContent);
        $this->assertStringNotContainsString('name="term_monthly_active"', $editContent);

        $this->adminSession()->get("/admin/hosting-plans/{$plan->id}/edit")
            ->assertOk()
            ->assertSee('NVMe storage');

        $this->adminSession()->get('/admin/hosting-plans/create/vps')
            ->assertOk()
            ->assertSee('plan-circle-basic');
    }

    /** @test */
    public function admin_saves_one_terms_price_and_flags_inline_per_card()
    {
        $planA = HostingPlan::where('type', 'vps')->where('name', 'Plan A')->first();
        $planB = HostingPlan::where('type', 'vps')->where('name', 'Plan B')->first();

        // Switch 2 Years ON + set its price for Plan A only.
        $this->adminSession()
            ->post('/admin/hosting-plans/vps/twoYear/save', [
                'prices' => [$planA->id => '44000'],
                'terms' => [$planA->id => (string) $planA->id],
            ])
            ->assertRedirect('/admin/hosting-plans/vps/twoYear');

        $this->assertSame(44000.0, (float) $planA->fresh()->price_two_year);
        $this->assertTrue($planA->fresh()->term_two_year_active);
        // Plan B untouched (its own independent 2 Years stays off).
        $this->assertFalse($planB->fresh()->term_two_year_active);
        $this->assertSame(60000.0, (float) $planB->fresh()->price_two_year);

        // Mixed state on the public page: tab available, only card A offers it.
        $content = $this->get('/hosting/vps')->getContent();
        $this->assertStringContainsString('data-period="twoYear" data-soon="false"', $content);
        $this->assertSame(1, substr_count($content, 'data-soon-twoyear="false"'));
        $this->assertSame(3, substr_count($content, 'data-soon-twoyear="true"'));

        // Monthly data must be untouched by a 2 Years save.
        $this->assertSame(2000.0, (float) $planA->fresh()->price_monthly);
    }

    /** @test */
    public function guests_cannot_save_inline_term_prices()
    {
        $plan = HostingPlan::where('type', 'vps')->where('name', 'Plan A')->first();
        $this->post('/admin/hosting-plans/vps/twoYear/save', [
            'prices' => [$plan->id => '1'],
            'terms' => [$plan->id],
        ])->assertRedirect('/admin/login');
        $this->assertFalse($plan->fresh()->term_two_year_active);
    }

    /* ------------------------------------------------------------------ */
    /* Service fallback                                                    */
    /* ------------------------------------------------------------------ */

    /** @test */
    public function service_falls_back_to_legacy_arrays_for_empty_types()
    {
        $service = app(\App\Services\HostingPlanService::class);
        HostingPlan::where('type', 'vps')->delete();

        // No vps rows -> vps falls back to the legacy hardcoded set
        // ( shape-compatible: normalized available/feature_key keys included ).
        $plans = $service->plans('vps');
        $this->assertCount(4, $plans);
        $this->assertSame('Plan A', $plans[0]['name']);
        $this->assertSame(11, count($plans[0]['primary_features']) + count($plans[0]['extra_features']));
        $this->assertArrayHasKey('available', $plans[0]['extra_features'][0]);

        // Shared still has rows -> CMS data keeps being served.
        $this->assertCount(5, $service->plans('shared'));
    }

    /** @test */
    public function orphan_features_are_cleaned_up_with_their_plan()
    {
        $plan = HostingPlan::where('type', 'shared')->where('name', 'Plan B')->first();
        $plan->loadMissing('features');
        $this->assertSame(30, HostingPlanFeature::where('hosting_plan_id', $plan->id)->count());

        $this->adminSession()->post("/admin/hosting-plans/{$plan->id}/delete")->assertRedirect();

        $this->assertSame(0, HostingPlanFeature::where('hosting_plan_id', $plan->id)->count());
    }
}
