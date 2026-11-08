<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use App\Models\EmailVerificationToken;
use App\Rules\StrongPassword;
use App\Support\AuthIntent;
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
    private const VERIFICATION_RESEND_COOLDOWN = 300;

    private $httpClient;

    public function __construct($httpClient = null)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Sign-in screen. The checkout page and the client-area guard send guests
     * here with ?return_to=..., so a buyer can finish the order they started.
     * Opening the screen on its own (the header "Log in" button) sends them to
     * the dashboard as before.
     */
    public function showLogin(Request $request)
    {
        if (Auth::check() || session('customer')) {
            return redirect()->route('dashboard');
        }

        AuthIntent::replace($request->session(), $this->intendedDestination($request));

        return view('auth.login', ['returnTo' => AuthIntent::peek($request->session())]);
    }

    /**
     * Where the next successful sign-in should go.
     *
     * 1. the return_to the previous screen asked for (checkout, a client-area
     *    page), else
     * 2. the checkout, when the guest is carrying a cart — that is where a
     *    buyer is headed even if they clicked the header "Log in" button, else
     * 3. null, so a plain sign-in still starts in the dashboard.
     *
     * @return string|null
     */
    private function intendedDestination(Request $request)
    {
        $returnTo = AuthIntent::sanitize($request->query('return_to'));
        if ($returnTo !== null) {
            return $returnTo;
        }

        if (count((array) $request->session()->get('domain_cart', [])) > 0) {
            return route('domains.checkout', [], false);
        }

        return null;
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

        return $this->signIn($request, $user);
    }

    /**
     * Complete a customer login. A guest who was sent here mid-purchase (or
     * from a client-area page) continues where they left off; everyone else
     * starts in the dashboard.
     */
    private function signIn(Request $request, User $user)
    {
        $this->establishSession($request, $user);

        return redirect()->to(AuthIntent::pull($request->session(), route('dashboard')));
    }

    /**
     * Session mechanics shared by login and email verification: regenerate
     * the session against fixation, guarantee a customer UID and snapshot
     * the customer into the session.
     */
    private function establishSession(Request $request, User $user): void
    {
        $request->session()->regenerate();
        if (!$user->customer_uid) {
            $user->forceFill(['customer_uid' => User::generateUid()])->save();
        }
        $request->session()->put('customer', self::customerSession($user));
    }

    public function showRegister(Request $request)
    {
        // The checkout page sends guests here with return_to so a brand-new
        // buyer can be returned to their purchase after verification. Without
        // the query param a plain sign-up starts in the dashboard after
        // verification, so an older destination is dropped.
        AuthIntent::replace($request->session(), $this->intendedDestination($request));

        return view('auth.register', ['returnTo' => AuthIntent::peek($request->session())]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_dial' => ['nullable', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9]+$/'],
            'company_name' => ['nullable', 'string', 'max:190'],
            'address1' => ['required', 'string', 'max:190'],
            // address2 and state are intentionally not collected on the sign-up form.
            'city' => ['required', 'string', 'max:120'],
            'country' => ['required', 'string', 'max:120'],
            'postcode' => ['required', 'string', 'max:30'],
            'currency' => ['required', 'string', 'max:10'],
            'terms' => ['required', 'accepted'],
            'password' => ['required', 'string', 'confirmed', new StrongPassword],
        ]);

        $normalizedPhone = trim(($request->input('phone_dial', '') . ' ' . $data['phone']));
        if (User::where('phone', $normalizedPhone)->exists()) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['phone' => 'This phone number is already registered.']);
        }

        if (!$this->turnstileIsValid($request)) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['captcha' => 'Please complete the security check and try again.']);
        }

        $user = User::create([
            'name' => trim(preg_replace('/\s+/u', ' ', $data['name'])),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $normalizedPhone,
            'company_name' => $data['company_name'] ?? null,
            'address1' => $data['address1'],
            'city' => $data['city'],
            'country' => $data['country'],
            'postcode' => $data['postcode'],
            'currency' => $data['currency'],
            'found_us' => null,
        ]);
        $user->forceFill(['customer_uid' => User::generateUid()])->save();
        if ($this->sendVerificationEmail($user)) {
            $this->startVerificationResendCooldown($request, $user->email);
        }

        return redirect()->route('verification.notice')->with('verify_email', $user->email);
    }

    private function turnstileIsValid(Request $request): bool
    {
        $secret = (string) config('services.turnstile.secret_key');
        if ($secret === '') {
            return true;
        }

        $token = (string) $request->input('cf-turnstile-response');
        if ($token === '') {
            return false;
        }

        try {
            if ($this->httpClient) {
                $response = $this->httpClient->post(
                    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                    [
                        'form_params' => [
                            'secret' => $secret,
                            'response' => $token,
                            'remoteip' => $request->ip(),
                        ],
                    ]
                );
                $body = (string) $response->getBody();
            } else {
                if (!function_exists('curl_init')) {
                    throw new \RuntimeException('The PHP cURL extension is not available.');
                }

                $curl = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
                curl_setopt_array($curl, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query([
                        'secret' => $secret,
                        'response' => $token,
                        'remoteip' => $request->ip(),
                    ]),
                    CURLOPT_TIMEOUT => 8,
                ]);
                $body = curl_exec($curl);
                $curlError = curl_error($curl);
                curl_close($curl);

                if ($body === false) {
                    throw new \RuntimeException($curlError ?: 'Cloudflare verification request failed.');
                }
            }

            $result = json_decode((string) $body, true);
        } catch (\Throwable $e) {
            Log::error('Turnstile verification request failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if (!is_array($result) || empty($result['success'])) {
            Log::warning('Turnstile verification rejected registration', [
                'error_codes' => is_array($result) ? ($result['error-codes'] ?? []) : [],
            ]);

            return false;
        }

        return true;
    }

    /**
     * "Check your inbox" page shown after sign-up, with a resend form.
     */
    public function verificationNotice(Request $request)
    {
        $email = session('verify_email', old('email'));

        return view('auth.verify', [
            'email' => $email,
            'resendAvailableAt' => $this->verificationResendAvailableAt($request, $email),
            'returnTo' => AuthIntent::peek($request->session()),
        ]);
    }

    public function resendVerification(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $availableAt = $this->verificationResendAvailableAt($request, $data['email']);
        if ($availableAt !== null) {
            $seconds = max(1, $availableAt - now()->timestamp);

            return redirect()
                ->route('verification.notice')
                ->with('verify_email', $data['email'])
                ->withErrors(['email' => 'Please wait ' . ceil($seconds / 60) . ' minute(s) before requesting another verification email.']);
        }

        $user = User::where('email', $data['email'])->first();

        // Same answer whether or not the address exists, so emails can't be probed.
        if ($user && !$user->email_verified_at) {
            if ($this->sendVerificationEmail($user)) {
                $this->startVerificationResendCooldown($request, $user->email);
            }
        }

        return redirect()
            ->route('verification.notice')
            ->with('verify_email', $data['email'])
            ->with('success', 'If that account is waiting for verification, a new link is on its way.');
    }

    private function startVerificationResendCooldown(Request $request, $email): void
    {
        $request->session()->put('verification_resend_email', strtolower(trim($email)));
        $request->session()->put('verification_resend_available_at', now()->addSeconds(self::VERIFICATION_RESEND_COOLDOWN)->timestamp);
    }

    private function verificationResendAvailableAt(Request $request, $email)
    {
        $availableAt = (int) $request->session()->get('verification_resend_available_at', 0);
        $cooldownEmail = strtolower(trim((string) $request->session()->get('verification_resend_email', '')));

        if (!$availableAt || !$email || $cooldownEmail !== strtolower(trim($email))) {
            return null;
        }

        if ($availableAt <= now()->timestamp) {
            $request->session()->forget(['verification_resend_email', 'verification_resend_available_at']);

            return null;
        }

        return $availableAt;
    }

    /**
     * Guide section 5: consume the one-time token from /verify-email/{token}.
     * Marks the address verified, burns the token and mails login details.
     * Then signs the user in and returns them to their intended page
     * (e.g. straight back to checkout) — no separate login step needed.
     */
    public function verifyEmail(Request $request, $token)
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
            'year' => date('Y'),
            'logo_url' => url('image/logo.png'),
            'support_email' => \App\Models\Setting::get('support_email'),
            'support_phone' => \App\Models\Setting::get('support_phone'),
            'footer_text' => \App\Models\Setting::get('footer_text'),
        ]);

        $this->establishSession($request, $user);

        // Show the green-tick confirmation first, then let it continue to
        // the page the user started from (checkout for a returning buyer).
        $destination = AuthIntent::pull($request->session(), route('dashboard'));
        $request->session()->flash('verified_destination', $destination);

        return redirect()->route('email.verified');
    }

    /**
     * Green-tick "email verified" confirmation page (verifyEmail sends
     * freshly verified users here). Shows the success moment for a few
     * seconds, then continues to the stored destination. A session-less
     * visit (other device, stale link) simply falls through to the dashboard.
     */
    public function verifiedNotice(Request $request)
    {
        if (!$request->session()->has('verified_destination')) {
            return redirect()->route('dashboard');
        }

        $destination = (string) $request->session()->get('verified_destination');
        try {
            app('router')->getRoutes()->match(Request::create($destination, 'GET'));
        } catch (\Throwable $e) {
            $destination = route('dashboard', [], false);
            $request->session()->put('verified_destination', $destination);
        }

        return view('auth.verified', [
            'destination' => $destination,
            'name' => $request->session()->get('customer.name', 'there'),
        ]);
    }

    /**
     * Guide section 5: store a one-time token, then queue the mail (section 7).
     * Never breaks registration if database/email_system.sql has not been run.
     */
    public function sendVerificationEmail(User $user)
    {
        try {
            if (!Schema::hasTable('email_verification_tokens') || !Schema::hasTable('email_templates')) {
                Log::warning('Email system tables missing — run database/email_system.sql to send verification mail.');

                return false;
            }

            EmailVerificationToken::where('user_id', $user->id)
                ->whereNull('used_at')
                ->delete();

            $token = Str::random(64);

            EmailVerificationToken::create([
                'user_id' => $user->id,
                'token' => $token,
                'expires_at' => now()->addHour(),
            ]);

            SendEmailJob::dispatch(0, 'email_verification', $user->email, [
                'name' => $user->first_name ?: $user->name,
                'link' => url('/verify-email/' . $token),
                'year' => date('Y'),
                'logo_url' => url('image/logo.png'),
                'support_email' => \App\Models\Setting::get('support_email'),
                'support_phone' => \App\Models\Setting::get('support_phone'),
                'footer_text' => \App\Models\Setting::get('footer_text'),
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('Could not queue verification email', ['error' => $e->getMessage()]);

            return false;
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