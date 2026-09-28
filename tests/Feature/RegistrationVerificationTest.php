<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;

class RegistrationVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function testRegistrationSendsVerificationEmailAndBlocksUnverifiedLogin()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'newuser@example.com',
            'phone' => '01712345678',
            'address1' => 'Road 1',
            'city' => 'Dhaka',
            'country' => 'Bangladesh',
            'postcode' => '1200',
            'currency' => 'BDT',
            'security_question' => 'What is your favourite sports team?',
            'security_answer' => 'Football',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
        $response->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);

        // Verification email was sent
        Notification::assertSentTo($user, VerifyEmail::class);

        // Unverified user cannot see the dashboard
        $this->actingAs($user);
        $this->get('/dashboard')->assertRedirect(route('verification.notice'));

        // Resend works
        $this->post(route('verification.resend'))->assertSessionHas('resent');

        // Verify the email via the signed URL
        $verificationUrl = $user->sendEmailVerificationNotification() ? null : null;
        $this->get($this->verificationLink($user))->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->email_verified_at);

        // Verified user can now access the dashboard
        $this->get('/dashboard')->assertOk();
    }

    private function verificationLink(User $user)
    {
        $hash = sha1($user->getEmailForVerification());

        return \URL::temporarySignedRoute(
            'verification.verify',
            \Illuminate\Support\Carbon::now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => $hash]
        );
    }
}
