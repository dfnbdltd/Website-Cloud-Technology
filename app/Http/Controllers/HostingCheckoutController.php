<?php

namespace App\Http\Controllers;

use App\Models\HostingPlan;
use App\Services\HostingOrderService;
use App\Support\CustomerLookup;
use App\User;
use Illuminate\Http\Request;

/**
 * Buying a hosting plan:
 *
 *   plan card "Choose this plan" →  /hosting/connect   (which domain name?)
 *                                →  /hosting/checkout  (guest: sign in · customer: who it is for + payment)
 *
 * Both pages are public so a guest sees the real BDT price before being asked
 * to sign in; the sign-in sends the buyer straight back to /hosting/checkout
 * (the same return_to mechanism domain and SSL checkout use). The chosen
 * plan/period/domain waits in session('hosting_cart').
 *
 * PAYMENT IS NOT CONNECTED YET. The checkout page is complete, but "Place
 * order" is locked ("Payment coming soon") and there is deliberately no order
 * POST route: nothing is saved or charged until the gateway is built.
 *
 * The cart stores only region + type + plan id + period + domain. Every price
 * shown is recomputed by HostingOrderService::quote().
 */
class HostingCheckoutController extends Controller
{
    const CART_KEY = 'hosting_cart';

    private $orders;

    public function __construct(HostingOrderService $orders)
    {
        $this->orders = $orders;
    }

    /** Step 1 — which domain name is the plan for? */
    public function connect(Request $request)
    {
        $quote = $this->orders->quote(
            $request->query('region'),
            $request->query('type'),
            $request->query('plan'),
            $request->query('period', 'monthly')
        );

        if (!$quote) {
            return $this->backToPlans($request->query('region'), $request->query('type'));
        }

        $cart = $request->session()->get(self::CART_KEY, []);
        $sameChoice = ($cart['region'] ?? null) === $quote['region']
            && ($cart['type'] ?? null) === $quote['type']
            && (string) ($cart['plan'] ?? '') === $quote['plan_id']
            && ($cart['period'] ?? null) === $quote['period'];

        return view('hosting.connect', [
            'quote' => $quote,
            'domain' => old('domain', $sameChoice ? ($cart['domain'] ?? '') : ''),
        ]);
    }

    public function storeDomain(Request $request)
    {
        $data = $request->validate([
            'region' => ['required', 'string', 'max:20'],
            'type' => ['required', 'string', 'max:20'],
            'plan' => ['required', 'string', 'max:80'],
            'period' => ['required', 'string', 'max:20'],
            'domain' => ['required', 'string', 'max:300'],
        ]);

        $quote = $this->orders->quote($data['region'], $data['type'], $data['plan'], $data['period']);
        if (!$quote) {
            return $this->backToPlans($data['region'], $data['type']);
        }

        $domain = HostingOrderService::normalizeDomain($data['domain']);
        if ($domain === null) {
            return back()->withInput()->withErrors([
                'domain' => 'Enter a valid domain name, like example.com.',
            ]);
        }

        $request->session()->put(self::CART_KEY, [
            'region' => $quote['region'],
            'type' => $quote['type'],
            'plan' => $quote['plan_id'],
            'period' => $quote['period'],
            'domain' => $domain,
        ]);

        return redirect()->route('hosting.checkout');
    }

    /** Step 2 — sign in (guests) or choose who it is for and how to pay. */
    public function checkout(Request $request)
    {
        $cart = $this->cart($request);
        $quote = $cart
            ? $this->orders->quote($cart['region'], $cart['type'], $cart['plan'], $cart['period'])
            : null;

        if (!$cart || !$quote) {
            $request->session()->forget(self::CART_KEY);

            return $this->backToPlans($cart['region'] ?? null, $cart['type'] ?? null);
        }

        $user = $this->currentUser($request);
        $methods = array_intersect_key(config('services.payments.methods', []), array_flip(['bkash', 'nagad']));

        // Keep the confirmed customer visible when the page reloads after an error.
        $verifiedCustomer = null;
        if (old('purchase_for') === 'customer' && old('customer_identifier')) {
            $verifiedCustomer = CustomerLookup::find(old('customer_identifier'));
        }

        return view('hosting.checkout', [
            'quote' => $quote,
            'domain' => $cart['domain'],
            'user' => $user,
            'methods' => $methods,
            'verifiedCustomer' => $verifiedCustomer,
        ]);
    }

    /** AJAX: does this customer exist? (the "Confirm" button) */
    public function lookupCustomer(Request $request)
    {
        $data = $request->validate(['identifier' => ['required', 'string', 'max:255']]);

        $customer = CustomerLookup::find($data['identifier']);
        if (!$customer) {
            return response()->json(['message' => 'Customer does not exist. Check the email or customer ID and try again.'], 404);
        }

        return response()->json([
            'verified' => true,
            'customer' => CustomerLookup::profile($customer),
        ]);
    }

    private function cart(Request $request)
    {
        $cart = $request->session()->get(self::CART_KEY);

        if (!is_array($cart)
            || empty($cart['region']) || empty($cart['type'])
            || empty($cart['plan']) || empty($cart['period']) || empty($cart['domain'])) {
            return null;
        }

        return $cart;
    }

    private function currentUser(Request $request)
    {
        return $request->user() ?: User::find($request->session()->get('customer.id'));
    }

    /** Back to the plan list the buyer came from (or the Local shared page). */
    private function backToPlans($region, $type)
    {
        $section = HostingPlan::normalizeSection((string) $region, (string) $type)
            ?: [HostingPlan::REGION_LOCAL, HostingPlan::TYPE_SHARED];

        return redirect(HostingOrderService::plansUrl($section[0], $section[1]));
    }
}
