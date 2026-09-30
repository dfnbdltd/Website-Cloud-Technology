<?php

namespace App\Http\Controllers;

use App\Services\PaymentGatewayManager;
use App\Services\ResellerClubClient;
use App\Models\DomainOrder;
use App\Models\DomainOrderItem;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            if (!empty($entry['classkey']) && $entry['classkey'] !== $plan['classkey']) {
                $live = $this->resellerClub->pricing()[$entry['classkey']] ?? null;
                if ($live) {
                    $price = (float) ($live['addnewdomain']['1'] ?? $price);
                    $renewal = (float) ($live['renewdomain']['1'] ?? $renewal);
                }
            }

            $results[] = [
                'domain' => $name . '.' . $tld,
                'tld' => $tld,
                'available' => $available,
                'price' => $price,
                'renewal' => $renewal,
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
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        // Server-side price check: never trust the posted price.
        $parts = explode('.', strtolower($data['domain']));
        $tld = end($parts);
        $plan = $this->resellerClub->catalog()[$tld] ?? null;
        $price = $plan ? $plan['price'] : (float) $data['price'];

        $cart = $request->session()->get('domain_cart', []);
        $cart[strtolower($data['domain'])] = ['domain' => strtolower($data['domain']), 'price' => $price, 'years' => 1];
        $request->session()->put('domain_cart', $cart);

        return redirect()->back()->with('cart_added', strtolower($data['domain']));
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

    public function checkout(Request $request)
    {
        if (!$this->isCustomer($request)) {
            return redirect()->guest(route('login'))
                ->with('success', 'Please sign in or create an account to complete your purchase. Your cart is saved.');
        }

        $cart = $request->session()->get('domain_cart', []);
        if (!$cart) {
            return redirect()->route('cart.index')->withErrors(['domain' => 'Add a domain before checkout.']);
        }

        $user = $request->user();

        return view('domains.checkout', [
            'cart' => $cart,
            'total' => $this->total($cart),
            'methods' => $this->payments->methods(),
            'gatewayReady' => $this->payments->gatewayConfigured('bkash'),
            // Prefill contact details already on file — editable, not re-asked.
            'contactPhone' => (string) ($user ? $user->phone : session('customer.phone', '')),
        ]);
    }

    public function placeOrder(Request $request)
    {
        // Login is enforced HERE, right before payment.
        if (!$request->user() && !session('customer')) {
            return redirect()
                ->guest(route('login'))
                ->with('success', 'Please sign in or create an account to complete your purchase. Your cart is saved.');
        }

        $user = $request->user();

        $cart = $request->session()->get('domain_cart', []);
        if (!$cart) {
            return redirect()->route('domains.index')->withErrors(['domain' => 'Your cart is empty.']);
        }
        $data = $request->validate([
            'payment_method' => ['required', 'in:bkash,nagad,bank'],
            'full_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $order = [
            'reference' => 'CT-' . date('Ymd') . '-' . strtoupper(substr(sha1(uniqid('', true)), 0, 6)),
            'items' => array_values($cart),
            'total' => $this->total($cart),
            'customer' => $data,
            'created_at' => date('c'),
        ];
        $order['payment'] = $this->payments->createPendingPayment($order, $data['payment_method']);
        $persisted = DB::transaction(function () use ($order, $data, $cart, $user) {
            $model = DomainOrder::create([
                'reference' => $order['reference'],
                'user_id' => $user ? $user->id : session('customer.id'),
                'total' => $order['total'],
                'status' => 'payment_pending',
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'provider' => $data['payment_method'] === 'bkash' ? 'bkash' : 'manual',
                'customer_name' => $data['full_name'],
                'customer_phone' => $data['phone'],
                'customer_email' => $user ? $user->email : session('customer.email'),
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
                    'renewal_price' => $item['price'],
                ]);
            }
            Payment::create([
                'domain_order_id' => $model->id,
                'reference' => $order['payment']['reference'],
                'provider' => $order['payment']['method'],
                'amount' => $order['total'],
                'status' => 'pending',
            ]);

            return $model;
        });
        $order['database_id'] = $persisted->id;
        $request->session()->put('last_domain_order', $order);
        $request->session()->forget('domain_cart');

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
        if (!$order || ($order['reference'] ?? null) !== $reference) {
            return redirect()->route('domains.index')->withErrors(['payment' => 'Order not found in this session.']);
        }

        $databaseOrder = isset($order['database_id']) ? DomainOrder::find($order['database_id']) : null;

        return view('domains.confirmation', [
            'order' => $order,
            'paid' => $databaseOrder ? $databaseOrder->payment_status === 'paid' : (($order['payment']['status'] ?? null) === 'paid'),
        ]);
    }

    private function total(array $cart)
    {
        return array_reduce($cart, function ($total, $item) {
            return $total + (float) $item['price'] * (int) $item['years'];
        }, 0);
    }

    private function isCustomer(Request $request)
    {
        return (bool) ($request->user() || $request->session()->get('customer'));
    }
}
