<?php

namespace App\Http\Controllers;

use App\Models\SslOrder;
use App\Services\InvoiceService;
use App\Services\SslOrderService;
use App\User;
use Illuminate\Http\Request;

/**
 * Buying an SSL certificate:
 *
 *   plan card "Buy Now"  →  /ssl/connect   (which domain is it for?)
 *                        →  /ssl/checkout  (guest: sign in · customer: who + how to pay)
 *                        →  /ssl/orders/{reference}  (order saved, waiting for payment)
 *
 * Connect and checkout are public so a guest sees the real BDT price before
 * being asked to sign in; placing the order needs a signed-in customer. The
 * chosen plan/term/domain waits in session('ssl_cart'), which survives the
 * login/registration round trip (the sign-in sends the buyer back to
 * /ssl/checkout through AuthIntent, the same way domain checkout works).
 *
 * The cart stores only plan key + years + domain. Every price shown or charged
 * is recomputed by SslOrderService::quote().
 */
class SslCheckoutController extends Controller
{
    const CART_KEY = 'ssl_cart';

    private $orders;

    public function __construct(SslOrderService $orders)
    {
        $this->orders = $orders;
    }

    /** Step 1 — which domain is the certificate for? */
    public function connect(Request $request)
    {
        $quote = $this->orders->quote($request->query('plan'), $request->query('term', 1));
        if (!$quote) {
            return $this->backToPlans('That SSL plan is not available. Please choose a plan again.');
        }

        $cart = $request->session()->get(self::CART_KEY, []);
        $sameChoice = ($cart['plan'] ?? null) === $quote['plan']['key'] && (int) ($cart['years'] ?? 0) === $quote['years'];

        return view('ssl.connect', [
            'quote' => $quote,
            'wildcard' => SslOrderService::isWildcard($quote['plan']),
            'domain' => old('domain', $sameChoice ? ($cart['domain'] ?? '') : ''),
        ]);
    }

    public function storeDomain(Request $request)
    {
        $data = $request->validate([
            'plan' => ['required', 'string', 'max:80'],
            'term' => ['required', 'integer', 'min:1', 'max:10'],
            'domain' => ['required', 'string', 'max:300'],
        ]);

        $quote = $this->orders->quote($data['plan'], $data['term']);
        if (!$quote) {
            return $this->backToPlans('That SSL plan is not available. Please choose a plan again.');
        }

        $wildcard = SslOrderService::isWildcard($quote['plan']);
        $domain = SslOrderService::normalizeDomain($data['domain'], $wildcard);
        if ($domain === null) {
            return back()->withInput()->withErrors([
                'domain' => $wildcard
                    ? 'Enter a valid domain name, like example.com. We will issue it as *.example.com.'
                    : 'Enter a valid domain name, like example.com or www.example.com.',
            ]);
        }

        $request->session()->put(self::CART_KEY, [
            'plan' => $quote['plan']['key'],
            'years' => $quote['years'],
            'domain' => $domain,
        ]);

        return redirect()->route('ssl.checkout');
    }

    /** Step 2 — sign in (guests) or choose who it is for and how to pay. */
    public function checkout(Request $request)
    {
        $cart = $this->cart($request);
        $quote = $cart ? $this->orders->quote($cart['plan'], $cart['years']) : null;
        if (!$cart || !$quote) {
            $request->session()->forget(self::CART_KEY);

            return $this->backToPlans('Choose an SSL plan to continue.');
        }

        $user = $this->currentUser($request);
        $configured = config('services.payments.methods', []);
        $methods = array_intersect_key($configured, array_flip(SslOrderService::PAYMENT_METHODS));

        return view('ssl.checkout', [
            'quote' => $quote,
            'domain' => $cart['domain'],
            'user' => $user,
            'methods' => $methods,
        ]);
    }

    /** AJAX: does this customer exist? (the "Confirm" button) */
    public function lookupCustomer(Request $request)
    {
        $data = $request->validate(['identifier' => ['required', 'string', 'max:255']]);

        if (!$this->orders->findCustomer($data['identifier'])) {
            return response()->json(['message' => 'Customer does not exist. Check the email or customer ID and try again.'], 404);
        }

        return response()->json(['verified' => true]);
    }

    /** Save the order + unpaid invoice. Payment and ResellerClub come next. */
    public function placeOrder(Request $request)
    {
        $user = $this->currentUser($request);
        if (!$user) {
            return redirect()->route('login', ['return_to' => route('ssl.checkout', [], false)]);
        }

        $cart = $this->cart($request);
        $quote = $cart ? $this->orders->quote($cart['plan'], $cart['years']) : null;
        if (!$cart || !$quote) {
            $request->session()->forget(self::CART_KEY);

            return $this->backToPlans('Your SSL selection expired. Please choose a plan again.');
        }

        $buyingForCustomer = $request->input('purchase_for') === 'customer';
        $data = $request->validate([
            'purchase_for' => ['required', 'in:myself,customer'],
            'customer_identifier' => $buyingForCustomer ? ['required', 'string', 'max:255'] : ['nullable'],
            'payment_method' => ['required', 'in:' . implode(',', SslOrderService::PAYMENT_METHODS)],
        ], [
            'customer_identifier.required' => 'Enter the email or ID of the customer you are buying for.',
            'payment_method.required' => 'Choose bKash or Nagad to continue.',
            'payment_method.in' => 'Choose bKash or Nagad to continue.',
        ]);

        $owner = $user;
        if ($buyingForCustomer) {
            $owner = $this->orders->findCustomer($data['customer_identifier']);
            if (!$owner) {
                return back()->withInput()->withErrors([
                    'customer_identifier' => 'Customer does not exist. Check the email or customer ID and try again.',
                ]);
            }
        }

        $order = $this->orders->place($quote, $cart['domain'], $owner, $user, $data['payment_method']);

        $request->session()->forget(self::CART_KEY);

        // TODO(payment): hand $order to the bKash / Nagad gateway here. When the
        // payment is confirmed: set payment_status = 'paid' and status = 'paid',
        // call app(InvoiceService::class)->markSslOrderPaid($order), then place the
        // certificate order on ResellerClub and store reseller_order_id.

        return redirect()->route('ssl.order', $order->reference);
    }

    /** Step 3 — the saved order (owner or the person who placed it only). */
    public function order(Request $request, $reference)
    {
        $user = $this->currentUser($request);

        $order = SslOrder::where('reference', $reference)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)->orWhere('placed_by_user_id', $user->id);
            })
            ->firstOrFail();

        // The invoice page only opens for the account that owns the invoice.
        $invoice = app(InvoiceService::class)->findForSource(SslOrder::class, $order->id);
        if ($invoice && (int) $invoice->user_id !== (int) $user->id) {
            $invoice = null;
        }

        return view('ssl.order', ['order' => $order, 'invoice' => $invoice]);
    }

    private function cart(Request $request)
    {
        $cart = $request->session()->get(self::CART_KEY);

        if (!is_array($cart) || empty($cart['plan']) || empty($cart['domain']) || (int) ($cart['years'] ?? 0) < 1) {
            return null;
        }

        return $cart;
    }

    private function currentUser(Request $request)
    {
        return $request->user() ?: User::find($request->session()->get('customer.id'));
    }

    private function backToPlans($message)
    {
        return redirect()->route('services.coming-soon', ['service' => 'ssl-certificate'])
            ->withErrors(['ssl' => $message]);
    }
}
