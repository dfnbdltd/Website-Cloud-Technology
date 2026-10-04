<?php

namespace Tests\Feature;

use App\User;
use App\Services\MailDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function testResetRequestSendsLinkAndDoesNotRevealUnknownAccounts()
    {
        $user = User::create([
            'name' => 'Reset User',
            'email' => 'reset@example.test',
            'password' => Hash::make('old-password'),
        ]);
        $mailer = new class extends MailDispatcher {
            public $sent = [];

            public function sendPasswordReset(string $to, string $token): void
            {
                $this->sent[] = ['to' => $to, 'token' => $token];
            }
        };
        $this->app->instance(MailDispatcher::class, $mailer);

        $this->get(route('password.request'))->assertOk();
        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', 'Password reset instructions have been sent to reset@example.test. Please check your inbox and spam folder.');

        $this->assertCount(1, $mailer->sent);
        $this->assertSame($user->email, $mailer->sent[0]['to']);
        $this->assertDatabaseHas('password_resets', ['email' => $user->email]);

        $this->post(route('password.email'), ['email' => 'unknown@example.test'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', 'If an account exists for that email, a password reset link will be sent.');

        $this->assertCount(1, $mailer->sent);
    }

    public function testValidResetTokenChangesPasswordAndCanOnlyBeUsedOnce()
    {
        $user = User::create([
            'name' => 'Reset User',
            'email' => 'reset@example.test',
            'password' => Hash::make('old-password'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $token = Password::broker()->createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Choose a new password.');

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('StrongPass1!', $user->fresh()->password));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseMissing('password_resets', ['email' => $user->email]);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'AnotherPass2@',
            'password_confirmation' => 'AnotherPass2@',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('StrongPass1!', $user->fresh()->password));
    }

    public function testResetRejectsInvalidTokenAndMismatchedPasswords()
    {
        $user = User::create([
            'name' => 'Reset User',
            'email' => 'reset@example.test',
            'password' => Hash::make('old-password'),
        ]);

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'StrongPass1!',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors('password');

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function testResetRejectsPasswordsMissingRequiredCharacterTypes()
    {
        $user = User::create([
            'name' => 'Reset User',
            'email' => 'reset@example.test',
            'password' => Hash::make('old-password'),
        ]);
        $token = Password::broker()->createToken($user);

        foreach (['lowercase1!', 'UPPERCASE1!', 'NoNumber!!', 'NoSpecial1', 'Aa1!abc'] as $weakPassword) {
            $this->post(route('password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => $weakPassword,
                'password_confirmation' => $weakPassword,
            ])->assertSessionHasErrors('password');
        }

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
