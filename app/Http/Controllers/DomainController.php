<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Services\PaymentGatewayManager;
use App\Services\InvoiceService;
use App\Services\Notifier;
use App\Services\ResellerClubClient;
use App\Services\DomainRegistrationService;
use App\Services\DomainTransferService;
use App\Models\DomainOrder;
use App\Models\DomainOrderItem;
use App\Models\Payment;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DomainController extends Controller
{
    private $resellerClub;
    private $payments;

    public function __construct(ResellerClubClient $resellerClub, PaymentGatewayManager $payments)
    {
        $this->resellerClub = $resellerClub;
        $this->payments = $payments;
    }

    public function index(Request $request)
    {
        return view('domains.index', [
            'query' => old('domain', $request->query('domain', '')),
            'results' => $request->session()->get('domain_results', []),
            'cart' => $request->session()->get('domain_cart', []),
            'plans' => $this->resellerClub->catalog(),
            'liveSource' => $this->resellerClub->isConfigured() && !empty($this->resellerClub->pricing()),
        ]);
    }

    public function search(Request $request)
    {
        $data = $request->validate(['domain' => ['required', 'regex:/^[a-z0-9][a-z0-9.-]{1,62}$/i']]);
        $parts = explode('.', strtolower(trim($data['domain'])), 2);
        $name = $parts[0];
        $requestedTld = isset($parts[1]) ? $parts[1] : null;
        $domain = strtolower(trim($data['domain']));
        $testDomains = config('services.test_domains', []);
        if (isset($testDomains[$domain])) {
            $results = [[
                'domain' => $domain,
                'tld' => 'test',
                'available' => true,
                'price' => $testDomains[$domain]['price'],
                'renewal' => $testDomains[$domain]['renewal'],
                'year_prices' => [1 => (float) $testDomains[$domain]['price']],
                'classkey' => 'test',
                'source' => 'test',
            ]];
            $request->session()->put('domain_results', $results);

            return redirect()->route('domains.index', ['domain' => $data['domain']]);
        }

        $catalog = $this->resellerClub->catalog();

        // Search all catalog TLDs, or just the requested one when it is in the catalog.
        if ($requestedTld && isset($catalog[$requestedTld])) {
            $tldList = [$requestedTld => $catalog[$requestedTld]];
        } else {
            $tldList = $catalog;
        }

        $results = [];
        foreach ($tldList as $tld => $plan) {
            $remote = $this->resellerClub->checkAvailability($name, $tld);
            $entry = is_array($remote) ? ($remote[$name . '.' . $tld] ?? null) : null;
            $status = is_array($entry) ? strtolower($entry['status'] ?? '') : '';
            // null = the lookup failed (API error / IP not whitelisted), not "taken".
            $available = $status === '' ? null : in_array($status, ['available', 'notinuse'], true);

            // If the classkey differs from the catalog, pull its price live.
            $price = $plan['price'];
            $renewal = $plan['renewal'];
            $yearPrices = $plan['year_prices'];
            if (!empty($entry['classkey']) && $entry['classkey'] !== $plan['classkey']) {
                $pricing = $this->resellerClub->pricesForClasskey($entry['classkey'], [
                    'add' => $price,
                    'renew' => $renewal,
                ]);
                $price = $pricing['price'];
                $renewal = $pricing['renewal'];
                $yearPrices = $pricing['year_prices'];
            }

            $results[] = [
                'domain' => $name . '.' . $tld,
                'tld' => $tld,
                'available' => $available,
                'price' => $price,
                'renewal' => $renewal,
                'year_prices' => $yearPrices,
                'classkey' => $plan['classkey'],
                'source' => $status ? 'resellerclub' : 'catalog',
            ];
        }

        $request->session()->put('domain_results', $results);

        return redirect()->route('domains.index', ['domain' => $data['domain']]);
    }

    public function addToCart(Request $request)
    {
        $data = $request->validate([
            'domain' => ['required', 'regex:/^[a-z0-9][a-z0-9.-]{1,62}$/i'],
            'years' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        // Server-side price check: never trust the posted price.
        $parts = explode('.', strtolower($data['domain']));
        $tld = end($parts);
        $domain = strtolower($data['domain']);
        $testDomains = config('services.test_domains', []);
        if (isset($testDomains[$domain])) {
            $yearPrice = (float) $testDomains[$domain]['price'];
            $firstYearPrice = $yearPrice;
            $renewalPrice = (float) $testDomains[$domain]['renewal'];
            $years = 1;
        } else {
            $plan = $this->resellerClub->catalog()[$tld] ?? null;
            if (!$plan) {
                return back()->withErrors(['domain' => 'This domain extension is not available for registration.']);
            }
            $years = (int) ($data['years'] ?? 1);
            $termPrice = $plan['year_prices'][$years] ?? null;
            if ($termPrice === null) {
                return back()->withErrors(['domain' => 'Pricing is not available for the selected registration term.']);
            }
            $yearPrice = (float) $termPrice / $years;
            $firstYearPrice = (float) $plan['price'];
            $renewalPrice = (float) $plan['renewal'];
        }

        $cart = $request->session()->get('domain_cart', []);
        if (collect($cart)->contains(function ($item) {
            return ($item['order_type'] ?? 'registration') === 'transfer';
        })) {
            return back()->withErrors(['domain' => 'Domain transfers must be checked out separately from domain registrations.']);
        }
        $cart[$domain] = [
            'domain' => $domain,
            'order_type' => 'registration',
            'price' => $yearPrice,
            'purchase_price' => $yearPrice,
            'first_year_price' => $firstYearPrice,
            'renewal_price' => $renewalPrice,
            'years' => $years,
        ];
        $request->session()->put('domain_cart', $cart);

        return redirect()->route('domains.checkout')->with('cart_added', $domain);
    }

    public function startTransfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'domain' => ['required', 'string', 'max:253', 'regex:/^[a-z0-9][a-z0-9.-]*[a-z0-9]$/i'],
            'auth_code' => ['required', 'string', 'max:255'],
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->except('auth_code'));
        }
        $data = $validator->validated();

        $domain = strtolower(trim($data['domain']));
        if (!preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+(?:[a-z]{2,63}|xn--[a-z0-9-]{2,59})$/i', $domain)) {
            return back()->withInput($request->except('auth_code'))->withErrors(['domain' => 'Enter a valid domain name, including its extension.']);
        }

        $tld = substr(strrchr($domain, '.'), 1);
        $isCountryCodeTld = preg_match('/^[a-z]{2}$/', $tld) === 1;
        if (!$isCountryCodeTld
            && !preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/', trim($data['auth_code']))) {
            return back()->withInput($request->except('auth_code'))->withErrors([
                'auth_code' => 'For this domain extension, the authorization code must contain a letter, a number, and a special character.',
            ]);
        }

        $cart = $request->session()->get('domain_cart', []);
        if (collect($cart)->contains(function ($item) {
            return ($item['order_type'] ?? 'registration') !== 'transfer';
        })) {
            return back()->withInput($request->except('auth_code'))->withErrors(['domain' => 'Domain transfers must be checked out separately from domain registrations.']);
        }

        $eligible = $this->resellerClub->validateDomainTransferIn($domain);
        if ($eligible === null) {
            return back()->withInput($request->except('auth_code'))->withErrors([
                'domain' => 'We could not verify this domain’s transfer eligibility. No payment has been taken; please try again shortly.',
            ]);
        }
        if (!$eligible) {
            return back()->withInput($request->except('auth_code'))->withErrors([
                'domain' => 'This domain is not eligible for transfer. Disable its transfer lock at the current registrar and check the 60-day transfer rule.',
            ]);
        }

        $price = $this->resellerClub->liveTransferPrice($tld);
        if ($price === null) {
            return back()->withInput($request->except('auth_code'))->withErrors(['domain' => 'A live transfer price is not available for this domain extension. Please contact support.']);
        }

        $cart[$domain] = [
            'domain' => $domain,
            'order_type' => 'transfer',
            'price' => $price,
            'purchase_price' => $price,
            'first_year_price' => $price,
            'renewal_price' => $price,
            'years' => 1,
            'transfer_auth_code' => Crypt::encryptString(trim($data['auth_code'])),
        ];
        $request->session()->put('domain_cart', $cart);

        return redirect()->route('domains.checkout')->with('cart_added', $domain);
    }

    /**
     * Stand-alone cart page (the cart no longer lives beside the search).
     */
    public function cart(Request $request)
    {
        $cart = $request->session()->get('domain_cart', []);

        return view('cart.index', ['cart' => $cart, 'total' => $this->total($cart)]);
    }

    public function removeFromCart(Request $request)
    {
        $domain = strtolower((string) $request->input('domain'));
        $cart = $request->session()->get('domain_cart', []);
        unset($cart[$domain]);
        $request->session()->put('domain_cart', $cart);

        return redirect()->route('cart.index')->with('success', $domain . ' was removed from your cart.');
    }

    public function lookupCustomer(Request $request)
    {
        $data = $request->validate(['identifier' => ['required', 'string', 'max:255']]);
        $identifier = trim($data['identifier']);
        $customerQuery = User::whereNotNull('email_verified_at');
        $customer = filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? $customerQuery->where('email', $identifier)->first()
            : $customerQuery->where('customer_uid', $identifier)->first();

        if (!$customer) {
            return response()->json(['message' => 'No customer account was found for that email.'], 404);
        }

        return response()->json(['verified' => true]);
    }

    public function checkout(Request $request)
    {
        $this->cancelAbandonedBkashCheckout($request);
        $cart = $request->session()->get('domain_cart', []);
        if (!$cart) {
            return redirect()->route('cart.index')->withErrors(['domain' => 'Add a domain before checkout.']);
        }

        $user = $request->user() ?: User::find(session('customer.id'));
        $accountDetails = $this->accountDetails($request, $user);
        $accountDetailsComplete = !empty($accountDetails['name'])
            && !empty($accountDetails['email']);
        $methods = $this->payments->methods();
        $defaultPaymentMethod = key($methods);
        if ($user) {
            $methods = ['wallet' => [
                'label' => 'Wallet balance · ৳ ' . number_format((float) $user->balance, 2),
                'description' => 'Pay using your available account balance',
            ]] + $methods;
            if ((float) $user->balance >= $this->total($cart)) {
                $defaultPaymentMethod = 'wallet';
            }
        }

        return view('domains.checkout', [
            'cart' => $cart,
            'total' => $this->total($cart),
            'methods' => $methods,
            'gatewayReady' => $this->payments->gatewayConfigured('bkash'),
            'accountDetails' => $accountDetails,
            'accountDetailsComplete' => $accountDetailsComplete,
            'defaultPaymentMethod' => $defaultPaymentMethod,
        ]);
    }

    public function placeOrder(Request $request)
    {
        $user = $request->user() ?: User::find(session('customer.id'));

        $cart = $request->session()->get('domain_cart', []);
        if (!$cart) {
            return redirect()->route('domains.index')->withErrors(['domain' => 'Your cart is empty.']);
        }
        $hasTransfers = collect($cart)->contains(function ($item) {
            return ($item['order_type'] ?? 'registration') === 'transfer';
        });
        if ($hasTransfers && collect($cart)->contains(function ($item) {
            return ($item['order_type'] ?? 'registration') !== 'transfer';
        })) {
            return redirect()->route('cart.index')->withErrors(['domain' => 'Domain transfers must be checked out separately from domain registrations.']);
        }

        if ($hasTransfers) {
            $transferError = app(DomainTransferService::class)->preflightError($cart);
            if ($transferError) {
                return back()->withErrors(['payment_method' => $transferError]);
            }
        }
        $allowedMethods = array_keys($this->payments->methods());
        if ($user) {
            array_unshift($allowedMethods, 'wallet');
        }
        $purchaseFor = $request->input('purchase_for');
        $accountDetails = $this->accountDetails($request, $user);
        $useAccountDetails = $purchaseFor === 'myself'
            && (bool) session('customer')
            && !empty($accountDetails['name'])
            && !empty($accountDetails['email']);
        $buyingForCustomer = $purchaseFor === 'customer';
        $data = $request->validate([
            'payment_method' => ['required', 'in:' . implode(',', $allowedMethods)],
            'full_name' => $useAccountDetails || $buyingForCustomer ? ['nullable'] : ['required', 'string', 'max:120'],
            'email' => $useAccountDetails || $buyingForCustomer ? ['nullable'] : ['required', 'email', 'max:255'],
            'phone' => $useAccountDetails || $buyingForCustomer ? ['nullable'] : ['required', 'string', 'max:30'],
            'purchase_for' => ['required', 'in:myself,customer'],
            'customer_identifier' => $buyingForCustomer ? ['required', 'string', 'max:255'] : ['nullable'],
        ]);
        if ($useAccountDetails) {
            $data['full_name'] = $accountDetails['name'];
            $data['email'] = $accountDetails['email'];
            $data['phone'] = $accountDetails['phone'];
        } elseif ($buyingForCustomer) {
            $identifier = trim($data['customer_identifier']);
            $customerQuery = User::whereNotNull('email_verified_at');
            $selectedCustomer = filter_var($identifier, FILTER_VALIDATE_EMAIL)
                ? $customerQuery->where('email', $identifier)->first()
                : $customerQuery->where('customer_uid', $identifier)->first();
            if (!$selectedCustomer) {
                return back()->withInput()->withErrors(['customer_identifier' => 'No verified customer account was found for that email or ID.']);
            }
            $data['full_name'] = $selectedCustomer->name;
            $data['email'] = $selectedCustomer->email;
            $data['phone'] = (string) $selectedCustomer->phone;
            $orderUser = $selectedCustomer;
        } else {
            $orderUser = $user;
        }
        $data['customer_email'] = $data['email'];

        $walletError = app(DomainRegistrationService::class)->walletPreflightError($cart);
        if ($walletError) {
            return back()->withInput()->withErrors(['payment_method' => $walletError]);
        }

        $safeItems = array_values($cart);
        foreach ($safeItems as &$safeItem) {
            unset($safeItem['transfer_auth_code']);
        }
        unset($safeItem);

        $order = [
            'reference' => 'CT-' . date('Ymd') . '-' . strtoupper(substr(sha1(uniqid('', true)), 0, 6)),
            'items' => $safeItems,
            'total' => $this->total($cart),
            'customer' => $data,
            'created_at' => date('c'),
        ];
        $order['payment'] = $this->payments->createPendingPayment($order, $data['payment_method']);
        $isWalletPayment = $data['payment_method'] === 'wallet';
        $orderUser = $orderUser ?? $user;
        $persisted = DB::transaction(function () use (&$order, $data, $cart, $user, $orderUser, $isWalletPayment) {
            if ($isWalletPayment) {
                $walletUser = $user ? User::whereKey($user->id)->lockForUpdate()->first() : null;
                if (!$walletUser || (float) $walletUser->balance < (float) $order['total']) {
                    return null;
                }
                $walletUser->forceFill([
                    'balance' => (float) $walletUser->balance - (float) $order['total'],
                ])->save();
                $order['payment']['status'] = 'paid';
                $order['payment']['message'] = 'Paid from wallet balance.';
            }

            $model = DomainOrder::create([
                'reference' => $order['reference'],
                'user_id' => $orderUser ? $orderUser->id : null,
                'total' => $order['total'],
                'status' => $isWalletPayment ? 'paid' : 'payment_pending',
                'payment_method' => $data['payment_method'],
                'payment_status' => $isWalletPayment ? 'paid' : 'pending',
                'provider' => $data['payment_method'] === 'bkash' ? 'bkash' : ($isWalletPayment ? 'wallet' : 'manual'),
                'customer_name' => $data['full_name'],
                'customer_phone' => $data['phone'],
                'customer_email' => $data['customer_email'],
                'payload' => json_encode($order),
            ]);
            foreach ($cart as $item) {
                $parts = explode('.', $item['domain']);
                DomainOrderItem::create([
                    'domain_order_id' => $model->id,
                    'domain' => $item['domain'],
                    'tld' => end($parts),
                    'years' => $item['years'],
                    'price' => $item['price'],
                    'renewal_price' => $item['renewal_price'] ?? $item['price'],
                    'purchase_price' => $item['purchase_price'] ?? $item['price'],
                    'order_type' => $item['order_type'] ?? 'registration',
                    'transfer_auth_code' => $item['transfer_auth_code'] ?? null,
                ]);
            }
            Payment::create([
                'domain_order_id' => $model->id,
                'reference' => $order['payment']['reference'],
                'provider' => $order['payment']['method'],
                'amount' => $order['total'],
                'status' => $isWalletPayment ? 'paid' : 'pending',
            ]);

            return $model;
        });
        if (!$persisted) {
            return back()->withErrors(['payment_method' => 'Your wallet balance is not enough to pay for this order.']);
        }

        // The invoice mirrors the order: paid for instant payments (wallet),
        // unpaid for bKash / Nagad / bank until the payment completes.
        app(InvoiceService::class)->createForOrder($persisted->fresh());

        $order['database_id'] = $persisted->id;
        $request->session()->put('last_domain_order', $order);
        $request->session()->forget('domain_cart');

        if ($isWalletPayment) {
            app(DomainRegistrationService::class)->registerPaidOrder($persisted->fresh());
            $persisted = $persisted->fresh();
            $request->session()->put('customer.balance', (float) $user->fresh()->balance);
            Notifier::send($persisted->customer_email, new InvoiceMail($persisted->fresh()));

            return view('domains.confirmation', [
                'order' => $order,
                'paid' => true,
                'registrationPending' => $persisted->status === 'registration_pending',
                'isTransfer' => $hasTransfers,
                'transferPending' => $persisted->status === 'transfer_pending',
                'transferFailed' => $persisted->status === 'transfer_failed',
            ]);
        }

        if ($data['payment_method'] === 'bkash') {
            if (!$this->payments->gatewayConfigured('bkash')) {
                return view('domains.confirmation', [
                    'order' => $order,
                    'gatewayWarning' => 'bKash gateway credentials are not configured on this server yet. '
                        . 'Your order is saved and our billing team will contact you with payment instructions for reference ' . $order['reference'] . '.',
                ]);
            }

            return redirect()->route('bkash.pay', ['invoice' => $order['reference']]);
        }

        return view('domains.confirmation', ['order' => $order]);
    }

    /**
     * Renderable order confirmation page (also the bKash popup success target).
     */
    public function confirmation(Request $request, $reference)
    {
        $order = $request->session()->get('last_domain_order');
        $user = $request->user() ?: User::find(session('customer.id'));
        $query = DomainOrder::with(['items', 'payments'])->where('reference', $reference);
        if ($user) {
            $query->where('user_id', $user->id);
        }
        $databaseOrder = $query->first();

        if (!$order || ($order['reference'] ?? null) !== $reference) {
            if (!$user || !$databaseOrder) {
                return redirect()->route('domains.index')->withErrors(['payment' => 'Order not found in this session.']);
            }

            $order = [
                'reference' => $databaseOrder->reference,
                'items' => $databaseOrder->items->map(function ($item) {
                    return [
                        'domain' => $item->domain,
                        'years' => (int) $item->years,
                        'price' => (float) $item->price,
                    ];
                })->all(),
                'total' => (float) $databaseOrder->total,
                'customer' => ['payment_method' => $databaseOrder->payment_method],
                'payment' => [
                    'method' => $databaseOrder->payment_method,
                    'status' => $databaseOrder->payment_status,
                    'transaction_id' => $databaseOrder->provider_payment_id,
                ],
                'database_id' => $databaseOrder->id,
            ];
        } elseif ($user && (!$databaseOrder || (int) $databaseOrder->user_id !== (int) $user->id)) {
            return redirect()->route('domains.index')->withErrors(['payment' => 'Order not found in this account.']);
        }

        return view('domains.confirmation', [
            'order' => $order,
            'paid' => $databaseOrder ? $databaseOrder->payment_status === 'paid' : (($order['payment']['status'] ?? null) === 'paid'),
            'registrationPending' => $databaseOrder && $databaseOrder->status === 'registration_pending',
            'isTransfer' => $databaseOrder && $databaseOrder->items->contains(function ($item) {
                return $item->order_type === 'transfer';
            }),
            'transferPending' => $databaseOrder && $databaseOrder->status === 'transfer_pending',
            'transferFailed' => $databaseOrder && $databaseOrder->items->contains(function ($item) {
                return $item->order_type === 'transfer' && $item->registration_status === 'transfer_failed';
            }),
            'transferComplete' => $databaseOrder && $databaseOrder->status === 'active'
                && $databaseOrder->items->contains(function ($item) {
                    return $item->order_type === 'transfer';
                }),
        ]);
    }

    /**
     * Returning to checkout means the customer left the bKash payment flow.
     * Clear that unpaid attempt before the cart redirect can hide the signal.
     * Manual payment orders are intentionally preserved for admin processing.
     */
    private function cancelAbandonedBkashCheckout(Request $request)
    {
        $order = $request->session()->get('last_domain_order');
        if (!$order || ($order['payment']['method'] ?? null) !== 'bkash') {
            return;
        }

        $databaseOrder = isset($order['database_id'])
            ? DomainOrder::find($order['database_id'])
            : null;
        if (!$databaseOrder || $databaseOrder->payment_status === 'paid' || $databaseOrder->status === 'cancelled') {
            return;
        }

        app(InvoiceService::class)->cancelOrder($databaseOrder);
        $request->session()->forget('last_domain_order');
    }

    private function total(array $cart)
    {
        return array_reduce($cart, function ($total, $item) {
            return $total + (float) $item['price'] * (int) $item['years'];
        }, 0);
    }

    private function accountDetails(Request $request, $user)
    {
        $customer = $request->session()->get('customer', []);

        return [
            'name' => (string) ($user ? $user->name : ($customer['name'] ?? '')),
            'email' => (string) ($user ? $user->email : ($customer['email'] ?? '')),
            'phone' => (string) ($user && $user->phone ? $user->phone : ($customer['phone'] ?? '')),
        ];
    }

}
