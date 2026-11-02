<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\SupportTicket;
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

    /**
     * The page the guest asked for travels with them, so signing in resumes it
     * instead of dropping them on the dashboard (see PostAuthRedirectTest).
     */
    public function testClientAreaRedirectsGuestsToLogin()
    {
        foreach (['/dashboard', '/mystore', '/wallet', '/invoices', '/profile/settings'] as $uri) {
            $this->get($uri)->assertRedirect(route('login', ['return_to' => $uri]));
        }
    }

    public function testSupportPageIsPublic()
    {
        $this->get('/support')
            ->assertOk()
            ->assertSee('How can we help?')
            ->assertDontSee('domain-sidebar');
    }

    public function testCustomerCanOpenOnlyTheirOwnSupportTicket()
    {
        $ticket = SupportTicket::create([
            'user_id' => 1,
            'subject' => 'DNS issue',
            'message' => 'The DNS record needs attention.',
            'priority' => 'normal',
            'status' => 'open',
        ]);

        $session = ['customer' => [
            'id' => 1,
            'uid' => 'CT-1',
            'name' => 'Test Customer',
            'initials' => 'TC',
            'email' => 'customer@example.test',
            'balance' => 0,
            'logo' => null,
        ]];

        $this->withSession($session)
            ->get(route('support.ticket', $ticket))
            ->assertOk()
            ->assertSee('DNS issue');

        $otherTicket = SupportTicket::create([
            'user_id' => 2,
            'subject' => 'Private issue',
            'message' => 'This must not be visible.',
            'priority' => 'high',
            'status' => 'open',
        ]);

        $this->withSession($session)
            ->get(route('support.ticket', $otherTicket))
            ->assertNotFound();
    }

    public function testLogoutReturnsCustomerToCenteredPublicDomainLayout()
    {
        $session = ['customer' => [
            'id' => 1,
            'uid' => 'CT-1',
            'name' => 'Test Customer',
            'initials' => 'TC',
            'email' => 'customer@example.test',
            'balance' => 0,
            'logo' => null,
        ]];

        $this->withSession($session)->post(route('logout'))
            ->assertRedirect(route('landing'));

        $this->get(route('domains.index'))
            ->assertOk()
            ->assertDontSee('domain-sidebar')
            // The shared public navbar (same one as the landing page) marks
            // the Domains item as the current page.
            ->assertSee('class="selected" href="' . route('domains.index') . '">Domains', false)
            ->assertSee('site-account');
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
