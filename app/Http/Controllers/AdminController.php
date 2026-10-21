<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Models\ChatMessage;
use App\Models\DomainOrder;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\SupportTicket;
use App\Models\Topup;
use App\Services\InvoiceService;
use App\Services\Notifier;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            app(InvoiceService::class)->markOrderPaid($order->fresh());
            if ($order->customer_email) {
                Notifier::send($order->customer_email, new InvoiceMail($order->fresh()));
            }

            return redirect()->route('admin.dashboard')->with('success', 'Order marked paid and the invoice was emailed to the customer.');
        }

        // Failed / refunded orders reopen their invoice (amount received back to zero).
        if (in_array($data['status'], ['failed', 'refunded'], true)) {
            app(InvoiceService::class)->markOrderUnpaid($order->fresh());
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

        // Approval also issues the paid top-up receipt the customer can download.
        app(InvoiceService::class)->createForTopup($topup->fresh());

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

    public function editCustomer(Request $request, User $customer)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        return view('admin.customer-edit', ['customer' => $customer]);
    }

    public function updateCustomer(Request $request, User $customer)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($customer->id)],
            'phone' => ['required', 'string', 'max:40'],
            'company_name' => ['nullable', 'string', 'max:190'],
            'address1' => ['required', 'string', 'max:190'],
            'address2' => ['nullable', 'string', 'max:190'],
            'city' => ['required', 'string', 'max:120'],
            'country' => ['required', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'postcode' => ['required', 'string', 'max:30'],
            'currency' => ['required', 'string', 'max:10'],
            'found_us' => ['nullable', 'string', 'max:190'],
            'newsletter' => ['nullable', 'boolean'],
        ]);

        $customer->fill($data);
        $customer->name = trim($data['first_name'] . ' ' . $data['last_name']);
        $customer->newsletter = $request->boolean('newsletter');
        $customer->save();

        return redirect()->route('admin.customers')->with('success', 'Customer details updated.');
    }

    /**
     * ---------------------------------------------------------------
     * Invoices: hosted bills with admin-only editing.
     *
     * - /admin/invoices               list (all invoices, newest first)
     * - /admin/invoices/{invoice}     view the hosted invoice + PDF links
     * - /admin/invoices/{invoice}/edit  edit line items (SL, years, prices…)
     * - POST /admin/invoices/{invoice}  save: servers recompute all money
     *
     * The 3-years -> 4-years upgrade case is handled in
     * InvoiceService::applyEdit(): a paid invoice whose total grows reopens
     * as unpaid while keeping the already-paid amount on record.
     * ---------------------------------------------------------------
     */
    public function invoices(Request $request)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        $search = trim((string) $request->query('q'));
        $invoices = Invoice::query()
            ->with('user', 'items', 'source')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($u) use ($search) {
                            $u->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('customer_uid', 'like', "%{$search}%");
                        })
                        ->orWhereHas('items', function ($items) use ($search) {
                            $items->where('description', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(25)
            ->appends(['q' => $search]);

        return view('admin.invoices', ['invoices' => $invoices, 'search' => $search]);
    }

    public function showInvoice(Request $request, Invoice $invoice)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        return view('invoices.show', [
            'invoice' => $invoice->loadMissing('items', 'user'),
            'isAdmin' => true,
            'backUrl' => route('admin.invoices'),
            'customer' => ['name' => $invoice->user ? $invoice->user->name : 'Customer', 'email' => $invoice->user ? $invoice->user->email : ''],
        ]);
    }

    public function editInvoice(Request $request, Invoice $invoice)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }
        if (!$invoice->isEditable()) {
            return redirect()->route('admin.invoices')->withErrors(['status' => 'A voided invoice can no longer be edited.']);
        }

        $invoice->loadMissing('items', 'user');

        return view('admin.invoice-edit', ['invoice' => $invoice]);
    }

    public function updateInvoice(Request $request, Invoice $invoice)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }
        if (!$invoice->isEditable()) {
            return redirect()->route('admin.invoices')->withErrors(['status' => 'A voided invoice can no longer be edited.']);
        }

        $data = $request->validate([
            'status' => ['required', 'in:unpaid,paid,void'],
            'payment_method' => ['nullable', 'in:wallet,bkash,nagad,bank'],
            'vat_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.item_type' => ['nullable', 'in:domain-registration,domain-renewal,hosting,topup,manual'],
            'items.*.years' => ['required', 'integer', 'min:0', 'max:10'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        app(InvoiceService::class)->applyEdit($invoice, $data['items'], $data);

        return redirect()->route('admin.invoices.show', $invoice->fresh())
            ->with('success', 'Invoice ' . $invoice->invoice_number . ' saved — the customer sees the updated invoice at the same link.');
    }

    /**
     * Quick paid/unpaid switch from the hosted invoice page, so an admin can
     * reverse a payment without re-saving every line item.
     */
    public function updateInvoiceStatus(Request $request, Invoice $invoice)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }
        if (!$invoice->isEditable()) {
            return redirect()->route('admin.invoices.show', $invoice)
                ->withErrors(['status' => 'A voided invoice can no longer be changed.']);
        }

        $data = $request->validate(['status' => ['required', 'in:paid,unpaid']]);

        app(InvoiceService::class)->setStatus($invoice, $data['status']);

        return redirect()->route('admin.invoices.show', $invoice->fresh())
            ->with('success', 'Invoice ' . $invoice->invoice_number . ' marked as ' . $data['status'] . '.');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin');
        return redirect()->route('admin.login');
    }
}