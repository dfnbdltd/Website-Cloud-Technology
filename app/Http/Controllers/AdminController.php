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

        // Opening the Orders page re-checks every pending transfer with ResellerClub (one
        // call, at most every 5 minutes), so the statuses shown here are real even when
        // the scheduler cron is not installed.
        try {
            if (\Cache::add('transfer-sync.admin-orders', 1, 300)) {
                app(\App\Services\DomainTransferSyncService::class)->syncAllPending();
            }
        } catch (\Throwable $exception) {
            \Log::warning('Transfer status refresh failed on the admin orders page', ['error' => $exception->getMessage()]);
        }

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
                    'purpose' => 'Domain availability, pricing, wallet pre-check and registration, plus SSL certificate selling prices.',
                    'status' => $resellerClubConfigured ? 'Configured' : 'Needs configuration',
                    'endpoints' => [
                        ['method' => 'GET', 'url' => $resellerClubBase . '/products/customer-price.json', 'usage' => 'Fetch registration and renewal prices, and SSL certificate selling prices (sslcert plans).'],
                        ['method' => 'GET', 'url' => $resellerClubBase . '/products/reseller-cost-price.json', 'usage' => 'Fetch actual reseller costs for invoice purchase-price snapshots and the SSL cost shown in admin.'],
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
                        [
                            'name' => 'SSL live pricing (RESELLERCLUB_SSL_LIVE_PRICING)',
                            'status' => config('services.resellerclub.ssl_live_pricing', true) ? 'Enabled' : 'Disabled',
                        ],
                        [
                            'name' => 'SSL plans linked to ResellerClub',
                            'status' => $this->sslLinkedPlanStatus(),
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
            // Manual override (ResellerClub unreachable, or a transfer done outside the API):
            // the items become "transferred" too, so the customer gets the same manage features.
            // Prefer "Sync with ResellerClub", which only records what the registrar reports.
            \Log::notice('Admin manually marked a domain transfer complete', [
                'order' => $order->reference,
                'admin' => (string) data_get($request->session()->get('admin'), 'email'),
                'domains' => $order->items()->where('order_type', 'transfer')->pluck('domain')->all(),
            ]);
            $order->items()->where('order_type', 'transfer')->update([
                'transfer_auth_code' => null,
                'registration_status' => \App\Models\DomainOrderItem::STATUS_TRANSFERRED,
                'transfer_completed_at' => now(),
            ]);
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

    /**
     * Ask ResellerClub how this order's submitted transfers are doing right now.
     * Preferred over marking the order Success by hand: it only ever records
     * what the registrar itself reports.
     */
    public function syncOrderTransfer(Request $request, DomainOrder $order)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        $sync = app(\App\Services\DomainTransferSyncService::class);
        $items = $sync->pendingItems()->where('domain_order_id', $order->id);
        if ($items->isEmpty()) {
            return redirect()->route('admin.orders')->withErrors(['transfer' => 'This order has no submitted transfer waiting on ResellerClub.']);
        }

        $counts = $sync->syncItems($items);
        $summary = collect($counts)->map(function ($count, $outcome) {
            return $count . ' ' . $outcome;
        })->implode(', ');

        if (isset($counts[\App\Services\DomainTransferSyncService::OUTCOME_UNAVAILABLE])) {
            return redirect()->route('admin.orders')->withErrors(['transfer' => 'ResellerClub could not be read just now (' . $summary . '). Check the log and try again.']);
        }

        return redirect()->route('admin.orders')->with('success', 'Transfer status checked with ResellerClub: ' . $summary . '.');
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
     * CMS: hosting plan cards.
     *
     *   Hosting
     *   ├── Local      Shared · VPS · Dedicated
     *   ├── USA        Shared · VPS · Dedicated
     *   └── WordPress
     *
     * One list page per section (region + type) with every card's
     * prices and status in one table, saved with ONE button. Edit /
     * Add open one simple page per card (details, prices, features).
     *
     * Public pages read App\Services\HostingPlanService; nothing here
     * touches checkout, invoices or payments.
     * ---------------------------------------------------------------
     */

    /** Billing terms: key => column / flag / visibility / feature slug / label. */
    private static function hostingTerms()
    {
        return [
            'monthly' => ['column' => 'price_monthly', 'flag' => 'term_monthly_active', 'visibility' => 'term_monthly_visible', 'name' => null, 'slug' => HostingPlanFeature::TERM_MONTHLY, 'label' => 'Monthly'],
            'yearly' => ['column' => 'price_yearly', 'flag' => 'term_yearly_active', 'visibility' => 'term_yearly_visible', 'name' => 'name_yearly', 'slug' => HostingPlanFeature::TERM_YEARLY, 'label' => 'Yearly'],
            'twoYear' => ['column' => 'price_two_year', 'flag' => 'term_two_year_active', 'visibility' => 'term_two_year_visible', 'name' => 'name_two_year', 'slug' => HostingPlanFeature::TERM_TWO_YEAR, 'label' => '2 Years'],
            'threeYear' => ['column' => 'price_three_year', 'flag' => 'term_three_year_active', 'visibility' => 'term_three_year_visible', 'name' => 'name_three_year', 'slug' => HostingPlanFeature::TERM_THREE_YEAR, 'label' => '3 Years'],
        ];
    }

    /** on sale / coming soon / hidden, from a plan's flags for one term. */
    private static function hostingTermStatus(HostingPlan $plan, array $meta)
    {
        if (!$plan->{$meta['visibility']}) {
            return 'hidden';
        }

        return $plan->{$meta['flag']} ? 'on' : 'soon';
    }

    /** Turn a price into automatic term availability. */
    private static function hostingTermColumns(array $meta, $rawPrice)
    {
        $price = null;
        if (is_scalar($rawPrice) && trim((string) $rawPrice) !== '' && is_numeric(trim((string) $rawPrice)) && (float) $rawPrice >= 0 && (float) $rawPrice <= 99999999) {
            $price = round((float) $rawPrice, 2);
        }

        return [
            $meta['column'] => $price,
            $meta['flag'] => $price !== null,
            $meta['visibility'] => true,
        ];
    }

    /** [region, type] from the URL, falling back to Local / Shared. */
    private function adminHostingSection($region, $type)
    {
        $region = is_string($region) ? strtolower($region) : HostingPlan::REGION_LOCAL;
        if (!isset(HostingPlan::REGIONS[$region])) {
            $region = HostingPlan::REGION_LOCAL;
        }
        if ($region === HostingPlan::REGION_WORDPRESS) {
            return [HostingPlan::REGION_WORDPRESS, HostingPlan::TYPE_WORDPRESS];
        }
        $type = in_array($type, HostingPlan::TYPES, true) ? $type : HostingPlan::TYPE_SHARED;

        return [$region, $type];
    }

    private function hostingListRedirect($region, $type)
    {
        return redirect()->route('admin.hosting.plans', ['region' => $region, 'type' => $type]);
    }

    public function hostingPlans(Request $request, $region = HostingPlan::REGION_LOCAL, $type = null)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        [$region, $type] = $this->adminHostingSection($region, $type);

        $counts = [];
        foreach (HostingPlan::TYPED_REGIONS as $r) {
            foreach (HostingPlan::TYPES as $t) {
                $counts[$r][$t] = HostingPlan::query()->inSection($r, $t)->count();
            }
        }
        $counts[HostingPlan::REGION_WORDPRESS][HostingPlan::TYPE_WORDPRESS] = HostingPlan::query()
            ->inSection(HostingPlan::REGION_WORDPRESS, HostingPlan::TYPE_WORDPRESS)->count();

        $plans = HostingPlan::query()
            ->inSection($region, $type)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        $copyFromType = $type === HostingPlan::TYPE_WORDPRESS ? HostingPlan::TYPE_SHARED : $type;

        return view('admin.hosting-plans', [
            'region' => $region,
            'type' => $type,
            'plans' => $plans,
            'terms' => self::hostingTerms(),
            'counts' => $counts,
            'regionReady' => HostingPlan::hasRegionColumn(),
            'canCopy' => $region !== HostingPlan::REGION_LOCAL
                && HostingPlan::hasRegionColumn()
                && $plans->isEmpty()
                && HostingPlan::query()->inSection(HostingPlan::REGION_LOCAL, $copyFromType)->exists(),
            'copyFromType' => $copyFromType,
        ]);
    }

    /** The list page's single Save button: every card's order, visibility, prices and term status. */
    public function saveHostingPlans(Request $request, $region, $type)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        [$region, $type] = $this->adminHostingSection($region, $type);

        $data = $request->validate([
            'rows' => ['nullable', 'array', 'max:100'],
            'rows.*.sort' => ['nullable', 'integer', 'min:0', 'max:999'],
            'rows.*.price' => ['nullable', 'array'],
        ]);

        $rows = (array) ($data['rows'] ?? []);
        $plans = HostingPlan::query()->inSection($region, $type)->whereIn('id', array_keys($rows))->get();

        DB::transaction(function () use ($plans, $rows) {
            foreach ($plans as $plan) {
                $row = $rows[$plan->id];
                $columns = [
                    'sort' => (int) ($row['sort'] ?? $plan->sort),
                    'is_visible' => !empty($row['visible']),
                ];
                foreach (self::hostingTerms() as $key => $meta) {
                    $columns += self::hostingTermColumns(
                        $meta,
                        $row['price'][$key] ?? null
                    );
                }
                $plan->update($columns);
            }
        });

        return $this->hostingListRedirect($region, $type)
            ->with('success', 'Saved ' . $plans->count() . ' card(s). The public page is updated.');
    }

    /** Fill an empty USA / WordPress section from the Local plans (copies are saved HIDDEN so nothing goes live by accident). */
    public function copyHostingPlans(Request $request, $region, $type)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        [$region, $type] = $this->adminHostingSection($region, $type);

        if ($region === HostingPlan::REGION_LOCAL || !HostingPlan::hasRegionColumn()) {
            return $this->hostingListRedirect($region, $type)
                ->with('error', 'Nothing to copy here. Run the region SQL first (database/hosting_regions.sql).');
        }
        if (HostingPlan::query()->inSection($region, $type)->exists()) {
            return $this->hostingListRedirect($region, $type)->with('error', 'This section already has cards, so nothing was copied.');
        }

        $sourceType = $type === HostingPlan::TYPE_WORDPRESS ? HostingPlan::TYPE_SHARED : $type;
        $sources = HostingPlan::query()->inSection(HostingPlan::REGION_LOCAL, $sourceType)->with('features')->orderBy('sort')->orderBy('id')->get();

        DB::transaction(function () use ($sources, $region, $type) {
            foreach ($sources as $source) {
                $copy = $source->replicate(['id', 'created_at', 'updated_at']);
                $copy->region = $region;
                $copy->type = $type;
                $copy->is_visible = false;
                $copy->save();
                foreach ($source->features as $feature) {
                    $clone = $feature->replicate(['id', 'created_at', 'updated_at']);
                    $clone->hosting_plan_id = $copy->id;
                    $clone->save();
                }
            }
        });

        return $this->hostingListRedirect($region, $type)
            ->with('success', 'Copied ' . $sources->count() . ' card(s) from Local ' . HostingPlan::TYPE_LABELS[$sourceType] . '. They are HIDDEN: set the real prices, then tick "Show" and Save.');
    }

    public function createHostingPlan(Request $request, $region, $type)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        [$region, $type] = $this->adminHostingSection($region, $type);

        return view('admin.hosting-plan-form', $this->hostingFormData(null, $region, $type));
    }

    public function editHostingPlan(Request $request, HostingPlan $plan)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        $plan->loadMissing('features');

        return view('admin.hosting-plan-form', $this->hostingFormData($plan, $plan->regionKey(), $plan->type));
    }

    /** Everything the card form needs, including one feature list per billing term. */
    private function hostingFormData($plan, $region, $type)
    {
        $terms = self::hostingTerms();
        $featureRows = [];
        $same = [];
        foreach ($terms as $key => $meta) {
            $rows = $plan ? $plan->features->where('billing_term', $meta['slug']) : collect();
            $same[$key] = false;
            if ($plan && $key !== 'monthly' && $rows->isEmpty()) {
                $same[$key] = true; // not saved yet: inherits Monthly
            }
            $featureRows[$key] = $rows->map(function (HostingPlanFeature $feature) {
                return [
                    'group' => $feature->feature_group === HostingPlanFeature::GROUP_EXTRA ? 'extra' : 'primary',
                    'line' => trim((string) $feature->value_monthly),
                    'feature_key' => $feature->feature_key,
                    'is_available' => (bool) $feature->is_available,
                ];
            })->values()->all();
        }

        return [
            'plan' => $plan,
            'region' => $region,
            'type' => $type,
            'terms' => $terms,
            'featureRows' => $featureRows,
            'same' => $same,
        ];
    }

    public function storeHostingPlan(Request $request)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        [$columns, $features, $same] = $this->validatedHostingPlan($request);

        $plan = DB::transaction(function () use ($columns, $features, $same) {
            $plan = HostingPlan::create($columns);
            $this->syncHostingPlanFeatures($plan, $features, $same);
            return $plan;
        });

        return $this->hostingListRedirect($plan->regionKey(), $plan->type)
            ->with('success', 'Card "' . $plan->name . '" added.' . ($plan->is_visible ? '' : ' It is hidden: tick "Show" and Save to publish it.'));
    }

    public function updateHostingPlan(Request $request, HostingPlan $plan)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        [$columns, $features, $same] = $this->validatedHostingPlan($request, $plan);

        DB::transaction(function () use ($plan, $columns, $features, $same) {
            $plan->update($columns);
            $this->syncHostingPlanFeatures($plan, $features, $same);
        });

        return $this->hostingListRedirect($plan->regionKey(), $plan->type)
            ->with('success', 'Card "' . $plan->name . '" saved. The public page is updated.');
    }

    /** Quick Show / Hide for a whole card from the list page. */
    public function toggleHostingPlanVisibility(Request $request, HostingPlan $plan)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        $plan->update(['is_visible' => !$plan->is_visible]);

        return $this->hostingListRedirect($plan->regionKey(), $plan->type)
            ->with('success', 'Card "' . $plan->name . '" is now ' . ($plan->is_visible ? 'shown' : 'hidden') . '.');
    }

    public function deleteHostingPlan(Request $request, HostingPlan $plan)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        $region = $plan->regionKey();
        $type = $plan->type;
        $name = $plan->name;

        DB::transaction(function () use ($plan) {
            HostingPlanFeature::where('hosting_plan_id', $plan->id)->delete();
            $plan->delete();
        });

        return $this->hostingListRedirect($region, $type)->with('success', 'Card "' . $name . '" deleted.');
    }

    /**
     * SSL plan list with the ResellerClub prices underneath each plan.
     *
     * The admin sees COST, PROFIT % and SELLING per plan/action/tenure; the
     * customer-facing SslPlanService never touches cost or profit, so this is
     * the only place that data appears.
     *
     * Every load drops the two 5-minute price caches so an owner who just
     * changed a margin in the ResellerClub panel sees the new numbers at once.
     * Clearing them is harmless to the domain pages — they simply refetch on
     * their next request.
     */
    public function sslPlans(Request $request)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }

        if (!$this->sslCmsAvailable()) {
            return view('admin.ssl-plans', [
                'plans' => collect(),
                'cmsUnavailable' => 'SSL CMS tables are not available. Run the SSL migration before managing plans.',
                'rcPricing' => [],
                'rcStatus' => 'not_configured',
                'rcLinked' => 0,
                'rcLinkedIds' => [],
                'usdRate' => $this->usdToBdtRate(),
            ]);
        }

        // The admin always sees what ResellerClub says right now (a margin
        // change made in the panel shows up on the next page load). The
        // customer side keeps using the 5-minute cache.
        if ($this->sslCmsAvailable() && config('services.resellerclub.ssl_live_pricing', true)) {
            cache()->forget('resellerclub.pricing.v2');
            cache()->forget('resellerclub.reseller_cost_pricing.v1');
        }

        $plans = SslPlan::with('features')->orderBy('sort')->orderBy('id')->get();

        $linkedIds = [];
        foreach ($plans as $plan) {
            $planId = $plan->resellerclubPlanId();
            if ($planId !== null) {
                $linkedIds[$planId] = $planId;
            }
        }
        $linkedIds = array_values($linkedIds);

        $rcPricing = [];
        $rcStatus = 'not_configured';

        try {
            $client = app(\App\Services\ResellerClubClient::class);
            $configured = $client->isConfigured();

            if ($configured && $linkedIds) {
                // Both endpoints here on purpose: cost is what makes the profit
                // column possible, and this page is admin-only and rarely hit.
                $prices = $client->sslPlanPricing($linkedIds);
                $rcPricing = is_array($prices) ? $prices : [];

                // A result array is not proof: buildSslPricing() still returns a
                // row per requested ID (with empty add/renew) when the API did
                // not know that plan. "ok" therefore means at least one plan
                // actually came back with a selling price.
                $rcStatus = $this->hasAnySslSellingPrice($rcPricing, $linkedIds) ? 'ok' : 'unavailable';
            } elseif ($configured) {
                $rcStatus = 'ok';
            }
        } catch (\Throwable $e) {
            \Log::warning('SSL plan pricing could not be loaded for the admin page', [
                'plan_ids' => $linkedIds,
                'error' => $e->getMessage(),
            ]);
            $rcPricing = [];
            $rcStatus = 'unavailable';
        }

        return view('admin.ssl-plans', [
            'plans' => $plans,
            'cmsUnavailable' => null,
            'rcPricing' => $rcPricing,
            'rcStatus' => $rcStatus,
            'rcLinked' => count($linkedIds),
            'rcLinkedIds' => $linkedIds,
            // Named usdRate, not exchangeRate: the AppServiceProvider view
            // composer only fills $exchangeRate for partials.admin-sidebar, and
            // reusing the name here would be confusing.
            'usdRate' => $this->usdToBdtRate(),
        ]);
    }

    /**
     * Did ResellerClub return a usable selling price for any linked plan?
     *
     * Without this the admin page would claim live pricing is working while
     * every card quietly shows the typed fallback — the most confusing state
     * there is for whoever is trying to debug the API.
     *
     * @param  array  $prices     planId => price rows
     * @param  array  $linkedIds
     * @return bool
     */
    private function hasAnySslSellingPrice(array $prices, array $linkedIds)
    {
        foreach ($linkedIds as $planId) {
            $selling = isset($prices[$planId]['add'][12]['selling'])
                ? $prices[$planId]['add'][12]['selling']
                : null;

            if (is_numeric($selling) && (float) $selling > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * "2 of 4 plans linked" for /admin/apis.
     *
     * Deliberately cheap and side-effect free: no ResellerClub call, and it
     * tolerates an install where the SSL tables or the plan-ID column do not
     * exist yet (the API page must never 500).
     *
     * @return string
     */
    private function sslLinkedPlanStatus()
    {
        if (!Schema::hasTable('ssl_plans') || !Schema::hasColumn('ssl_plans', 'resellerclub_plan_id')) {
            return 'Not available (run the SSL migration)';
        }

        $total = SslPlan::query()->count();
        if ($total === 0) {
            return 'No SSL plans yet';
        }

        $linked = SslPlan::query()->whereNotNull('resellerclub_plan_id')->count();

        return $linked . ' of ' . $total . ' plans linked';
    }

    /**
     * The USD→BDT rate the customer cards will use, for the admin price line.
     * Never throws: a failed rate lookup shows 0 and the view falls back to USD.
     */
    private function usdToBdtRate()
    {
        try {
            return (float) app(ExchangeRateService::class)->usdToBdt()['rate'];
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    public function createSslPlan(Request $request)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }
        if (!$this->sslCmsAvailable()) {
            return redirect()->route('admin.ssl.plans')->withErrors(['ssl_cms' => 'SSL CMS tables are not available. Run the SSL migration before creating plans.']);
        }

        return view('admin.ssl-plan-form', ['plan' => null]);
    }

    public function editSslPlan(Request $request, SslPlan $plan)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }
        if (!$this->sslCmsAvailable()) {
            return redirect()->route('admin.ssl.plans')->withErrors(['ssl_cms' => 'SSL CMS tables are not available. Run the SSL migration before editing plans.']);
        }

        $plan->load('features');
        return view('admin.ssl-plan-form', ['plan' => $plan]);
    }

    public function storeSslPlan(Request $request)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }
        if (!$this->sslCmsAvailable()) {
            return redirect()->route('admin.ssl.plans')->withErrors(['ssl_cms' => 'SSL CMS tables are not available. Run the SSL migration before creating plans.']);
        }

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
        if (!$this->sslCmsAvailable()) {
            return redirect()->route('admin.ssl.plans')->withErrors(['ssl_cms' => 'SSL CMS tables are not available. Run the SSL migration before updating plans.']);
        }

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
        if (!$this->sslCmsAvailable()) {
            return redirect()->route('admin.ssl.plans')->withErrors(['ssl_cms' => 'SSL CMS tables are not available. Run the SSL migration before changing visibility.']);
        }

        $plan->update(['is_visible' => !$plan->is_visible]);
        return redirect()->route('admin.ssl.plans')->with('success', 'SSL plan "' . $plan->name . '" is now ' . ($plan->is_visible ? 'visible' : 'hidden') . '.');
    }

    public function deleteSslPlan(Request $request, SslPlan $plan)
    {
        if (!$request->session()->get('admin')) { return redirect()->route('admin.login'); }
        if (!$this->sslCmsAvailable()) {
            return redirect()->route('admin.ssl.plans')->withErrors(['ssl_cms' => 'SSL CMS tables are not available. Run the SSL migration before deleting plans.']);
        }

        $name = $plan->name;
        DB::transaction(function () use ($plan) {
            SslPlanFeature::where('ssl_plan_id', $plan->id)->delete();
            $plan->delete();
        });

        return redirect()->route('admin.ssl.plans')->with('success', 'SSL plan "' . $name . '" deleted.');
    }

    private function sslCmsAvailable()
    {
        return Schema::hasTable('ssl_plans') && Schema::hasTable('ssl_plan_features');
    }

    /**

     * Validate an SSL plan submission.

     *

     * All three terms are always offered, so there are no per-term flags to

     * read — only the three discount percentages. Badge and the per-feature

     * "available" flag were dropped from the admin UI, so they are no longer

     * accepted here; every feature is shown as included.

     *

     * @return array [<plan columns>, <feature rows>]

     */

    private function validatedSslPlan(Request $request, SslPlan $plan = null)

    {

        $data = $request->validate([

            'plan_key' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9][a-z0-9-]*$/i'],

            'name' => ['required', 'string', 'max:120'],

            'tagline' => ['nullable', 'string', 'max:255'],

            // Price, profit and discounts come from ResellerClub, so the plan
            // must be linked to a ResellerClub product. No price is typed here.
            'resellerclub_plan_id' => ['required', 'integer', 'min:1', 'max:2147483647'],

            'features' => ['nullable', 'array', 'max:60'],

        ], [

            'plan_key.regex' => 'The plan key may only contain letters, numbers and dashes.',

        ]);



        $keyQuery = SslPlan::where('plan_key', $data['plan_key']);

        if ($plan) {

            $keyQuery->where('id', '!=', $plan->id);

        }

        if ($keyQuery->exists()) {

            throw \Illuminate\Validation\ValidationException::withMessages([

                'plan_key' => 'That SSL plan key is already in use.',

            ]);

        }



        $columns = [

            'plan_key' => strtolower($data['plan_key']),

            'resellerclub_plan_id' => (int) $data['resellerclub_plan_id'],

            'name' => $data['name'],

            'tagline' => $data['tagline'] ?? null,

            'is_visible' => $request->boolean('is_visible'),

            'sort' => (int) $request->input('sort', 0),

        ];



        $features = [];

        // Per-row visibility. `is_available` and the visibility flag move
        // together so the two meanings cannot drift apart: a hidden row is also
        // not "available", which is what every older reader of this table
        // (and the config fallback) understands.
        $hasVisibilityColumn = Schema::hasColumn('ssl_plan_features', 'shown_to_customers');

        foreach ((array) $request->input('features', []) as $feature) {

            if (!is_array($feature)) {

                continue;

            }



            $text = trim((string) ($feature['text'] ?? ''));

            if ($text === '') {

                // Deleting a row in the editor simply removes it from the POST,
                // so a blank row is a delete: it is not carried over.
                continue;

            }



            // A checkbox posts only when checked, so a lone "shown" key cannot
            // tell "unchecked" apart from "not posted at all". The editor sends
            // a `shown_marker` sentinel beside it:
            //   shown=1                     -> visible
            //   shown_marker=1, no shown    -> explicitly hidden
            //   neither                     -> no visibility info (an older
            //                                  form, or a script): keep it
            //                                  visible, which is the behaviour
            //                                  every feature had before this
            //                                  column existed.
            if (array_key_exists('shown', $feature)) {
                $shown = !empty($feature['shown']);
            } elseif (!empty($feature['shown_marker'])) {
                $shown = false;
            } else {
                $shown = true;
            }



            $row = [

                'feature' => mb_substr($text, 0, 190),

                'is_available' => $shown,

                'sort' => count($features) + 1,

            ];



            if ($hasVisibilityColumn) {

                $row['shown_to_customers'] = $shown;

            }



            $features[] = $row;

        }



        // An install that has not run the 2026_11_13 migration has no such
        // column, so writing it would throw. Drop it and keep saving the rest.
        if (!Schema::hasColumn('ssl_plans', 'resellerclub_plan_id')) {
            unset($columns['resellerclub_plan_id']);
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
     * Card form validation (add and edit share it).
     *
     * Form fields: region, type, name, description, badge, style_class, sort,
     * is_visible, names[term], price[term],
     * same[term] ("same as Monthly"), features[termSlug][i][line|group|is_available|feature_key].
     *
     * @return array [<plan columns>, <features by term slug>, <same-as-monthly by term slug>]
     */
    private function validatedHostingPlan(Request $request, HostingPlan $existing = null)
    {
        $data = $request->validate([
            'region' => ['required', Rule::in(array_keys(HostingPlan::REGIONS))],
            'type' => ['nullable', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'badge' => ['nullable', 'string', 'max:60'],
            'style_class' => ['required', Rule::in(array_keys(HostingPlan::STYLE_CLASSES))],
            'sort' => ['required', 'integer', 'min:0', 'max:999'],
            'names' => ['nullable', 'array'],
            'price' => ['nullable', 'array'],
        ]);

        $section = HostingPlan::normalizeSection($data['region'], $data['type'] ?? null);
        if ($section === null) {
            throw \Illuminate\Validation\ValidationException::withMessages(['type' => 'Pick Shared, VPS or Dedicated for this region.']);
        }
        [$region, $type] = $section;

        $columns = [
            'type' => $type,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'badge' => $data['badge'] ?? null,
            'style_class' => $data['style_class'],
            'sort' => (int) $data['sort'],
            'is_visible' => $request->boolean('is_visible'),
        ];
        if (HostingPlan::hasRegionColumn()) {
            $columns['region'] = $region;
        }

        foreach (self::hostingTerms() as $key => $meta) {
            $columns += self::hostingTermColumns($meta, $request->input('price.' . $key));
            if ($meta['name'] !== null) {
                $own = trim((string) $request->input('names.' . $key, ''));
                $columns[$meta['name']] = $own !== '' && $own !== $data['name'] ? mb_substr($own, 0, 120) : null;
            }
        }

        $features = [];
        $same = [];
        foreach (self::hostingTerms() as $key => $meta) {
            $slug = $meta['slug'];
            $same[$slug] = $slug !== HostingPlanFeature::TERM_MONTHLY && $request->boolean('same.' . $key);
            $features[$slug] = [];
            foreach ((array) $request->input('features.' . $key, []) as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $line = trim((string) ($row['line'] ?? ''));
                if ($line === '') {
                    continue; // blank row left by the "add row" helper
                }
                $features[$slug][] = [
                    'billing_term' => $slug,
                    'feature_group' => ($row['group'] ?? 'primary') === 'extra' ? HostingPlanFeature::GROUP_EXTRA : HostingPlanFeature::GROUP_PRIMARY,
                    'name' => mb_substr($line, 0, 120),
                    'value_monthly' => mb_substr($line, 0, 190),
                    'value_yearly' => null,
                    'feature_key' => preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', (string) ($row['feature_key'] ?? ''))
                        ? substr((string) $row['feature_key'], 0, 40)
                        : null,
                    'is_available' => !empty($row['is_available']),
                ];
                if (count($features[$slug]) >= 60) {
                    break; // sanity ceiling on feature rows per term
                }
            }
        }

        return [$columns, $features, $same];
    }

    /**
     * Save the feature rows of every term the form posted. "Same as Monthly"
     * deletes that term's own rows (the public page then inherits Monthly).
     * A term whose form section was not posted at all is left untouched.
     */
    private function syncHostingPlanFeatures(HostingPlan $plan, array $features, array $same)
    {
        foreach ($features as $slug => $rows) {
            if (!empty($same[$slug])) {
                HostingPlanFeature::where('hosting_plan_id', $plan->id)->where('billing_term', $slug)->delete();
                continue;
            }
            HostingPlanFeature::where('hosting_plan_id', $plan->id)->where('billing_term', $slug)->delete();
            foreach ($rows as $row) {
                $plan->features()->create($row);
            }
        }
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin');
        return redirect()->route('admin.login');
    }
}
