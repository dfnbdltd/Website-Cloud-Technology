<?php

namespace Tests\Feature;

use App\Services\ResellerClubClient;
use Tests\TestCase;

class LandingAdminLoginTest extends TestCase
{
    public function testLandingPageProvidesAnAdminLoginButton()
    {
        $catalog = \Mockery::mock(ResellerClubClient::class);
        $catalog->shouldReceive('catalog')->once()->andReturn([]);
        $this->app->instance(ResellerClubClient::class, $catalog);

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Admin login')
            ->assertSee('href="' . route('admin.login') . '"', false);
    }
}
