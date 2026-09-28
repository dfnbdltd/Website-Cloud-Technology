<?php

namespace App\Http\Controllers;

use App\Models\DomainOrder;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

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