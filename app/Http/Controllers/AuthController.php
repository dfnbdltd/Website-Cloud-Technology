<?php

namespace App\Http\Controllers;

use App\Mail\AccountReady;
use App\Mail\VerifyEmail;
use App\Services\Notifier;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() || session('customer')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
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
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_dial' => ['nullable', 'string', 'max:8'],
            'phone' => ['required', 'string', 'max:32'],
            'company_name' => ['nullable', 'string', 'max:190'],
            'address1' => ['required', 'string', 'max:190'],
            'address2' => ['nullable', 'string', 'max:190'],
            'city' => ['required', 'string', 'max:120'],
            'country' => ['required', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'postcode' => ['required', 'string', 'max:30'],
            'currency' => ['required', 'string', 'max:10'],
            'found_us' => ['nullable', 'string', 'max:190'],
            'security_question' => ['required', 'string', 'max:255'],
            'security_answer' => ['required', 'string', 'max:255'],
            'newsletter' => ['nullable', 'boolean'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $user = User::create([
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => trim(($request->input('phone_dial', '') . ' ' . $data['phone'])),
            'company_name' => $data['company_name'] ?? null,
            'address1' => $data['address1'],
            'address2' => $data['address2'] ?? null,
            'city' => $data['city'],
            'country' => $data['country'],
            'state' => $data['state'] ?? null,
            'postcode' => $data['postcode'],
            'currency' => $data['currency'],
            'found_us' => $data['found_us'] ?? null,
            'security_question' => $data['security_question'],
            'security_answer' => $data['security_answer'],
            'newsletter' => $request->boolean('newsletter'),
        ]);
        $user->forceFill(['customer_uid' => User::generateUid()])->save();
        Notifier::send($user->email, new VerifyEmail($user));

        return redirect()->route('verification.notice')->with('verify_email', $user->email);
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
            Notifier::send($user->email, new VerifyEmail($user));
        }

        return redirect()
            ->route('verification.notice')
            ->with('verify_email', $data['email'])
            ->with('success', 'If that account is waiting for verification, a new link is on its way.');
    }

    /**
     * Signed link from the verification email: activate, email the login
     * details, sign the customer in and continue where they left off.
     */
    public function verify(Request $request, $id, $hash)
    {
        $user = User::find($id);
        if (!$user || !URL::hasValidSignature($request, false) || !hash_equals(sha1($user->email), (string) $hash)) {
            return redirect()->route('login')->withErrors(['email' => 'That verification link is invalid or has expired. Sign in to get a new one.']);
        }

        if (!$user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
            Notifier::send($user->email, new AccountReady($user));
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('customer', self::customerSession($user));

        $next = $request->session()->get('domain_cart') ? route('cart.index') : route('dashboard');

        return redirect()->intended($next)->with('success', 'Email verified — welcome aboard, ' . ($user->first_name ?: $user->name) . '! Your login details have been emailed to you.');
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