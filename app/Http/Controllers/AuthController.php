<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
            Auth::login($user, $remember);
        }

        $request->session()->regenerate();
        $user = Auth::user();
        $request->session()->put('customer', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => (string) $user->phone,
            'plan' => 'BDIX Premium',
        ]);

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
        Auth::login($user);
        $request->session()->put('customer', ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'phone' => (string) $user->phone, 'plan' => 'BDIX Premium']);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Welcome aboard, ' . $user->first_name . '! Your account is ready.');
    }


    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}