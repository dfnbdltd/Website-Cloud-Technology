<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guests must never see the client-area sidebar, and every client-area or
 * admin endpoint has to bounce them first.
 */
class GuestAccessTest extends TestCase
{
    use RefreshDatabase;

    /** Landing-page menus (Blog, Offers, Web Hosting, ...) land here as guests. */
    public function testComingSoonPagesArePublicButShowNoClientSidebar()
    {
        foreach (['/coming-soon/blog', '/coming-soon/web-hosting'] as $uri) {
            $response = $this->get($uri);

            $response->assertStatus(200);
            $response->assertDontSee('domain-sidebar');
            $response->assertDontSee('Client area /');
            $response->assertSee('site-header');
        }
    }

    /** Signed-in customers keep the client-area layout on the same page. */
    public function testComingSoonPageKeepsSidebarForCustomers()
    {
        $response = $this->withSession(['customer' => [
            'id' => 1,
            'uid' => 'CT-1',
            'name' => 'Test Customer',
            'initials' => 'TC',
            'email' => 'customer@example.test',
            'phone' => '01700000000',
            'balance' => 0,
            'logo' => null,
            'plan' => 'BDIX Premium',
        ]])->get('/coming-soon/blog');

        $response->assertStatus(200);
        $response->assertSee('domain-sidebar');
    }

    public function testClientAreaRedirectsGuestsToLogin()
    {
        foreach (['/dashboard', '/mystore', '/wallet', '/invoices', '/support', '/domains', '/domains/cart', '/profile/settings'] as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
    }

    public function testAdminChatEndpointsRejectGuests()
    {
        $this->getJson('/admin/chat-poll?session=abc')->assertStatus(401);

        $this->postJson('/admin/chat-reply', ['session' => 'abc', 'message' => 'hi'])->assertStatus(401);
    }

    public function testAdminChatEndpointsAcceptAdminSession()
    {
        $response = $this->withSession(['admin' => ['email' => 'admin@example.test']])
            ->getJson('/admin/chat-poll?session=abc');

        $response->assertStatus(200);
        $response->assertJsonStructure(['messages']);
    }
}
