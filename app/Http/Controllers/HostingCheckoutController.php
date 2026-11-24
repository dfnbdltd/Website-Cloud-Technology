<?php

namespace App\Http\Controllers;

use App\Models\HostingOrder;
use App\Models\HostingPlan;
use App\Services\HostingDomainCheckService;
use App\Services\HostingOrderService;
use App\Services\InvoiceService;
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
 * Place order (bKash) saves the order + an unpaid invoice and sends the buyer
 * to the bKash page (HostingBkashController). Nagad is not connected yet.
 *
 * The cart stores only region + type + plan id + period + domain. Every price
 * shown is recomputed by HostingOrderService::quote().
 */
class HostingCheckoutController extends Controller
{
    const CART_KEY = 'hosting_cart';

    private $orders;
    private $domainCheck;

    public function __construct(HostingOrderService $orders, HostingDomainCheckService $domainCheck)
    {
        $this->orders = $orders;
        $this->domainCheck = $domainCheck;
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

        // A domain already checked for this very plan stays ticked when the page is reopened.
        $verified = $sameChoice && !empty($cart['domain_checked']) && !empty($cart['domain']);

        return view('hosting.connect', [
            'quote' => $quote,
            'domain' => old('domain', $sameChoice ? ($cart['domain'] ?? '') : ''),
            'verified' => $verified && old('domain') === null,
        ]);
    }

    /**
     * AJAX: the "Check" button. Is the typed domain registered, so a hosting
     * plan can be connected to it? Same ResellerClub lookup as the domain search.
     */
    public function checkDomain(Request $request)
    {
        $data = $request->validate(['domain' => ['required', 'string', 'max:300']]);

        return response()->json($this->domainCheck->check($data['domain']));
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

        // Checked again here: the tick in the browser is only feedback, this is the proof.
        $check = $this->domainCheck->check($data['domain']);
        if (!$check['ok']) {
            return back()->withInput()->withErrors(['domain' => $check['message']]);
        }
        $domain = $check['domain'];

        $request->session()->put(self::CART_KEY, [
            'region' => $quote['region'],
            'type' => $quote['type'],
            'plan' => $quote['plan_id'],
            'period' => $quote['period'],
            'domain' => $domain,
            'domain_checked' => true,
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

        // The domain must have passed the "is it registered" check (storeDomain sets the flag).
        if (empty($cart['domain_checked'])) {
            return redirect()->route('hosting.connect', [
                'region' => $quote['region'],
                'type' => $quote['type'],
                'plan' => $quote['plan_id'],
                'period' => $quote['period'],
            ]);
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

    /**
     * Step 1 - Place order. Saves the order + unpaid invoice, then goes to the
     * bKash payment page. The price is re-derived here, never read from the form.
     */
    public function placeOrder(Request $request)
    {
        $user = $this->currentUser($request);
        if (!$user) {
            return redirect()->route('login', ['return_to' => route('hosting.checkout', [], false)]);
        }

        $cart = $this->cart($request);
        $quote = $cart
            ? $this->orders->quote($cart['region'], $cart['type'], $cart['plan'], $cart['period'])
            : null;
        if (!$cart || !$quote || empty($cart['domain_checked'])) {
            $request->session()->forget(self::CART_KEY);

            return $this->backToPlans($cart['region'] ?? null, $cart['type'] ?? null);
        }

        $buyingForCustomer = $request->input('purchase_for') === 'customer';
        $data = $request->validate([
            'purchase_for' => ['required', 'in:myself,customer'],
            'customer_identifier' => $buyingForCustomer ? ['required', 'string', 'max:255'] : ['nullable'],
            'payment_method' => ['required', 'in:' . implode(',', HostingOrderService::PAYMENT_METHODS)],
        ], [
            'customer_identifier.required' => 'Enter the email or ID of the customer you are buying for.',
            'payment_method.required' => 'Choose bKash to continue.',
            'payment_method.in' => $request->input('payment_method') === 'nagad'
                ? 'Nagad is coming soon. Please pay with bKash.'
                : 'Choose bKash to continue.',
        ]);

        // The owner: you, or the existing customer you are buying for.
        $owner = $user;
        if ($buyingForCustomer) {
            $owner = CustomerLookup::find($data['customer_identifier']);
            if (!$owner) {
                return back()->withInput()->withErrors(['customer_identifier' => CustomerLookup::NOT_FOUND]);
            }
        }

        // An earlier unpaid attempt from this session is replaced, not left dangling.
        $previousId = $request->session()->get(HostingBkashController::ORDER_KEY);
        if ($previousId) {
            $previous = HostingOrder::where('id', $previousId)->where('placed_by_user_id', $user->id)->first();
            if ($previous && !$previous->isPaid() && !$previous->isCancelled()) {
                app(InvoiceService::class)->cancelHostingOrder($previous);
            }
        }

        $order = $this->orders->place($quote, $cart['domain'], $owner, $user, $data['payment_method']);

        $request->session()->put(HostingBkashController::ORDER_KEY, $order->id);

        return redirect()->route('hosting.bkash.pay');
    }

    /** The saved order (the owner or the person who placed it only). */
    public function order(Request $request, $reference)
    {
        $user = $this->currentUser($request);

        $order = HostingOrder::where('reference', $reference)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)->orWhere('placed_by_user_id', $user->id);
            })
            ->firstOrFail();

        // The invoice page only opens for the account that owns the invoice.
        $invoice = app(InvoiceService::class)->findForSource(HostingOrder::class, $order->id);
        if ($invoice && (int) $invoice->user_id !== (int) $user->id) {
            $invoice = null;
        }

        return view('hosting.order', ['order' => $order, 'invoice' => $invoice]);
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
