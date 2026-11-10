<?php

namespace Tests\Feature;

use App\Models\SslPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
    public function public_and_client_pages_read_cms_ssl_content()
    {
        $plan = SslPlan::where('plan_key', 'positive-ssl')->first();
        $plan->update([
            'name' => 'CMS Positive SSL',
            'monthly_price' => 111,
            'term_two_year_active' => false,
        ]);

        $this->get('/services/ssl-certificate')
            ->assertOk()
            ->assertSee('CMS Positive SSL')
            ->assertSee('>111<', false)
            ->assertSee('disabled');

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
        $this->admin()->get('/admin/ssl-plans/create')->assertOk()->assertSee('Add an SSL plan');

        $this->admin()->post('/admin/ssl-plans', [
            'plan_key' => 'custom-ssl',
            'name' => 'Custom SSL',
            'tagline' => 'Custom protection',
            'monthly_price' => '250',
            'renewal_price' => '300',
            'discount_one_year' => '0',
            'discount_two_year' => '7',
            'discount_three_year' => '12',
            'sort' => '20',
            'is_visible' => '1',
            'term_one_year_active' => '1',
            'term_one_year_visible' => '1',
            'features' => [
                ['text' => 'Domain validation', 'is_available' => '1'],
                ['text' => 'Wildcard coverage'],
            ],
        ])->assertRedirect('/admin/ssl-plans');

        $plan = SslPlan::where('plan_key', 'custom-ssl')->firstOrFail();
        $this->admin()->get("/admin/ssl-plans/{$plan->id}/edit")->assertOk()->assertSee('Custom SSL');
        $this->assertSame(2, $plan->features()->count());
        $this->assertFalse($plan->term_two_year_active);
        $this->assertFalse($plan->term_two_year_visible);
        $this->assertTrue($plan->features()->where('feature', 'Domain validation')->first()->is_available);
        $this->assertFalse($plan->features()->where('feature', 'Wildcard coverage')->first()->is_available);

        $this->admin()->post("/admin/ssl-plans/{$plan->id}", [
            'plan_key' => 'custom-ssl',
            'name' => 'Updated SSL',
            'tagline' => 'Updated protection',
            'monthly_price' => '275',
            'renewal_price' => '325',
            'discount_one_year' => '1',
            'discount_two_year' => '8',
            'discount_three_year' => '13',
            'sort' => '3',
            'is_visible' => '1',
            'term_one_year_active' => '1',
            'term_two_year_active' => '1',
            'term_two_year_visible' => '1',
            'features' => [['text' => 'Updated feature', 'is_available' => '1']],
        ])->assertRedirect('/admin/ssl-plans');

        $updated = $plan->fresh();
        $this->assertSame('Updated SSL', $updated->name);
        $this->assertSame(275.0, $updated->monthly_price);
        $this->assertSame(325.0, $updated->renewal_price);
        $this->assertSame(8.0, $updated->discount_two_year);
        $this->assertTrue($updated->term_two_year_active);
        $this->assertTrue($updated->term_two_year_visible);
        $this->assertSame(1, $updated->features()->count());
        $this->assertSame('Updated feature', $updated->features()->first()->feature);

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
}
