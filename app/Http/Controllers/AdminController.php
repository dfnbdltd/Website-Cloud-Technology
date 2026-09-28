<?php

namespace App\Http\Controllers;

use App\Models\DomainOrder;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function loginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (!$configEmail = config('services.admin.email')) {
            return back()->withErrors(['email' => 'Admin access is not configured.']);
        }
        if (!hash_equals($configEmail, $data['email']) || !hash_equals((string) config('services.admin.password'), $data['password'])) {
            return back()->withErrors(['email' => 'Invalid admin credentials.'])->withInput();
        }
        $request->session()->regenerate();
        $request->session()->put('admin', ['email' => $data['email']]);
        return redirect()->route('admin.dashboard');
    }

    public function dashboard(Request $request)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }
        return view('admin.dashboard', [
            'orders' => DomainOrder::with('items')->latest()->take(50)->get(),
            'tickets' => SupportTicket::latest()->take(20)->get(),
        ]);
    }

    public function updateOrder(Request $request, DomainOrder $order)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }
        $data = $request->validate(['status' => ['required', 'in:payment_pending,paid,registration_pending,active,failed,refunded']]);
        $order->update(['status' => $data['status']]);
        return redirect()->route('admin.dashboard')->with('success', 'Order status updated.');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin');
        return redirect()->route('admin.login');
    }
}