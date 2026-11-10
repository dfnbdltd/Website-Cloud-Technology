<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Models\ChatMessage;
use App\Models\DomainOrder;
use App\Models\HostingPlan;
use App\Models\HostingPlanFeature;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\SupportTicket;
use App\Models\SslPlan;
use App\Models\SslPlanFeature;
use App\Models\Topup;
use App\Services\InvoiceService;
use App\Services\Notifier;
use App\Services\ExchangeRateService;
use App\Services\DomainRegistrationService;
use App\Services\DomainTransferService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
            'exchangeRate' => app(ExchangeRateService::class)->usdToBdt(),
        ]);
    }

    public function apis(Request $request)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        $credential = function ($configKey, $environmentName, $secret = false) {
            $value = config($configKey);

            return [
                'name' => $environmentName,
                'type' => $secret ? 'Secret' : 'Credential',
                'status' => !empty($value) ? 'Configured' : 'Missing',
            ];
        };

        $resellerClubBase = rtrim((string) config('services.resellerclub.base_url'), '/');
        $bkashBase = rtrim((string) config('services.bkash.base_url'), '/');
        $resellerClubApiKeyConfigured = !empty(config('services.resellerclub.api_key'))
            || !empty(config('services.resellerclub.legacy_api_key'));
        $resellerClubConfigured = config('services.resellerclub.enabled')
            && !empty(config('services.resellerclub.auth_userid'))
            && $resellerClubApiKeyConfigured
            && !empty(config('services.resellerclub.customer_id'));
        $resellerClubContactsConfigured = true;
        foreach (['reg', 'admin', 'tech', 'billing'] as $role) {
            if (empty(config('services.resellerclub.' . $role . '_contact_id'))) {
                $resellerClubContactsConfigured = false;
                break;
            }
        }
        $resellerClubNameserversConfigured = count(array_filter((array) config('services.resellerclub.nameservers', []))) > 0;
        $resellerClubConfigured = $resellerClubConfigured
            && $resellerClubContactsConfigured
            && $resellerClubNameserversConfigured;

        return view('admin.apis', [
            'integrations' => [
                [
                    'name' => 'ResellerClub',
                    'purpose' => 'Domain availability, pricing, wallet pre-check and registration.',
                    'status' => $resellerClubConfigured ? 'Configured' : 'Needs configuration',
                    'endpoints' => [
                        ['method' => 'GET', 'url' => $resellerClubBase . '/products/customer-price.json', 'usage' => 'Fetch registration and renewal prices.'],
                        ['method' => 'GET', 'url' => $resellerClubBase . '/products/reseller-cost-price.json', 'usage' => 'Fetch actual reseller costs for invoice purchase-price snapshots.'],
                        ['method' => 'GET', 'url' => $resellerClubBase . '/billing/reseller-balance.json', 'usage' => 'Check the reseller wallet before checkout.'],
                        ['method' => 'GET', 'url' => $resellerClubBase . '/domains/available.json', 'usage' => 'Check domain availability.'],
                        ['method' => 'POST', 'url' => $resellerClubBase . '/domains/register.json', 'usage' => 'Register a paid domain under the configured default ResellerClub customer.'],
                    ],
                    'credentials' => [
                        $credential('services.resellerclub.auth_userid', 'RESELLERCLUB_AUTH_USERID'),
                        [
                            'name' => 'RESELLERCLUB_HTTP_API_KEY (or RESELLERCLUB_API_KEY)',
                            'type' => 'Secret',
                            'status' => $resellerClubApiKeyConfigured ? 'Configured' : 'Missing',
                        ],
                        $credential('services.resellerclub.reseller_id', 'RESELLERCLUB_RESELLER_ID'),
                        $credential('services.resellerclub.customer_id', 'RESELLERCLUB_CUSTOMER_ID'),
                        $credential('services.resellerclub.reg_contact_id', 'RESELLERCLUB_REG_CONTACT_ID'),
                        $credential('services.resellerclub.admin_contact_id', 'RESELLERCLUB_ADMIN_CONTACT_ID'),
                        $credential('services.resellerclub.tech_contact_id', 'RESELLERCLUB_TECH_CONTACT_ID'),
                        $credential('services.resellerclub.billing_contact_id', 'RESELLERCLUB_BILLING_CONTACT_ID'),
                    ],
                    'settings' => [
                        [
                            'name' => 'Integration enabled (RESELLERCLUB_ENABLED)',
                            'status' => config('services.resellerclub.enabled') ? 'Enabled' : 'Disabled',
                        ],
                        [
                            'name' => 'Domain registration',
                            'status' => 'Automatically attempted after successful payment',
                        ],
                        [
                            'name' => 'Domain nameservers (RESELLERCLUB_NAMESERVERS)',
                            'status' => $resellerClubNameserversConfigured ? 'Configured' : 'Missing',
                        ],
                        [
                            'name' => 'Registration contact IDs (Registrant/Admin/Tech/Billing)',
                            'status' => $resellerClubContactsConfigured ? 'Configured' : 'Missing',
                        ],
                    ],
                ],
                [
                    'name' => 'bKash Checkout',
                    'purpose' => 'Create and verify online checkout payments.',
                    'status' => app(\App\Services\BkashClient::class)->isConfigured() ? 'Configured' : 'Needs configuration',
                    'endpoints' => [
                        ['method' => 'POST', 'url' => $bkashBase . '/checkout/token/grant', 'usage' => 'Authenticate and obtain a temporary API token.'],
                        ['method' => 'POST', 'url' => $bkashBase . '/checkout/payment/create', 'usage' => 'Create a payment for the order.'],
                        ['method' => 'POST', 'url' => $bkashBase . '/checkout/payment/execute/{paymentID}', 'usage' => 'Finalize an authorized payment.'],
                        ['method' => 'POST', 'url' => $bkashBase . '/checkout/payment/query/{paymentID}', 'usage' => 'Check payment status if execution is pending.'],
                        ['method' => 'POST', 'url' => $bkashBase . '/checkout/payment/search/{trxID}', 'usage' => 'Transaction search endpoint available for reconciliation.'],
                        ['method' => 'GET', 'url' => 'https://scripts.pay.bka.sh/versions/1.2.0-beta/checkout/bKash-checkout.js', 'usage' => 'Load the bKash checkout popup in the browser.'],
                    ],
                    'credentials' => [
                        $credential('services.bkash.app_key', 'BKASH_APP_KEY'),
                        $credential('services.bkash.app_secret', 'BKASH_APP_SECRET', true),
                        $credential('services.bkash.username', 'BKASH_USERNAME'),
                        $credential('services.bkash.password', 'BKASH_PASSWORD', true),
                    ],
                    'settings' => [],
                ],
                [
                    'name' => 'USD to BDT exchange rate',
                    'purpose' => 'Convert ResellerClub USD prices to portal BDT prices.',
                    'status' => 'Public API · no credential',
                    'endpoints' => [
                        ['method' => 'GET', 'url' => 'https://hexarate.paikama.co/api/rates/latest/USD?target=BDT', 'usage' => 'Fetch and cache the USD/BDT exchange rate.'],
                    ],
                    'credentials' => [],
                    'settings' => [],
                ],
                [
                    'name' => 'Google reCAPTCHA',
                    'purpose' => 'Validate the public contact form challenge.',
                    'status' => config('services.recaptcha.secret') ? 'Configured' : 'Needs configuration',
                    'endpoints' => [
                        ['method' => 'POST', 'url' => 'https://www.google.com/recaptcha/api/siteverify', 'usage' => 'Verify the submitted challenge token.'],
                    ],
                    'credentials' => [
                        $credential('services.recaptcha.secret', 'RECAPTCHA_SECRET_KEY', true),
                    ],
                    'settings' => [],
                ],
            ],
        ]);
    }

    public function updateOrder(Request $request, DomainOrder $order)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }
        $data = $request->validate(['status' => ['required', 'in:payment_pending,paid,registration_pending,registration_processing,transfer_pending,transfer_processing,transfer_failed,active,failed,refunded,cancelled']]);
        $wasPaid = $order->payment_status === 'paid';
        if ($data['status'] === 'cancelled') {
            if ($wasPaid) {
                return redirect()->route('admin.dashboard')->withErrors(['status' => 'Paid orders cannot be cancelled.']);
            }
            app(InvoiceService::class)->cancelOrder($order);
            return redirect()->route('admin.dashboard')->with('success', 'Order cancelled and its unpaid invoice removed.');
        }

        $hasTransferItems = $order->items()->where('order_type', 'transfer')->exists();
        if ($data['status'] === 'active' && $hasTransferItems) {
            if ($order->payment_status !== 'paid') {
                return redirect()->route('admin.dashboard')->withErrors(['status' => 'A transfer cannot be marked complete before payment is confirmed.']);
            }
            $order->items()->where('order_type', 'transfer')->update(['transfer_auth_code' => null]);
        }
        $order->update(['status' => $data['status']]);

        // Manual payments (bank / Nagad) are confirmed here: mark paid and email the invoice.
        if ($data['status'] === 'paid' && !$wasPaid) {
            $order->update(['payment_status' => 'paid']);
            $order = $order->fresh();
            app(InvoiceService::class)->markOrderPaid($order);
            app(DomainRegistrationService::class)->registerPaidOrder($order);
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

    public function retryOrderTransfer(Request $request, DomainOrder $order)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        $data = $request->validate([
            'auth_codes' => ['nullable', 'array'],
            'auth_codes.*' => ['nullable', 'string', 'max:255'],
        ]);

        if ($order->payment_status !== 'paid' || $order->status !== 'transfer_failed') {
            return redirect()->route('admin.orders')->withErrors([
                'transfer' => 'Only failed, paid transfer orders can be retried.',
            ]);
        }

        $retried = app(DomainTransferService::class)->retryFailedTransfer($order, $data['auth_codes'] ?? []);
        if (!$retried) {
            return redirect()->route('admin.orders')->withErrors([
                'transfer' => 'A failed transfer needs its saved authorization code or a new EPP code before it can be retried.',
            ]);
        }

        $freshOrder = $order->fresh();
        $message = $freshOrder->status === 'transfer_pending'
            ? 'The transfer request was submitted again for order ' . $freshOrder->reference . '.'
            : 'The retry did not complete successfully. Check the ResellerClub response before retrying again.';

        if ($freshOrder->status === 'transfer_pending') {
            return redirect()->route('admin.orders')->with('success', $message);
        }

        return redirect()->route('admin.orders')->withErrors(['transfer' => $message]);
    }

    public function retryOrderRegistration(Request $request, DomainOrder $order)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }
        Log::info('Admin requested domain registration retry', [
            'order_id' => $order->id,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
        ]);
        if ($order->payment_status !== 'paid') {
            return redirect()->route('admin.orders')->withErrors(['registration' => 'Only paid orders can be retried.']);
        }

        app(DomainRegistrationService::class)->registerPaidOrder($order);
        $order = $order->fresh();
        Log::info('Domain registration retry finished', [
            'order_id' => $order->id,
            'status' => $order->status,
        ]);

        if ($order->status === 'active') {
            return redirect()->route('admin.orders')->with('success', 'Domain registration completed for ' . $order->reference . '.');
        }

        if ($order->status === 'registration_processing') {
            return redirect()->route('admin.orders')->withErrors(['registration' => 'Registration is already in progress for this order.']);
        }

        return redirect()->route('admin.orders')->withErrors(['registration' => 'Registration is still pending. Check the ResellerClub account and server log, then retry again.']);
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
            'exchangeRate' => app(ExchangeRateService::class)->usdToBdt(),
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

    public function resendCustomerVerification(Request $request, User $customer)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        if ($customer->email_verified_at) {
            return back()->withErrors(['customer' => 'This customer is already verified.']);
        }

        if (!app(AuthController::class)->sendVerificationEmail($customer)) {
            return back()->withErrors(['customer' => 'The verification email could not be sent. Check the email system configuration and try again.']);
        }

        return back()->with('success', 'A new verification email was queued for ' . $customer->email . '.');
    }

    public function deleteCustomer(Request $request, User $customer)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        DB::transaction(function () use ($customer) {
            // Preserve order and invoice history as anonymous business records,
            // while removing personal records that require a customer owner.
            if (Schema::hasTable('domain_orders')) {
                DB::table('domain_orders')->where('user_id', $customer->id)->update(['user_id' => null]);
            }
            if (Schema::hasTable('invoices')) {
                DB::table('invoices')->where('user_id', $customer->id)->update(['user_id' => null]);
            }
            if (Schema::hasTable('chat_messages')) {
                DB::table('chat_messages')->where('user_id', $customer->id)->delete();
            }
            if (Schema::hasTable('support_tickets')) {
                DB::table('support_tickets')->where('user_id', $customer->id)->delete();
            }
            if (Schema::hasTable('topups')) {
                DB::table('topups')->where('user_id', $customer->id)->delete();
            }

            if (Schema::hasTable('email_verification_tokens')) {
                DB::table('email_verification_tokens')->where('user_id', $customer->id)->delete();
            }

            $customer->delete();
        });

        return redirect()->route('admin.customers')->with('success', 'Customer account and personal records were deleted.');
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
            'items.*.item_type' => ['nullable', 'in:domain-registration,domain-renewal,domain-transfer,hosting,topup,manual'],
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

    /**
     * ---------------------------------------------------------------
     * CMS: hosting plan cards behind the shared / vps / dedicated
     * hosting pages. Editing prices, features, per-feature
     * available/unavailable flags, billing terms and ordering.
     *
     * Public pages read App\Services\HostingPlanService; no business
     * logic (purchase/checkout/invoices) touches this data.
     * ---------------------------------------------------------------
     */
    public function hostingPlans(Request $request, $type = 'shared', $term = 'monthly')
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        $type = in_array($type, HostingPlan::TYPES, true) ? $type : HostingPlan::TYPE_SHARED;
        $term = self::hostingPlanTermMeta($term) !== null ? $term : 'monthly';

        return view('admin.hosting-plans', [
            'type' => $type,
            'term' => $term,
            'termMeta' => self::hostingPlanTermMeta($term),
            'plans' => HostingPlan::query()
                ->where('type', $type)
                ->with('features')
                ->orderBy('sort')
                ->orderBy('id')
                ->get(),
        ]);
    }

    /**
     * The term switcher on the list page: one price input + one
     * active/"Coming soon" checkbox per card, saved for exactly that billing
     * term. Only the selected term's column and flag are touched.
     */
    public function saveHostingPlanTerm(Request $request, $type, $term)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        if (!in_array($type, HostingPlan::TYPES, true) || self::hostingPlanTermMeta($term) === null) {
            return redirect()->route('admin.hosting.plans');
        }

        $meta = self::hostingPlanTermMeta($term);

        $data = $request->validate([
            'prices' => ['nullable', 'array', 'max:100'],
            'terms' => ['nullable', 'array', 'max:100'],
        ]);

        $plans = HostingPlan::where('type', $type)
            ->whereIn('id', array_keys((array) ($data['prices'] ?? [])))
            ->get();

        foreach ($plans as $plan) {
            $raw = $data['prices'][$plan->id] ?? null;
            if (is_scalar($raw)) {
                $raw = trim((string) $raw);
                if ($raw === '') {
                    $raw = null;
                } elseif (is_numeric($raw) && (float) $raw >= 0) {
                    $raw = (float) $raw;
                } else {
                    $raw = null; // non-numeric input -> clear rather than crash
                }
            } else {
                $raw = null;
            }

            $plan->update([
                $meta['column'] => $raw,
                $meta['flag'] => in_array((string) $plan->id, array_map('strval', (array) ($data['terms'] ?? [])), true),
            ]);
        }

        $updated = collect($plans->map->name);

        return redirect()->route('admin.hosting.plans', ['type' => $type, 'term' => $term])
            ->with('success', $meta['label'] . ' saved for ' . $updated->take(3)->implode(', ')
                . ($updated->count() > 3 ? ' … (' . $updated->count() . ' cards)' : '') . ' — other cards and billing terms untouched.');
    }

    /** Column + flag + label used by the per-term list switcher. */
    private static function hostingPlanTermMeta($term)
    {
        $meta = [
            'monthly' => ['column' => 'price_monthly', 'flag' => 'term_monthly_active', 'visibility' => 'term_monthly_visible', 'label' => 'Monthly', 'slug' => HostingPlanFeature::TERM_MONTHLY],
            'yearly' => ['column' => 'price_yearly', 'flag' => 'term_yearly_active', 'visibility' => 'term_yearly_visible', 'label' => 'Yearly', 'slug' => HostingPlanFeature::TERM_YEARLY],
            'twoYear' => ['column' => 'price_two_year', 'flag' => 'term_two_year_active', 'visibility' => 'term_two_year_visible', 'label' => '2 Years', 'slug' => HostingPlanFeature::TERM_TWO_YEAR],
            'threeYear' => ['column' => 'price_three_year', 'flag' => 'term_three_year_active', 'visibility' => 'term_three_year_visible', 'label' => '3 Years', 'slug' => HostingPlanFeature::TERM_THREE_YEAR],
        ];

        return $meta[$term] ?? null;
    }

    public function createHostingPlan(Request $request, $type)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        return view('admin.hosting-plan-form', [
            'type' => in_array($type, HostingPlan::TYPES, true) ? $type : HostingPlan::TYPE_SHARED,
            'plan' => null,
            'returnTerm' => $this->returnTerm($request),
            'termMeta' => self::hostingPlanTermMeta($this->returnTerm($request)),
            'featureRows' => collect(),
            'featuresInherited' => false,
            'termName' => '',
            'nameInherited' => false,
        ]);
    }

    public function editHostingPlan(Request $request, HostingPlan $plan)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        $plan->loadMissing('features');
        [$featureRows, $featuresInherited] = $this->featureRowsForTerm($plan, $this->returnTerm($request));
        $returnTerm = $this->returnTerm($request);
        $nameColumn = HostingPlan::termNameColumn(HostingPlanFeature::termSlug($returnTerm));
        $nameInherited = $nameColumn !== null && ($plan->{$nameColumn} === null || $plan->{$nameColumn} === '');

        return view('admin.hosting-plan-form', [
            'type' => $plan->type,
            'plan' => $plan,
            'returnTerm' => $returnTerm,
            'termMeta' => self::hostingPlanTermMeta($returnTerm),
            'featureRows' => $featureRows,
            'featuresInherited' => $featuresInherited,
            'termName' => $plan->nameFor($returnTerm),
            'nameInherited' => $nameInherited,
        ]);
    }

    /**
     * The feature rows a card's Edit page shows for one billing term: that
     * term's own rows when it has them, otherwise the Monthly rows (with
     * an "inherited" flag so the form says so instead of silently lying).
     */
    private function featureRowsForTerm(HostingPlan $plan, $returnTerm)
    {
        $slug = HostingPlanFeature::termSlug($returnTerm);
        $rows = $plan->features->where('billing_term', $slug);
        $inherited = $rows->isEmpty();
        if ($inherited) {
            $rows = $plan->features->where('billing_term', HostingPlanFeature::TERM_MONTHLY);
        }

        return [
            $rows->map(function (HostingPlanFeature $feature) {
                return [
                    'group' => $feature->feature_group === HostingPlanFeature::GROUP_EXTRA ? 'extra' : 'primary',
                    'line' => trim((string) $feature->value_monthly),
                    'feature_key' => $feature->feature_key,
                    'is_available' => (bool) $feature->is_available,
                ];
            })->values(),
            $inherited,
        ];
    }

    /** The billing-term view the admin came from (kept across save redirects). */
    private function returnTerm(Request $request)
    {
        $term = (string) $request->input('return_term', $request->query('term', 'monthly'));

        return self::hostingPlanTermMeta($term) !== null ? $term : 'monthly';
    }

    public function storeHostingPlan(Request $request)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        [$columns, $features, $hasFeatures, $featuresTerm] = $this->validatedHostingPlan($request);

        $features = collect($features);
        $columns += [
            'term_monthly_visible' => true,
            'term_yearly_visible' => true,
            'term_two_year_visible' => true,
            'term_three_year_visible' => true,
        ];

        $plan = DB::transaction(function () use ($columns, $features, $hasFeatures, $featuresTerm) {
            $plan = HostingPlan::create($columns);
            if ($hasFeatures) {
                $this->syncHostingPlanFeatures($plan, $features, $featuresTerm);
            }
            return $plan;
        });

        return redirect()->route('admin.hosting.plans', ['type' => $plan->type, 'term' => $this->returnTerm($request)])
            ->with('success', 'Plan card "' . $plan->name . '" added — the public hosting page is updated.');
    }

    public function updateHostingPlan(Request $request, HostingPlan $plan)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        [$columns, $features, $hasFeatures, $featuresTerm] = $this->validatedHostingPlan($request);

        $features = collect($features);
        unset($columns['is_visible']);

        DB::transaction(function () use ($plan, $columns, $features, $hasFeatures, $featuresTerm) {
            $plan->update($columns);
            // The admin form always posts the feature rows; only replace
            // the term it names (never wipe on a partial POST, never touch
            // the other terms' sets).
            if ($hasFeatures) {
                $this->syncHostingPlanFeatures($plan, $features, $featuresTerm);
            }
        });

        $returnTerm = $this->returnTerm($request);

        return redirect()->route('admin.hosting.plans', ['type' => $plan->type, 'term' => $returnTerm])
            ->with('success', 'Plan card "' . $plan->nameFor($returnTerm) . '" saved — the public hosting page is updated.');
    }

    /** Quick show/hide toggle from the list page. */
    public function toggleHostingPlanVisibility(Request $request, HostingPlan $plan)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        $returnTerm = $this->returnTerm($request);
        $meta = self::hostingPlanTermMeta($returnTerm);
        $plan->update([$meta['visibility'] => !$plan->{$meta['visibility']}]);

        return redirect()->route('admin.hosting.plans', ['type' => $plan->type, 'term' => $returnTerm])
            ->with('success', 'Plan card "' . $plan->nameFor($returnTerm) . '" is now ' . ($plan->{$meta['visibility']} ? 'visible' : 'hidden') . ' for ' . $meta['label'] . '; other billing terms are untouched.');
    }

    public function deleteHostingPlan(Request $request, HostingPlan $plan)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        $type = $plan->type;
        $name = $plan->name;

        DB::transaction(function () use ($plan) {
            HostingPlanFeature::where('hosting_plan_id', $plan->id)->delete();
            $plan->delete();
        });

        return redirect()->route('admin.hosting.plans', ['type' => $type, 'term' => $this->returnTerm($request)])
            ->with('success', 'Plan card "' . $name . '" deleted.');
    }

    public function sslPlans(Request $request)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        return view('admin.ssl-plans', [
            'plans' => SslPlan::with('features')->orderBy('sort')->orderBy('id')->get(),
        ]);
    }

    public function createSslPlan(Request $request)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        return view('admin.ssl-plan-form', ['plan' => null]);
    }

    public function editSslPlan(Request $request, SslPlan $plan)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        $plan->load('features');
        return view('admin.ssl-plan-form', ['plan' => $plan]);
    }

    public function storeSslPlan(Request $request)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        [$data, $features] = $this->validatedSslPlan($request);
        $plan = DB::transaction(function () use ($data, $features) {
            $plan = SslPlan::create($data);
            $this->syncSslPlanFeatures($plan, $features);
            return $plan;
        });

        return redirect()->route('admin.ssl.plans')->with('success', 'SSL plan "' . $plan->name . '" created.');
    }

    public function updateSslPlan(Request $request, SslPlan $plan)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        [$data, $features] = $this->validatedSslPlan($request, $plan);
        DB::transaction(function () use ($plan, $data, $features) {
            $plan->update($data);
            $this->syncSslPlanFeatures($plan, $features);
        });

        return redirect()->route('admin.ssl.plans')->with('success', 'SSL plan "' . $plan->name . '" updated.');
    }

    public function toggleSslPlanVisibility(Request $request, SslPlan $plan)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        $plan->update(['is_visible' => !$plan->is_visible]);
        return redirect()->route('admin.ssl.plans')->with('success', 'SSL plan "' . $plan->name . '" is now ' . ($plan->is_visible ? 'visible' : 'hidden') . '.');
    }

    public function deleteSslPlan(Request $request, SslPlan $plan)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        $name = $plan->name;
        DB::transaction(function () use ($plan) {
            SslPlanFeature::where('ssl_plan_id', $plan->id)->delete();
            $plan->delete();
        });

        return redirect()->route('admin.ssl.plans')->with('success', 'SSL plan "' . $name . '" deleted.');
    }

    private function validatedSslPlan(Request $request, SslPlan $plan = null)
    {
        $data = $request->validate([
            'plan_key' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9-]*$/i'],
            'name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'badge' => ['nullable', 'string', 'max:80'],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'renewal_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'discount_one_year' => ['required', 'numeric', 'min:0', 'max:100'],
            'discount_two_year' => ['required', 'numeric', 'min:0', 'max:100'],
            'discount_three_year' => ['required', 'numeric', 'min:0', 'max:100'],
            'features' => ['nullable', 'array', 'max:60'],
        ]);

        $keyQuery = SslPlan::where('plan_key', $data['plan_key']);
        if ($plan) { $keyQuery->where('id', '!=', $plan->id); }
        if ($keyQuery->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['plan_key' => 'That SSL plan key is already in use.']);
        }

        $columns = [
            'plan_key' => strtolower($data['plan_key']),
            'name' => $data['name'],
            'tagline' => $data['tagline'] ?? null,
            'badge' => $data['badge'] ?? null,
            'monthly_price' => $data['monthly_price'],
            'renewal_price' => $data['renewal_price'],
            'discount_one_year' => $data['discount_one_year'],
            'discount_two_year' => $data['discount_two_year'],
            'discount_three_year' => $data['discount_three_year'],
            'term_one_year_active' => $request->boolean('term_one_year_active'),
            'term_two_year_active' => $request->boolean('term_two_year_active'),
            'term_three_year_active' => $request->boolean('term_three_year_active'),
            'term_one_year_visible' => $request->boolean('term_one_year_visible'),
            'term_two_year_visible' => $request->boolean('term_two_year_visible'),
            'term_three_year_visible' => $request->boolean('term_three_year_visible'),
            'is_visible' => $request->boolean('is_visible'),
            'sort' => (int) $request->input('sort', 0),
        ];

        $features = [];
        foreach ((array) $request->input('features', []) as $index => $feature) {
            if (!is_array($feature)) { continue; }
            $text = trim((string) ($feature['text'] ?? ''));
            if ($text === '') { continue; }
            $features[] = [
                'feature' => mb_substr($text, 0, 190),
                'is_available' => !empty($feature['is_available']),
                'sort' => count($features) + 1,
            ];
        }

        return [$columns, $features];
    }

    private function syncSslPlanFeatures(SslPlan $plan, array $features)
    {
        SslPlanFeature::where('ssl_plan_id', $plan->id)->delete();
        foreach ($features as $feature) {
            $plan->features()->create($feature);
        }
    }

    /**
     * Plan form validation: scalars via the validator, the dynamic feature
     * rows normalized in-memory (blank rows dropped, availability from the
     * per-row checkbox).
     *
     * @return array [<plan columns>, <feature rows>, <bool features posted>, <features term slug>]
     */
    private function validatedHostingPlan(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(HostingPlan::TYPES)],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'badge' => ['nullable', 'string', 'max:60'],
            'style_class' => ['required', Rule::in(array_keys(HostingPlan::STYLE_CLASSES))],
            'sort' => ['required', 'integer', 'min:0', 'max:999'],
            'price_monthly' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'price_yearly' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'price_two_year' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'price_three_year' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $featuresTerm = in_array((string) $request->input('features_term', HostingPlanFeature::TERM_MONTHLY), [
            HostingPlanFeature::TERM_MONTHLY,
            HostingPlanFeature::TERM_YEARLY,
            HostingPlanFeature::TERM_TWO_YEAR,
            HostingPlanFeature::TERM_THREE_YEAR,
        ], true)
            ? (string) $request->input('features_term', HostingPlanFeature::TERM_MONTHLY)
            : HostingPlanFeature::TERM_MONTHLY;

        $columns = [
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'badge' => $data['badge'] ?? null,
            'style_class' => $data['style_class'],
            'sort' => (int) $data['sort'],
            'is_visible' => $request->boolean('is_visible'),
        ];

        // The NAME is term-scoped too: this save targets the billing term it
        // was opened from — Monthly writes the canonical `name`, the other
        // terms write their own column (un-edited terms inherit `name`), so
        // a rename that came from the Yearly view never leaks into Monthly.
        $nameColumn = HostingPlan::termNameColumn($featuresTerm);
        if ($nameColumn === null) {
            $columns['name'] = $data['name'];
        } else {
            $columns[$nameColumn] = $data['name'];
        }

        // The card form no longer carries prices / billing terms (they are
        // edited per term on the list page). Only save them when the POST
        // actually includes them — a feature-only save must never wipe them.
        foreach (['price_monthly', 'price_yearly', 'price_two_year', 'price_three_year'] as $priceKey) {
            if ($request->exists($priceKey)) {
                $columns[$priceKey] = $data[$priceKey] ?? null;
            }
        }
        foreach (['monthly', 'yearly', 'two_year', 'three_year'] as $termKey) {
            $flagKey = 'term_' . $termKey . '_active';
            if ($request->exists($flagKey)) {
                $columns[$flagKey] = $request->boolean($flagKey);
            }
            $visibilityColumns = [
                HostingPlanFeature::TERM_MONTHLY => 'term_monthly_visible',
                HostingPlanFeature::TERM_YEARLY => 'term_yearly_visible',
                HostingPlanFeature::TERM_TWO_YEAR => 'term_two_year_visible',
                HostingPlanFeature::TERM_THREE_YEAR => 'term_three_year_visible',
            ];
            $visibilityColumn = $visibilityColumns[$featuresTerm];
            if ($request->exists($visibilityColumn)) {
                $columns[$visibilityColumn] = $request->boolean($visibilityColumn);
            }
        }

        $features = [];
        foreach ((array) $request->input('features', []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            // One field holds the WHOLE feature line of the row for the
            // feature term ("10 GB NVMe storage") — no separate name typed.
            $line = trim((string) ($row['line'] ?? ($row['line_monthly'] ?? '')));
            if ($line === '') {
                continue; // blank row left by the "add row" helper
            }
            $group = ($row['group'] ?? 'primary') === 'extra' ? HostingPlanFeature::GROUP_EXTRA : HostingPlanFeature::GROUP_PRIMARY;
            $features[] = [
                'billing_term' => $featuresTerm,
                'feature_group' => $group,
                'name' => mb_substr($line, 0, 120),
                'value_monthly' => mb_substr($line, 0, 190),
                'value_yearly' => null,
                'feature_key' => preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', (string) ($row['feature_key'] ?? ''))
                    ? substr((string) $row['feature_key'], 0, 40)
                    : null,
                'is_available' => !empty($row['is_available']),
            ];

            if (count($features) >= 60) {
                break; // sanity ceiling on feature rows per card
            }
        }

        return [$columns, $features, $request->exists('features'), $featuresTerm];
    }

    /**
     * Replace ONE term's feature rows of the card with the submitted order.
     * The other three terms' sets are never touched — a feature edit in the
     * Yearly view cannot leak into Monthly.
     */
    private function syncHostingPlanFeatures(HostingPlan $plan, $features, $featuresTerm)
    {
        HostingPlanFeature::where('hosting_plan_id', $plan->id)
            ->where('billing_term', $featuresTerm)
            ->delete();

        foreach ($features as $row) {
            $plan->features()->create($row);
        }
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin');
        return redirect()->route('admin.login');
    }
}