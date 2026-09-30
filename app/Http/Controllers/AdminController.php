<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Models\ChatMessage;
use App\Models\DomainOrder;
use App\Models\Setting;
use App\Models\SupportTicket;
use App\Models\Topup;
use App\Services\Notifier;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $wasPaid = $order->payment_status === 'paid';
        $order->update(['status' => $data['status']]);

        // Manual payments (bank / Nagad) are confirmed here: mark paid and email the invoice.
        if ($data['status'] === 'paid' && !$wasPaid) {
            $order->update(['payment_status' => 'paid']);
            if ($order->customer_email) {
                Notifier::send($order->customer_email, new InvoiceMail($order->fresh()));
            }

            return redirect()->route('admin.dashboard')->with('success', 'Order marked paid and the invoice was emailed to the customer.');
        }

        return redirect()->route('admin.dashboard')->with('success', 'Order status updated.');
    }

    /**
     * Live chat inbox: conversations with the last message + unread badge.
     */
    public function chat(Request $request, $session = null)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        $conversations = ChatMessage::selectRaw('session_id, MAX(id) AS last_id, SUM(CASE WHEN sender = \'customer\' AND seen_at IS NULL THEN 1 ELSE 0 END) AS unread')
            ->groupBy('session_id')
            ->orderByDesc('last_id')
            ->get();

        $sessions = $conversations->map(function ($row) {
            $last = ChatMessage::find($row->last_id);
            $user = $last && $last->user_id ? User::find($last->user_id) : null;
            preg_match('/^(user|guest)-(.+)$/', (string) $row->session_id, $m);

            return (object) [
                'session_id' => $row->session_id,
                'name' => $user ? $user->name : ('Guest ' . substr((string) ($m[2] ?? ''), 0, 8)),
                'email' => $user ? $user->email : 'guest',
                'uid' => $user ? $user->customer_uid : null,
                'unread' => (int) $row->unread,
                'last_message' => $last ? $last->message : '',
                'last_time' => $last ? $last->created_at->format('d M, h:i A') : '',
                'last_sender' => $last ? $last->sender : '',
            ];
        });

        $selected = $session && $sessions->firstWhere('session_id', $session) ? $session : ($sessions->first()->session_id ?? null);
        $messages = $selected
            ? ChatMessage::where('session_id', $selected)->orderBy('created_at')->orderBy('id')->get()
            : collect();

        if ($selected) {
            ChatMessage::where('session_id', $selected)->where('sender', 'customer')->whereNull('seen_at')->update(['seen_at' => now()]);
        }

        return view('admin.chat', [
            'sessions' => $sessions,
            'selected' => $selected,
            'messages' => $messages,
            'topups' => Topup::with('user')->where('status', 'pending')->latest()->take(10)->get(),
        ]);
    }

    /**
     * Approve a top-up: credit the customer's balance atomically.
     */
    public function approveTopup(Request $request, Topup $topup)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }
        if ($topup->status !== 'pending') {
            return back()->withErrors(['status' => 'This request was already processed.']);
        }

        DB::transaction(function () use ($request, $topup) {
            $user = User::whereKey($topup->user_id)->lockForUpdate()->first();
            $user->forceFill(['balance' => (float) $user->balance + (float) $topup->amount])->save();
            $topup->forceFill([
                'status' => 'approved',
                'approved_at' => now(),
                'admin_note' => 'Approved by ' . session('admin.email'),
            ])->save();
        });

        return back()->with('success', 'Top-up ' . $topup->reference . ' approved and balance credited.');
    }

    public function rejectTopup(Request $request, Topup $topup)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }
        if ($topup->status !== 'pending') {
            return back()->withErrors(['status' => 'This request was already processed.']);
        }

        $topup->forceFill([
            'status' => 'rejected',
            'approved_at' => now(),
            'admin_note' => 'Rejected by ' . session('admin.email'),
        ])->save();

        return back()->with('success', 'Top-up ' . $topup->reference . ' rejected.');
    }

    /**
     * CMS: edit the public site's text content (stored in the settings table).
     */
    public function content(Request $request)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        return view('admin.content', ['groups' => Setting::fields(), 'values' => Setting::allValues()]);
    }

    public function updateContent(Request $request)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        $keys = [];
        foreach (Setting::fields() as $group) {
            $keys = array_merge($keys, array_keys($group));
        }
        $rules = array_fill_keys($keys, ['nullable', 'string', 'max:2000']);
        $data = $request->validate($rules);

        Setting::put(array_map(function ($value) {
            return trim((string) $value);
        }, array_intersect_key($data, array_flip($keys))));

        return redirect()->route('admin.content')->with('success', 'Site content saved — the public site is updated.');
    }

    public function customers(Request $request)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        $search = trim((string) $request->query('q'));
        $customers = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('customer_uid', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(25)
            ->appends(['q' => $search]);

        return view('admin.customers', [
            'customers' => $customers,
            'search' => $search,
            'topups' => Topup::with('user')->latest()->take(15)->get(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin');
        return redirect()->route('admin.login');
    }
}