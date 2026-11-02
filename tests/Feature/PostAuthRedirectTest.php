<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * A guest who is buying something must come back to the page they left
 * (checkout) after signing in or registering, while a plain sign-in still
 * starts in the dashboard.
 */
class PostAuthRedirectTest extends TestCase
{
    use RefreshDatabase;

    private const CART = [
        'domain_cart' => [
            'wallet-test.ctbd.test' => [
                'domain' => 'wallet-test.ctbd.test',
                'price' => 1.0,
                'purchase_price' => 1.0,
                'first_year_price' => 1.0,
                'renewal_price' => 1.0,
                'years' => 1,
            ],
        ],
    ];

    public function testCheckoutSendsGuestsToLoginWithTheCheckoutAsDestination()
    {
        $response = $this->withSession(self::CART)->get('/domains/checkout');

        $response->assertOk();
        $response->assertSee(route('login', ['return_to' => '/domains/checkout']), false);
        $response->assertSee(route('register', ['return_to' => '/domains/checkout']), false);
    }

    public function testGuestBuyerReturnsToCheckoutAfterSigningIn()
    {
        $this->makeVerifiedUser();

        $this->withSession(self::CART)
            ->get('/login?return_to=%2Fdomains%2Fcheckout')
            ->assertOk();

        $this->post('/login', [
            'email' => 'buyer@example.test',
            'password' => 'StrongPass1!',
        ])->assertRedirect('/domains/checkout');
    }

    public function testPlainSignInStillStartsInTheDashboard()
    {
        $this->makeVerifiedUser();

        $this->get('/login')->assertOk();

        $this->post('/login', [
            'email' => 'buyer@example.test',
            'password' => 'StrongPass1!',
        ])->assertRedirect(route('dashboard'));
    }

    /** The header "Log in" button carries no destination, but a waiting cart does. */
    public function testGuestWithACartResumesCheckoutEvenWithoutAnExplicitDestination()
    {
        $this->makeVerifiedUser();

        $this->withSession(self::CART)->get('/login')->assertOk();

        $this->post('/login', [
            'email' => 'buyer@example.test',
            'password' => 'StrongPass1!',
        ])->assertRedirect('/domains/checkout');
    }

    /** A client-area page the guest asked for is resumed after signing in. */
    public function testClientAreaPageIsResumedAfterSigningIn()
    {
        $this->makeVerifiedUser();

        $this->get('/wallet')
            ->assertRedirect(route('login', ['return_to' => '/wallet']));

        $this->post('/login', [
            'email' => 'buyer@example.test',
            'password' => 'StrongPass1!',
        ])->assertRedirect('/wallet');
    }

    public function testDestinationCannotPointOffSite()
    {
        $this->makeVerifiedUser();

        $this->get('/login?return_to=https%3A%2F%2Fevil.test%2Fsteal')->assertOk();
        $this->assertNull(session('url.intended'));
    }

    public function testProtocolRelativeDestinationIsRejected()
    {
        $this->makeVerifiedUser();

        $this->get('/login?return_to=%2F%2Fevil.test%2Fsteal')->assertOk();
        $this->assertNull(session('url.intended'));

        $this->post('/login', [
            'email' => 'buyer@example.test',
            'password' => 'StrongPass1!',
        ])->assertRedirect(route('dashboard'));
    }

    /** Switching to "Create account" keeps the destination for after verification. */
    public function testSignUpScreenKeepsTheDestinationForTheEmailVerificationStep()
    {
        $this->withSession(self::CART)
            ->get('/login?return_to=%2Fdomains%2Fcheckout')
            ->assertOk()
            ->assertSee(route('register', ['return_to' => '/domains/checkout']), false);

        $this->get('/register?return_to=%2Fdomains%2Fcheckout')
            ->assertOk()
            ->assertSee(route('login', ['return_to' => '/domains/checkout']), false);

        // Verified accounts continue to the stored destination, not the dashboard.
        $this->assertSame('/domains/checkout', session('url.intended'));
    }

    /** The green-tick page continues to the checkout the buyer started from. */
    public function testVerifiedNoticeContinuesToTheStoredDestination()
    {
        $this->withSession([
            'customer' => ['id' => 1, 'name' => 'Buyer Check', 'email' => 'buyer@example.test'],
            'verified_destination' => '/domains/checkout',
        ])->get('/email/verified')
            ->assertOk()
            ->assertSee('content="3; url=/domains/checkout"', false);
    }

    public function testPlainSignUpStartsInTheDashboardAfterVerification()
    {
        $this->get('/login?return_to=%2Fdomains%2Fcheckout')->assertOk();
        $this->assertSame('/domains/checkout', session('url.intended'));

        // Opening the sign-up screen on its own drops the older destination.
        $this->get('/register')->assertOk();
        $this->assertNull(session('url.intended'));
    }

    private function makeVerifiedUser(): User
    {
        $user = new User([
            'name' => 'Buyer Check',
            'email' => 'buyer@example.test',
            'password' => Hash::make('StrongPass1!'),
        ]);
        $user->forceFill([
            'customer_uid' => User::generateUid(),
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }
}
