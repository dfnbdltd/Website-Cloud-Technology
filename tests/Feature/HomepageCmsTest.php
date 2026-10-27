<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\ResellerClubClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageCmsTest extends TestCase
{
    use RefreshDatabase;

    public function testHomepageRendersCmsCarouselCopy()
    {
        $catalog = \Mockery::mock(ResellerClubClient::class);
        $catalog->shouldReceive('catalog')->once()->andReturn([]);
        $this->app->instance(ResellerClubClient::class, $catalog);

        Setting::put([
            'hero_title' => 'Find your brand',
            'hero_highlight' => 'online today.',
            'hero_text' => 'Search for a domain and launch your next idea.',
            'ssl_title' => 'Protect every visit.',
            'ssl_highlight' => 'Build trust instantly.',
            'ssl_text' => 'Simple SSL protection for growing businesses.',
            'communications_title' => 'Grow your business',
            'communications_highlight' => 'with reliable hosting.',
            'communications_text' => 'Fast hosting and professional email in one place.',
        ]);

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Find your brand')
            ->assertSee('Protect every visit.')
            ->assertSee('Grow your business')
            ->assertSee('Fast hosting and professional email in one place.');
    }

    public function testHomepageUsesCarouselDefaultsWhenValuesAreMissing()
    {
        $catalog = \Mockery::mock(ResellerClubClient::class);
        $catalog->shouldReceive('catalog')->once()->andReturn([]);
        $this->app->instance(ResellerClubClient::class, $catalog);

        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Your Perfect Domain,')
            ->assertSee('Secure your site.')
            ->assertSee('Build your presence');
    }
}
