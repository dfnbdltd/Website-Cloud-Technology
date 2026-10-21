<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use App\Models\EmailVerificationToken;
use App\Rules\StrongPassword;
use App\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() || session('customer')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    public function sendPasswordResetLink(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email']]);
        try {
            $status = Password::sendResetLink($credentials);
        } catch (\Throwable $e) {
            Log::error('Could not send password reset email', ['error' => $e->getMessage()]);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'We could not send a reset email right now. Please try again later.']);
        }

        if ($status === Password::RESET_LINK_SENT) {
            return redirect()->route('password.request')->with(
                'status',
                'Password reset instructions have been sent to ' . $credentials['email'] . '. Please check your inbox and spam folder.'
            );
        }

        if ($status === Password::RESET_THROTTLED) {
            return redirect()->route('password.request')->with(
                'status',
                'A reset link was recently sent. Please check your inbox and spam folder, or wait before requesting another.'
            );
        }

        return redirect()->route('password.request')
            ->with('status', 'If an account exists for that email, a password reset link will be sent.');
    }

    public function showResetPasswordForm(Request $request, $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', old('email')),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $credentials = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', new StrongPassword],
        ]);

        $status = Password::reset($credentials, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Your password has been reset. You can now sign in.');
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => trans($status)]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ]);
        $remember = $request->boolean('remember');

        if (!Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $remember)) {
            $demoEmail = env('DEMO_USER_EMAIL');
            $demoPassword = env('DEMO_USER_PASSWORD');
            if ($credentials['email'] !== $demoEmail || $credentials['password'] !== $demoPassword) {
                return back()->withErrors(['email' => 'Those credentials do not match our records.'])->withInput();
            }

            $user = User::firstOrCreate(
                ['email' => $demoEmail],
                ['name' => 'M Hafuz', 'password' => Hash::make($demoPassword)]
            );
            if (!$user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
            Auth::login($user, $remember);
        }

        $user = Auth::user();
        if (!$user->email_verified_at) {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Please verify your email address first — check your inbox for the verification link.'])
                ->with('unverified_email', $user->email)
                ->withInput();
        }

        $request->session()->regenerate();
        if (!$user->customer_uid) {
            $user->forceFill(['customer_uid' => User::generateUid()])->save();
        }
        $request->session()->put('customer', self::customerSession($user));

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            // The sign-up form collects one "Full Name" field. first_name/last_name
            // are still accepted for backwards compatibility with older clients.
            'full_name' => ['nullable', 'string', 'max:120', 'required_without:first_name'],
            'first_name' => ['nullable', 'string', 'max:120', 'required_without:full_name'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_dial' => ['nullable', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:32'],
            'company_name' => ['nullable', 'string', 'max:190'],
            'address1' => ['required', 'string', 'max:190'],
            // address2 and state are intentionally not collected on the sign-up form.
            'city' => ['required', 'string', 'max:120'],
            'country' => ['required', 'string', 'max:120'],
            'postcode' => ['required', 'string', 'max:30'],
            'currency' => ['required', 'string', 'max:10'],
            'found_us' => ['nullable', 'string', 'max:190'],
            'security_question' => ['required', 'string', 'max:255'],
            'security_answer' => ['required', 'string', 'max:255'],
            'newsletter' => ['nullable', 'boolean'],
            'password' => ['required', 'string', 'confirmed', new StrongPassword],
        ]);

        list($firstName, $lastName, $fullName) = $this->resolveCustomerName($data);

        $user = User::create([
            'name' => $fullName,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => trim(($request->input('phone_dial', '') . ' ' . $data['phone'])),
            'company_name' => $data['company_name'] ?? null,
            'address1' => $data['address1'],
            'city' => $data['city'],
            'country' => $data['country'],
            'postcode' => $data['postcode'],
            'currency' => $data['currency'],
            'found_us' => $data['found_us'] ?? null,
            'security_question' => $data['security_question'],
            'security_answer' => $data['security_answer'],
            'newsletter' => $request->boolean('newsletter'),
        ]);
        $user->forceFill(['customer_uid' => User::generateUid()])->save();
        $this->sendVerificationEmail($user);

        return redirect()->route('verification.notice')->with('verify_email', $user->email);
    }

    /**
     * Split the single "Full Name" input into the first_name/last_name columns
     * used by invoices, reports and the profile screen.
     *
     * @return array{0: string, 1: string|null, 2: string}
     */
    private function resolveCustomerName(array $data)
    {
        if (!empty($data['full_name'])) {
            $fullName = trim(preg_replace('/\s+/u', ' ', $data['full_name']));
        } else {
            $fullName = trim(trim($data['first_name']) . ' ' . trim($data['last_name'] ?? ''));
        }

        $parts = preg_split('/\s+/u', $fullName, 2, PREG_SPLIT_NO_EMPTY);
        $firstName = $parts[0] ?? '';
        $lastName = $parts[1] ?? null;

        return [$firstName, $lastName, $fullName];
    }

    /**
     * "Check your inbox" page shown after sign-up, with a resend form.
     */
    public function verificationNotice(Request $request)
    {
        return view('auth.verify', ['email' => session('verify_email', old('email'))]);
    }

    public function resendVerification(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $user = User::where('email', $data['email'])->first();

        // Same answer whether or not the address exists, so emails can't be probed.
        if ($user && !$user->email_verified_at) {
            $this->sendVerificationEmail($user);
        }

        return redirect()
            ->route('verification.notice')
            ->with('verify_email', $data['email'])
            ->with('success', 'If that account is waiting for verification, a new link is on its way.');
    }

    /**
     * Guide section 5: consume the one-time token from /verify-email/{token}.
     * Marks the address verified, burns the token and mails login details.
     */
    public function verifyEmail($token)
    {
        try {
            $record = EmailVerificationToken::where('token', $token)->whereNull('used_at')->first();
        } catch (\Throwable $e) {
            // database/email_system.sql has not been run yet.
            return redirect()->route('login')->withErrors(['email' => 'Verification is not available yet. Please contact support to activate your account.']);
        }

        if (!$record || $record->expires_at->isPast()) {
            return redirect()->route('login')->withErrors(['email' => 'Verification link is invalid or expired.']);
        }

        $user = User::find($record->user_id);
        if (!$user) {
            return redirect()->route('login')->withErrors(['email' => 'Verification link is invalid or expired.']);
        }

        if (!$user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }
        $record->forceFill(['used_at' => now()])->save();

        // The "check your inbox" page promises login details after activation.
        SendEmailJob::dispatch(0, 'account_ready', $user->email, [
            'name' => $user->first_name ?: $user->name,
            'email' => $user->email,
            'customer_id' => $user->customer_uid,
            'login_url' => route('login'),
        ]);

        return redirect()->route('login')->with('success', 'Email verified successfully.');
    }

    /**
     * Guide section 5: store a one-time token, then queue the mail (section 7).
     * Never breaks registration if database/email_system.sql has not been run.
     */
    private function sendVerificationEmail(User $user)
    {
        try {
            if (!Schema::hasTable('email_verification_tokens') || !Schema::hasTable('email_templates')) {
                Log::warning('Email system tables missing — run database/email_system.sql to send verification mail.');

                return;
            }

            $token = Str::random(64);

            EmailVerificationToken::create([
                'user_id' => $user->id,
                'token' => $token,
                'expires_at' => now()->addHour(),
            ]);

            SendEmailJob::dispatch(0, 'email_verification', $user->email, [
                'name' => $user->first_name ?: $user->name,
                'link' => url('/verify-email/' . $token),
            ]);
        } catch (\Throwable $e) {
            Log::error('Could not queue verification email', ['error' => $e->getMessage()]);
        }
    }


    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }

    /**
     * Session snapshot shared by login and registration so every client-area
     * page shows the same unique ID, balance and logo without extra queries.
     */
    public static function customerSession(User $user)
    {
        return [
            'id' => $user->id,
            'uid' => $user->customer_uid,
            'name' => $user->name,
            'initials' => User::initials($user->name),
            'email' => $user->email,
            'phone' => (string) $user->phone,
            'balance' => (float) $user->balance,
            'logo' => $user->logo ? asset('storage/' . $user->logo) : null,
            'plan' => 'BDIX Premium',
        ];
    }
}