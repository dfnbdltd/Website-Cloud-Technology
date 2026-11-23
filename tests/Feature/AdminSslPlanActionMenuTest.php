<?php

namespace Tests\Feature;

use App\Models\SslPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /admin/ssl-plans: each plan has ONE "Action" dropdown (Edit / Hide-Show / Delete)
 * instead of three always-visible buttons. The dropdown itself is JavaScript; these
 * tests pin the markup it depends on and that the real endpoints are still wired.
 */
class AdminSslPlanActionMenuTest extends TestCase
{
    use RefreshDatabase;

    private function admin()
    {
        return $this->withSession(['admin' => ['email' => 'admin@example.test']]);
    }

    public function test_every_plan_row_has_one_action_dropdown_with_edit_hide_and_delete()
    {
        $plan = SslPlan::orderBy('sort')->first();

        $response = $this->admin()->get('/admin/ssl-plans')->assertOk();
        $html = $response->getContent();

        $this->assertSame(SslPlan::count(), substr_count($html, 'data-ssl-act data-edit-url='));
        $this->assertSame(SslPlan::count(), substr_count($html, '<button type="button" class="ssl-act-main" data-act-main>Action</button>'));

        $response
            ->assertSee('data-choose="edit">Edit</button>', false)
            ->assertSee('data-choose="visibility">Hide</button>', false)
            ->assertSee('data-choose="delete">Delete</button>', false)
            ->assertSee('data-edit-url="' . route('admin.ssl.plans.edit', $plan) . '"', false)
            ->assertSee('action="' . route('admin.ssl.plans.visibility', $plan) . '"', false)
            ->assertSee('action="' . route('admin.ssl.plans.delete', $plan) . '"', false);
    }

    public function test_the_old_always_visible_buttons_are_gone()
    {
        $this->admin()->get('/admin/ssl-plans')
            ->assertOk()
            ->assertDontSee('<button class="danger"', false)
            ->assertDontSee('<button type="submit">Hide</button>', false)
            ->assertDontSee('<a href="' . route('admin.ssl.plans.edit', SslPlan::first()) . '">Edit</a>', false);
    }

    public function test_a_hidden_plan_offers_show_instead_of_hide()
    {
        SslPlan::query()->update(['is_visible' => false]);

        $this->admin()->get('/admin/ssl-plans')
            ->assertOk()
            ->assertSee('data-choose="visibility">Show</button>', false)
            ->assertDontSee('data-choose="visibility">Hide</button>', false);
    }

    public function test_the_actions_still_work_through_their_endpoints()
    {
        $plan = SslPlan::orderBy('sort')->first();
        $this->assertTrue((bool) $plan->is_visible);

        $this->admin()->post(route('admin.ssl.plans.visibility', $plan))->assertRedirect('/admin/ssl-plans');
        $this->assertFalse((bool) $plan->fresh()->is_visible);

        $this->admin()->get(route('admin.ssl.plans.edit', $plan))->assertOk();

        $this->admin()->post(route('admin.ssl.plans.delete', $plan))->assertRedirect('/admin/ssl-plans');
        $this->assertNull(SslPlan::find($plan->id));
    }
}
