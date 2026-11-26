<?php

namespace App\Http\Controllers;

use App\Mail\HostingInvoiceMail;
use App\Models\HostingOrder;
use App\Services\BkashClient;
use App\Services\InvoiceService;
use App\Services\Notifier;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * bKash payment for a hosting order (same bKash popup page the domain flow
 * uses, with hosting's own endpoints behind it):
 *
 *   /hosting/bkash/pay      page: plan, domain, who it is for, the bKash button
 *   /hosting/bkash/create   AJAX: server creates the payment (amount read from the saved order)
 *   /hosting/bkash/execute  AJAX: server executes it after the popup is authorised
 *   /hosting/bkash/query    AJAX: status fallback, also used when the popup is closed
 *   /hosting/bkash/cancel   AJAX: customer left without paying -> order cancelled
 *
 * The order is found from session('hosting_order_id') and must have been placed
 * by the signed-in user. The amount is always the order's frozen total.
 * Step 3 (payment confirmed) is markPaid(): payment_status paid + status
 * awaiting_setup, invoice paid, receipt mailed to the owner. Step 4 is what the
 * customer sees (order page + My Store -> Hosting "Setting up"). The actual
 * hosting setup comes in a later step.
 */
class HostingBkashController extends Controller
{
    const ORDER_KEY = 'hosting_order_id';

    private $bkash;

    public function __construct(BkashClient $bkash)
    {
        $this->bkash = $bkash;
    }

    public function pay(Request $request)
    {
        $order = $this->currentOrder($request);

        if (!$order) {
            return redirect()->route('hosting.checkout')
                ->withErrors(['payment' => 'Start your hosting order before paying.']);
        }
        if ($order->isPaid()) {
            return redirect()->route('hosting.order', $order->reference);
        }
        if ($order->isCancelled()) {
            $request->session()->forget(self::ORDER_KEY);

            return redirect()->route('hosting.checkout')
                ->withErrors(['payment' => 'That order was cancelled. You can place it again.']);
        }

        return view('domains.bkash-payment', [
            'order' => $this->viewOrder($order),
            'gatewayReady' => $this->bkash->isConfigured(),
            'summaryLabel' => 'Hosting order',
            'heading' => 'Pay for your hosting with bKash.',
            'backUrl' => route('hosting.order', $order->reference),
            'backLabel' => 'Cancel and go back →',
            'cancelUrl' => route('hosting.checkout'),
            'endpoints' => [
                'create' => route('hosting.bkash.create'),
                'execute' => route('hosting.bkash.execute'),
                'query' => route('hosting.bkash.query'),
                'cancel' => route('hosting.bkash.cancel'),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $order = $this->currentOrder($request);
        if (!$order) {
            return response()->json(['errorMessage' => 'No hosting order is in progress for this session.'], 409);
        }
        if ($order->isPaid()) {
            return response()->json(['errorMessage' => 'This order is already paid.'], 409);
        }
        if ($order->isCancelled()) {
            return response()->json(['errorMessage' => 'This order has been cancelled.'], 409);
        }
        if (!$this->bkash->isConfigured()) {
            Log::warning('bKash attempted for a hosting order without BKASH_* configuration');

            return response()->json(['errorMessage' => 'bKash is not configured on this server yet. Our billing team will contact you with payment instructions for reference ' . $order->reference . '.'], 503);
        }

        $response = $this->bkash->createPayment($order->total, $order->reference, $order->reference);

        if (!is_array($response) || empty($response['paymentID'])) {
            $reason = (string) ($response['errorMessage'] ?? $response['statusMessage'] ?? $response['msg'] ?? $response['message'] ?? $this->bkash->lastStatusMessage() ?? '');
            Log::warning('bKash hosting payment create failed', ['order' => $order->reference, 'response' => $response]);

            return response()->json(['errorMessage' => 'bKash could not start the payment' . ($reason !== '' ? ': ' . $reason : '.')], 502);
        }

        // Record the attempt server-side before the popup opens.
        $order->update([
            'payment_status' => 'initiated',
            'provider_payment_id' => $response['paymentID'],
        ]);

        return response()->json([
            'paymentID' => $response['paymentID'],
            'amount' => $response['amount'] ?? $order->total,
        ]);
    }

    public function execute(Request $request)
    {
        $order = $this->currentOrder($request);
        if (!$order) {
            return response()->json(['errorMessage' => 'No bKash payment is in progress for this session.'], 409);
        }
        if ($order->isPaid()) {
            return $this->success($order);
        }
        if ($order->isCancelled()) {
            return response()->json(['errorMessage' => 'This order has been cancelled.'], 409);
        }
        if ($order->payment_status !== 'initiated' || !$order->provider_payment_id) {
            return response()->json(['errorMessage' => 'No bKash payment is in progress for this session.'], 409);
        }

        $paymentId = $order->provider_payment_id;
        $response = $this->bkash->executePayment($paymentId);
        $status = strtolower((string) ($response['transactionStatus'] ?? ''));

        if ($status === 'completed' || ($response['statusCode'] ?? null) === '0000') {
            return $this->markPaid($request, $order, $response['trxID'] ?? ('BKASH-' . $paymentId), $response);
        }

        if (in_array($status, ['initiated', 'pending', 'progressing'], true)) {
            return response()->json(['status' => 'pending']);
        }

        $reason = (string) ($response['statusMessage'] ?? $response['errorMessage'] ?? $response['msg'] ?? $response['message'] ?? $this->bkash->lastStatusMessage() ?? '');
        Log::warning('bKash hosting execute did not complete', ['paymentID' => $paymentId, 'order' => $order->reference, 'response' => $response]);

        return response()->json(['errorMessage' => 'Payment was not completed' . ($reason !== '' ? ': ' . $reason : '.')], 402);
    }

    /** Status fallback; also what the page asks when the popup is closed. */
    public function query(Request $request)
    {
        $order = $this->currentOrder($request);
        if (!$order) {
            return response()->json(['errorMessage' => 'No bKash payment is in progress for this session.'], 409);
        }
        if ($order->isPaid()) {
            return $this->success($order);
        }
        if ($order->isCancelled()) {
            return response()->json(['errorMessage' => 'This order has been cancelled.'], 409);
        }
        if ($order->payment_status !== 'initiated' || !$order->provider_payment_id) {
            return response()->json(['errorMessage' => 'No bKash payment is in progress for this session.'], 409);
        }

        $paymentId = $order->provider_payment_id;
        $response = $this->bkash->queryPayment($paymentId);

        if (strtolower((string) ($response['transactionStatus'] ?? '')) === 'completed') {
            return $this->markPaid($request, $order, $response['trxID'] ?? ('BKASH-' . $paymentId), $response);
        }

        return response()->json(['status' => 'pending']);
    }

    /** The customer left the payment without paying: cancel the order. */
    public function cancel(Request $request)
    {
        $order = $this->currentOrder($request);
        $back = route('hosting.checkout');

        if (!$order) {
            return response()->json(['status' => 'ignored', 'redirect' => $back], 409);
        }
        if ($order->isPaid()) {
            return response()->json(['status' => 'paid', 'redirect' => route('hosting.order', $order->reference)], 409);
        }

        if (!$order->isCancelled()) {
            app(InvoiceService::class)->cancelHostingOrder($order);
        }
        $request->session()->forget(self::ORDER_KEY);

        return response()->json(['status' => 'cancelled', 'redirect' => $back]);
    }

    /**
     * Step 3 - payment confirmed. Runs exactly once even if execute and query
     * race: the order row is locked and re-checked inside the transaction.
     */
    private function markPaid(Request $request, HostingOrder $order, $transactionId, $raw)
    {
        $firstTime = false;

        $locked = DB::transaction(function () use ($order, $transactionId, $raw, &$firstTime) {
            $row = HostingOrder::whereKey($order->id)->lockForUpdate()->first();

            if ($row->isPaid() || $row->isCancelled()) {
                return $row;
            }

            $row->forceFill([
                'payment_status' => 'paid',
                'status' => HostingOrder::STATUS_AWAITING_SETUP,
                'provider_payment_id' => $transactionId,
                'payment_response' => json_encode($raw),
                'paid_at' => now(),
            ])->save();
            $firstTime = true;

            return $row;
        });

        if ($locked->isCancelled()) {
            return response()->json(['errorMessage' => 'This order has been cancelled.'], 409);
        }

        if ($firstTime) {
            $invoices = app(InvoiceService::class);
            $invoices->markHostingOrderPaid($locked);

            // Receipt to the OWNER's account email (the customer when bought for one).
            $owner = User::find($locked->user_id);
            $to = $owner && $owner->email ? $owner->email : $locked->customer_email;
            if ($to) {
                Notifier::send($to, new HostingInvoiceMail($locked->fresh(), $invoices->findForSource(HostingOrder::class, $locked->id)));
            }
        }

        $request->session()->forget([self::ORDER_KEY, HostingCheckoutController::CART_KEY]);

        return $this->success($locked);
    }

    private function success(HostingOrder $order)
    {
        return response()->json([
            'status' => 'success',
            'redirect' => route('hosting.order', $order->reference),
        ]);
    }

    /** The order in this session, only if the signed-in user placed it. */
    private function currentOrder(Request $request)
    {
        $id = $request->session()->get(self::ORDER_KEY);
        $user = $request->user() ?: User::find($request->session()->get('customer.id'));

        if (!$id || !$user) {
            return null;
        }

        return HostingOrder::where('id', $id)->where('placed_by_user_id', $user->id)->first();
    }

    /** Shape the bKash page / order-parties card read. */
    private function viewOrder(HostingOrder $order)
    {
        return [
            'reference' => $order->reference,
            'total' => $order->total,
            'payment' => ['transaction_id' => $order->isPaid() ? $order->provider_payment_id : null],
            'plan' => [
                // "Plan A (10 GB | NVMe Storage)" from the snapshot saved at purchase.
                'name' => $order->planLabel(),
                'meta' => $order->product . ' · ' . $order->period_label,
            ],
            'items_heading' => 'Domain name',
            'items' => [['domain' => $order->domain, 'label' => 'Hosting domain']],
            'customer' => [
                'full_name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
                'purchase_for' => $order->isForCustomer() ? 'customer' : 'myself',
            ],
        ];
    }
}
