<?php

namespace Tests\Feature;

use App\Models\SslPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * SSL plan CMS.
 *
 * Every plan offers all three terms (1, 2, 3 years); the only per-term setting
 * is a discount percentage. The per-term "active"/"visible" flags were removed
 * from the schema, so these tests also pin that removal.
 */
class SslPlanCmsTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['admin' => ['email' => 'admin@example.test']]);
    }

    /** @test */
    public function migration_seeds_ssl_plans_from_config()
    {
        $this->assertSame(4, SslPlan::count());
        $this->assertSame('positive-ssl', SslPlan::orderBy('sort')->first()->plan_key);
        $this->assertSame(8, SslPlan::where('plan_key', 'positive-ssl')->first()->features()->count());
    }

    /** @test */
    public function term_flag_columns_were_removed_from_the_schema()
    {
        foreach ([
            'term_one_year_active',
            'term_two_year_active',
            'term_three_year_active',
            'term_one_year_visible',
            'term_two_year_visible',
            'term_three_year_visible',
        ] as $column) {
            $this->assertFalse(
                Schema::hasColumn('ssl_plans', $column),
                "Expected column [{$column}] to be dropped."
            );
        }

        // The columns that carry real meaning must survive the rebuild.
        foreach ([
            'id', 'plan_key', 'name', 'tagline', 'badge', 'monthly_price',
            'renewal_price', 'discount_one_year', 'discount_two_year',
            'discount_three_year', 'is_visible', 'sort',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('ssl_plans', $column),
                "Expected column [{$column}] to still exist."
            );
        }
    }

    /** @test */
    public function public_and_client_pages_read_cms_ssl_content()
    {
        $plan = SslPlan::where('plan_key', 'positive-ssl')->first();
        $plan->update([
            'name' => 'CMS Positive SSL',
            'monthly_price' => 111,
        ]);

        $this->get('/services/ssl-certificate')
            ->assertOk()
            ->assertSee('CMS Positive SSL')
            // Yearly: 111/mo x 12 = 1,332 for the default 1-year term.
            ->assertSee('>1,332<', false)
            // All three terms are always offered.
            ->assertSee('1 Year')
            ->assertSee('2 Years')
            ->assertSee('3 Years')
            // No option carries the disabled attribute any more. Asserted on
            // the rendered attribute, because the bare word also appears in
            // the page's JavaScript (option.disabled).
            ->assertDontSee(' disabled>', false)
            ->assertDontSee(' disabled ', false);

        $this->withSession(['customer' => ['id' => 1, 'uid' => 'CT-000001', 'name' => 'Test Client', 'initials' => 'TC', 'email' => 'c@example.test']])
            ->get('/client/coming-soon/ssl-certificate')
            ->assertOk()
            ->assertSee('CMS Positive SSL');
    }

    /** @test */
    public function ssl_cms_requires_admin_and_supports_create_update_visibility_and_delete()
    {
        $this->get('/admin/ssl-plans')->assertRedirect('/admin/login');
        $this->admin()->get('/admin/ssl-plans')->assertOk()->assertSee('SSL plans');
        $this->admin()->get('/admin/ssl-plans/create')->assertOk()->assertSee('SSL plan');

        $this->admin()->post('/admin/ssl-plans', [
            'plan_key' => 'custom-ssl',
            'name' => 'Custom SSL',
            'tagline' => 'Custom protection',
            'resellerclub_plan_id' => '300',
            'sort' => '20',
            'is_visible' => '1',
            'features' => [
                ['text' => 'Domain validation'],
                ['text' => 'Wildcard coverage'],
            ],
        ])->assertRedirect('/admin/ssl-plans');

        $plan = SslPlan::where('plan_key', 'custom-ssl')->firstOrFail();
        // Admin can no longer type a price: a new plan starts with the DB
        // default (0) and is hidden from customers until ResellerClub prices it.
        $this->assertSame(0.0, $plan->monthly_price);
        $this->get('/services/ssl-certificate')->assertDontSee('Custom SSL');
        // Stand-in for a price saved earlier / filled in by the API fallback.
        $plan->update(['monthly_price' => 275, 'renewal_price' => 325, 'discount_two_year' => 8]);
        $this->admin()->get("/admin/ssl-plans/{$plan->id}/edit")->assertOk()->assertSee('Custom SSL');

        // Blank feature rows are dropped; every kept feature is available.
        $this->assertSame(2, $plan->features()->count());
        $this->assertSame(2, $plan->features()->where('is_available', true)->count());

        $this->admin()->post("/admin/ssl-plans/{$plan->id}", [
            'plan_key' => 'custom-ssl',
            'name' => 'Updated SSL',
            'tagline' => 'Updated protection',
            'resellerclub_plan_id' => '300',
            'monthly_price' => '1',
            'discount_two_year' => '99',
            'sort' => '3',
            'is_visible' => '1',
            'features' => [['text' => 'Updated feature']],
        ])->assertRedirect('/admin/ssl-plans');

        $updated = $plan->fresh();
        $this->assertSame('Updated SSL', $updated->name);
        $this->assertSame(275.0, $updated->monthly_price);
        $this->assertSame(325.0, $updated->renewal_price);
        $this->assertSame(8.0, $updated->discount_two_year);
        $this->assertSame(1, $updated->features()->count());
        $this->assertSame('Updated feature', $updated->features()->first()->feature);

        // Hiding removes the plan from the public page; showing brings it back.
        $this->admin()->post("/admin/ssl-plans/{$plan->id}/visibility")->assertRedirect('/admin/ssl-plans');
        $this->assertFalse($plan->fresh()->is_visible);
        $this->get('/services/ssl-certificate')->assertDontSee('Updated SSL');

        $this->admin()->post("/admin/ssl-plans/{$plan->id}/visibility")->assertRedirect('/admin/ssl-plans');
        $this->assertTrue($plan->fresh()->is_visible);
        $this->get('/services/ssl-certificate')->assertSee('Updated SSL');

        $this->admin()->post("/admin/ssl-plans/{$plan->id}/delete")->assertRedirect('/admin/ssl-plans');
        $this->assertDatabaseMissing('ssl_plans', ['id' => $plan->id]);
        $this->assertDatabaseMissing('ssl_plan_features', ['ssl_plan_id' => $plan->id]);
    }

    /** @test */
    public function term_discounts_produce_the_expected_bdt_totals()
    {
        $plan = SslPlan::where('plan_key', 'positive-ssl')->first();
        $plan->update([
            'monthly_price' => 100,
            'discount_one_year' => 0,
            'discount_two_year' => 10,
            'discount_three_year' => 20,
        ]);

        $resolved = app(\App\Services\SslPlanService::class)->plans();
        $row = collect($resolved)->firstWhere('key', 'positive-ssl');

        // 100/mo → 1y 1200, 2y 2400 −10% = 2160, 3y 3600 −20% = 2880
        $this->assertSame(1200.0, (float) $row['terms'][1]['total']);
        $this->assertSame(2160.0, (float) $row['terms'][2]['total']);
        $this->assertSame(2880.0, (float) $row['terms'][3]['total']);

        // 1 year is what the card quotes by default.
        $this->assertSame(1, $row['default']['years']);

        // Yearly quoting, from the same term totals: 1,200 / 2,160 / 2,880
        // over 1 / 2 / 3 years. The renewal column is a per-month figure, so
        // the yearly one is that × 12.
        $this->assertSame(1200.0, (float) $row['per_year']);
        $this->assertSame(1200.0, (float) $row['default']['per_year']);
        $this->assertSame(1080.0, (float) $row['terms'][2]['per_year']);
        $this->assertSame(960.0, (float) $row['terms'][3]['per_year']);
        $this->assertSame((float) $row['renewal'] * 12, (float) $row['renewal_year']);

        $this->get('/services/ssl-certificate')
            ->assertOk()
            ->assertSee('data-ssl-amount>1,200<', false)
            ->assertSee('Renews at ৳2,388/year')
            ->assertDontSee('/mo');
    }
}
