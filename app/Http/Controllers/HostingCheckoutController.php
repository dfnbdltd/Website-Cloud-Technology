<?php

namespace App\Http\Controllers;

use App\Models\HostingOrder;
use App\Models\HostingPlan;
use App\Services\HostingDomainCheckService;
use App\Services\HostingOrderService;
use App\Services\InvoiceService;
use App\Support\CustomerLookup;
use App\User;
use Illuminate\Http\RedirectResponse;
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
            // Whitelisted: the default for hosting is "I already have a registered domain".
            'mode' => HostingDomainCheckService::normalizeMode(old('mode', $request->query('mode'))),
            // The customer left for the domain register / transfer flow and has not finished yet.
            'pendingDomain' => $sameChoice ? ($cart['pending_domain'] ?? null) : null,
        ]);
    }

    /**
     * AJAX: the "Check" button. Is the typed domain registered, so a hosting
     * plan can be connected to it? Same ResellerClub lookup as the domain search.
     */
    public function checkDomain(Request $request)
    {
        $data = $request->validate([
            'domain' => ['required', 'string', 'max:300'],
            'mode' => ['nullable', 'string', 'in:' . implode(',', HostingDomainCheckService::MODES)],
        ]);

        return response()->json($this->domainCheck->checkForMode($data['mode'] ?? null, $data['domain']));
    }

    public function storeDomain(Request $request)
    {
        $data = $request->validate([
            'region' => ['required', 'string', 'max:20'],
            'type' => ['required', 'string', 'max:20'],
            'plan' => ['required', 'string', 'max:80'],
            'period' => ['required', 'string', 'max:20'],
            'domain' => ['required', 'string', 'max:300'],
            'mode' => ['nullable', 'string', 'in:' . implode(',', HostingDomainCheckService::MODES)],
            'auth_code' => ['nullable', 'string', 'max:255'],
        ]);
        $mode = HostingDomainCheckService::normalizeMode($data['mode'] ?? null);

        $quote = $this->orders->quote($data['region'], $data['type'], $data['plan'], $data['period']);
        if (!$quote) {
            return $this->backToPlans($data['region'], $data['type']);
        }

        // Checked again here: the tick in the browser is only feedback, this is the proof.
        $check = $this->domainCheck->checkForMode($mode, $data['domain']);
        if (!$check['ok']) {
            return back()->withInput($request->except('auth_code'))->withErrors(['domain' => $check['message']]);
        }
        $domain = $check['domain'];

        $cart = [
            'region' => $quote['region'],
            'type' => $quote['type'],
            'plan' => $quote['plan_id'],
            'period' => $quote['period'],
            'domain' => $domain,
            'domain_checked' => $mode === HostingDomainCheckService::MODE_OWN,
        ];

        if ($mode === HostingDomainCheckService::MODE_OWN) {
            $request->session()->put(self::CART_KEY, $cart);

            return redirect()->route('hosting.checkout');
        }

        // Register / transfer: the hosting plan waits in the session while the customer
        // goes through the normal domain flow and pays for it. Two separate payments:
        // hosting checkout re-checks the domain on return (see resumeAfterDomain()).
        if ($mode === HostingDomainCheckService::MODE_TRANSFER && trim((string) ($data['auth_code'] ?? '')) === '') {
            return back()->withInput($request->except('auth_code'))->withErrors(['auth_code' => 'Enter the authorization (EPP) code from your current registrar.']);
        }

        $cart['pending_domain'] = ['mode' => $mode, 'domain' => $domain];
        $request->session()->put(self::CART_KEY, $cart);

        return $this->handOverToDomainFlow($request, $mode, $domain, $data);
    }

    /**
     * Put the domain into the normal domain cart by calling the same DomainController
     * actions the domain pages use (so prices, eligibility and the cart format are the
     * domain flow's own), then land on the domain checkout.
     */
    private function handOverToDomainFlow(Request $request, $mode, $domain, array $data)
    {
        $sub = Request::create(
            $mode === HostingDomainCheckService::MODE_TRANSFER ? route('domains.transfer') : route('domains.cart'),
            'POST',
            $mode === HostingDomainCheckService::MODE_TRANSFER
                ? ['domain' => $domain, 'auth_code' => (string) $data['auth_code']]
                : ['domain' => $domain, 'years' => 1]
        );
        $sub->setLaravelSession($request->session());
        $sub->setUserResolver($request->getUserResolver());

        $controller = app(DomainController::class);
        $response = $mode === HostingDomainCheckService::MODE_TRANSFER
            ? $controller->startTransfer($sub)
            : $controller->addToCart($sub);

        // Success = the domain flow sent us to its checkout. Anything else is a refusal
        // whose message the domain flow already flashed; show it on this page.
        if ($response instanceof RedirectResponse && strpos($response->getTargetUrl(), route('domains.checkout')) === 0) {
            return $response;
        }

        $cart = $request->session()->get(self::CART_KEY, []);
        unset($cart['pending_domain']);
        $request->session()->put(self::CART_KEY, $cart);

        return redirect()->back()->withInput($request->except('auth_code'));
    }

    /**
     * AJAX: the buyer changed the Billing dropdown on checkout. Keeps session('hosting_cart')
     * in sync (so a guest who signs in lands back on the same term) and answers with that
     * term's label / price. The price always comes from the server.
     */
    public function updatePeriod(Request $request)
    {
        $data = $request->validate(['period' => ['required', 'string', 'max:20']]);

        $cart = $this->cart($request);
        $quote = $cart ? $this->orders->quote($cart['region'], $cart['type'], $cart['plan'], $data['period']) : null;
        if (!$cart || !$quote) {
            return response()->json(['message' => 'That billing term is not available for this plan.'], 422);
        }

        $cart['period'] = $quote['period'];
        $request->session()->put(self::CART_KEY, $cart);

        return response()->json([
            'period' => $quote['period'],
            'plan_label' => $quote['plan_label'],
            'price_text' => HostingOrderService::money($quote['price']),
            'total_text' => HostingOrderService::money($quote['total']),
            'unit' => $quote['unit'],
        ]);
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

        // Back from the domain register / transfer flow? Confirm the domain is registered now.
        if (empty($cart['domain_checked']) && !empty($cart['pending_domain'])) {
            $cart = $this->resumeAfterDomain($request, $cart);
        }

        // The domain must have passed the "is it registered" check (storeDomain sets the flag).
        if (empty($cart['domain_checked'])) {
            $pending = !empty($cart['pending_domain']);

            return redirect()->route('hosting.connect', [
                'region' => $quote['region'],
                'type' => $quote['type'],
                'plan' => $quote['plan_id'],
                'period' => $quote['period'],
            ])->withErrors($pending ? ['domain' => $cart['domain'] . ' is not registered with us yet. Finish and pay for your domain order first, then press Check.'] : []);
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
            // Every billing term this plan sells, for the Billing dropdown (JSON for the JS).
            'terms' => $this->orders->termOptions($quote['region'], $quote['type'], $quote['plan_id']),
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

        // The Billing dropdown is submitted with the order. The term is re-quoted on the
        // server (any price in the request is ignored) and the cart is kept in sync.
        $submittedPeriod = $request->input('period');
        if (is_scalar($submittedPeriod) && (string) $submittedPeriod !== '' && (string) $submittedPeriod !== $cart['period']) {
            $requote = $this->orders->quote($cart['region'], $cart['type'], $cart['plan'], $submittedPeriod);
            if (!$requote) {
                return back()->withInput()->withErrors(['period' => 'That billing term is not available for this plan. Please choose another.']);
            }
            $quote = $requote;
            $cart['period'] = $requote['period'];
            $request->session()->put(self::CART_KEY, $cart);
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

    /**
     * The buyer went to register / transfer a domain and is back. The hosting order may
     * only continue once the domain really is registered (same ResellerClub check as the
     * "I already have a registered domain" mode); otherwise they stay on the connect page.
     */
    private function resumeAfterDomain(Request $request, array $cart)
    {
        $check = $this->domainCheck->check($cart['domain'], true);
        if ($check['ok']) {
            $cart['domain'] = $check['domain'];
            $cart['domain_checked'] = true;
            unset($cart['pending_domain']);
            $request->session()->put(self::CART_KEY, $cart);
        }

        return $cart;
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
