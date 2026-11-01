<?php

namespace App\Http\Controllers;

use App\Models\DomainOrder;
use App\Models\SupportTicket;
use App\Models\Topup;
use App\Rules\StrongPassword;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function dashboard(Request $request)
    {
        if (!$this->customer($request)) { return redirect()->route('login'); }
        $userId = session('customer.id');
        // The dashboard represents owned services only. Never expose an order
        // as a purchased domain until its payment is confirmed in the DB.
        $orders = DomainOrder::with('items')
            ->where('user_id', $userId)
            ->where('payment_status', 'paid')
            ->latest()
            ->take(10)
            ->get();
        $allOrders = DomainOrder::where('user_id', $userId);
        $orderCount = (clone $allOrders)->where('payment_status', 'paid')->count();
        $pendingCount = (clone $allOrders)->where('payment_status', 'pending')->where('status', '!=', 'cancelled')->count();
        $outstanding = (clone $allOrders)->whereIn('payment_status', ['pending', 'failed'])->where('status', '!=', 'cancelled')->sum('total');
        $openTickets = SupportTicket::where('user_id', $userId)->where('status', 'open')->count();
        $orderTrend = collect(range(5, 0))->map(function ($monthsAgo) use ($userId) {
            $month = Carbon::now()->subMonths($monthsAgo);
            return [
                'label' => $month->format('M'),
                'count' => DomainOrder::where('user_id', $userId)
                    ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                    ->count(),
            ];
        });
        $paymentBreakdown = [
            'paid' => (clone $allOrders)->where('payment_status', 'paid')->count(),
            'pending' => $pendingCount,
            'failed' => (clone $allOrders)->where('payment_status', 'failed')->count(),
        ];
        $invoiceMap = \App\Models\Invoice::whereIn('source_id', $orders->modelKeys())
            ->where('source_type', DomainOrder::class)
            ->get()
            ->keyBy('source_id');
        $hostingInvoices = \App\Models\Invoice::with('items')
            ->where('user_id', $userId)
            ->where('status', \App\Models\Invoice::STATUS_PAID)
            ->whereHas('items', function ($items) {
                $items->where('item_type', 'like', '%hosting%')
                    ->orWhere('description', 'like', '%hosting%');
            })
            ->latest()
            ->get();
        $today = Carbon::today();
        $domainLifecycle = collect();
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $purchaseDate = $order->created_at->copy()->startOfDay();
                $years = max(1, (int) $item->years);
                $expiryDate = $purchaseDate->copy()->addYears($years);
                $totalDays = max(1, $purchaseDate->diffInDays($expiryDate));
                $elapsedDays = $today->greaterThanOrEqualTo($expiryDate)
                    ? $totalDays
                    : min($totalDays, max(0, $purchaseDate->diffInDays($today)));
                $remainingDays = $today->lessThan($expiryDate) ? $today->diffInDays($expiryDate) : 0;
                $domainLifecycle->push([
                    'domain' => $item->domain,
                    'order' => $order,
                    'years' => $years,
                    'purchaseDate' => $purchaseDate,
                    'expiryDate' => $expiryDate,
                    'progress' => round(($elapsedDays / $totalDays) * 100, 1),
                    'remainingDays' => $remainingDays,
                    'expired' => $today->greaterThanOrEqualTo($expiryDate),
                    'amount' => (float) ($item->purchase_price ?: $item->price),
                ]);
            }
        }
        return view('dashboard', [
            'customer' => session('customer'),
            'orders' => $orders,
            'orderCount' => $orderCount,
            'pendingCount' => $pendingCount,
            'outstanding' => $outstanding,
            'openTickets' => $openTickets,
            'domainCount' => $orderCount,
            'orderTrend' => $orderTrend,
            'paymentBreakdown' => $paymentBreakdown,
            'invoiceMap' => $invoiceMap,
            'hostingInvoices' => $hostingInvoices,
            'domainLifecycle' => $domainLifecycle,
        ]);
    }

    /**
     * My Store: domains, hosting, invoices, payments, balance and top-ups.
     */
    public function myStore(Request $request)
    {
        if (!$this->customer($request)) { return redirect()->route('login'); }
        $userId = session('customer.id');
        $orders = DomainOrder::with('items')
            ->where('user_id', $userId)
            ->where('payment_status', 'paid')
            ->latest()
            ->take(6)
            ->get();
        $payments = Topup::where('user_id', $userId)->latest()->take(6)->get();
        $domainCount = DomainOrder::where('user_id', $userId)
            ->where('payment_status', 'paid')
            ->count();
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
            ->route('wallet.index')
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
            'password' => ['required', 'string', 'confirmed', new StrongPassword],
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
        $orders = DomainOrder::where('user_id', session('customer.id'))
            ->where('payment_status', 'paid')
            ->latest()
            ->get();

        // Invoice rows created beside each order, keyed by order id for the list.
        $invoiceMap = \App\Models\Invoice::whereIn('source_id', $orders->modelKeys())
            ->where('source_type', DomainOrder::class)
            ->get()
            ->keyBy('source_id');

        return view('billing.invoices', ['orders' => $orders, 'invoiceMap' => $invoiceMap]);
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