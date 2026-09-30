<?php

namespace App\Http\Controllers;

use App\Models\DomainOrder;
use App\Models\SupportTicket;
use App\Models\Topup;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function dashboard(Request $request)
    {
        if (!$this->customer($request)) { return redirect()->route('login'); }
        $userId = session('customer.id');
        $orders = DomainOrder::where('user_id', $userId)->latest()->take(5)->get();
        $allOrders = DomainOrder::where('user_id', $userId);
        $orderCount = (clone $allOrders)->count();
        $pendingCount = (clone $allOrders)->where('payment_status', 'pending')->count();
        $outstanding = (clone $allOrders)->whereIn('payment_status', ['pending', 'failed'])->sum('total');
        $openTickets = SupportTicket::where('user_id', $userId)->where('status', 'open')->count();
        return view('dashboard', [
            'customer' => session('customer'),
            'orders' => $orders,
            'orderCount' => $orderCount,
            'pendingCount' => $pendingCount,
            'outstanding' => $outstanding,
            'openTickets' => $openTickets,
            'domainCount' => $orderCount,
        ]);
    }

    /**
     * My Store: domains, hosting, invoices, payments, balance and top-ups.
     */
    public function myStore(Request $request)
    {
        if (!$this->customer($request)) { return redirect()->route('login'); }
        $userId = session('customer.id');
        $orders = DomainOrder::where('user_id', $userId)->latest()->take(6)->get();
        $payments = Topup::where('user_id', $userId)->latest()->take(6)->get();
        $domainCount = DomainOrder::where('user_id', $userId)->count();
        $paidTotal = (float) DomainOrder::where('user_id', $userId)->where('payment_status', 'paid')->sum('total');
        $pendingTopup = Topup::where('user_id', $userId)->where('status', 'pending')->count();

        return view('store.index', [
            'customer' => session('customer'),
            'orders' => $orders,
            'payments' => $payments,
            'domainCount' => $domainCount,
            'paidTotal' => $paidTotal,
            'pendingTopup' => $pendingTopup,
        ]);
    }

    /**
     * Customer submits an "Add Balance" request (manual bKash/Nagad/bank proof).
     */
    public function addBalance(Request $request)
    {
        if (!$this->customer($request)) { return redirect()->route('login'); }
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:10', 'max:500000'],
            'method' => ['required', 'in:bkash,nagad,bank'],
            'sender_number' => ['nullable', 'string', 'max:40'],
            'transaction_id' => ['nullable', 'string', 'max:60'],
        ]);

        Topup::create([
            'user_id' => session('customer.id'),
            'reference' => 'TU-' . date('Ymd') . '-' . strtoupper(substr(sha1(uniqid('', true)), 0, 6)),
            'amount' => round((float) $data['amount'], 2),
            'method' => $data['method'],
            'sender_number' => $data['sender_number'] ?? null,
            'transaction_id' => $data['transaction_id'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()
            ->route('mystore')
            ->with('success', 'Add-balance request received. Our billing team will verify it and credit your balance shortly.');
    }

    public function profileSettings(Request $request)
    {
        if (!$this->customer($request)) { return redirect()->route('login'); }
        $user = User::find(session('customer.id'));
        if (!$user) { return redirect()->route('logout'); }

        return view('profile.settings', ['user' => $user, 'customer' => session('customer')]);
    }

    public function updateProfile(Request $request)
    {
        if (!$this->customer($request)) { return redirect()->route('login'); }
        $user = User::find(session('customer.id'));
        if (!$user) { return redirect()->route('logout'); }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'company_name' => ['nullable', 'string', 'max:190'],
            'address1' => ['required', 'string', 'max:190'],
            'address2' => ['nullable', 'string', 'max:190'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'postcode' => ['required', 'string', 'max:30'],
            'country' => ['required', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $user->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'phone' => $data['phone'],
            'company_name' => $data['company_name'] ?? null,
            'address1' => $data['address1'],
            'address2' => $data['address2'] ?? null,
            'city' => $data['city'],
            'state' => $data['state'] ?? null,
            'postcode' => $data['postcode'],
            'country' => $data['country'],
        ]);

        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $path = $request->file('logo')->store('logos', 'public');
            $user->forceFill(['logo' => $path]);
        }

        $user->save();
        $request->session()->put('customer', AuthController::customerSession($user->refresh()));

        return redirect()->route('profile.settings')->with('success', 'Profile updated.');
    }

    public function changePasswordForm(Request $request)
    {
        if (!$this->customer($request)) { return redirect()->route('login'); }

        return view('profile.password', ['customer' => session('customer')]);
    }

    public function changePassword(Request $request)
    {
        if (!$this->customer($request)) { return redirect()->route('login'); }
        $user = User::find(session('customer.id'));
        if (!$user) { return redirect()->route('logout'); }

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Your current password is incorrect.']);
        }

        $user->forceFill(['password' => Hash::make($data['password'])])->save();
        $request->session()->put('customer', AuthController::customerSession($user->refresh()));

        return redirect()->route('change.password')->with('success', 'Password changed successfully.');
    }

    public function invoices(Request $request)
    {
        if (!$this->customer($request)) { return redirect()->route('login'); }
        return view('billing.invoices', ['orders' => DomainOrder::where('user_id', session('customer.id'))->latest()->get()]);
    }

    public function support(Request $request)
    {
        if (!$this->customer($request)) { return redirect()->route('login'); }
        return view('support.index', ['tickets' => SupportTicket::where('user_id', session('customer.id'))->latest()->get()]);
    }

    public function createTicket(Request $request)
    {
        if (!$this->customer($request)) { return redirect()->route('login'); }
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:5000'],
            'priority' => ['required', 'in:low,normal,high'],
        ]);
        SupportTicket::create(array_merge($data, ['user_id' => session('customer.id'), 'status' => 'open']));
        return redirect()->route('support')->with('success', 'Your support ticket has been opened.');
    }

    private function customer(Request $request)
    {
        return (bool) $request->session()->get('customer');
    }
}