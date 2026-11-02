<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Models\DomainOrder;
use App\Models\Payment;
use App\Services\BkashClient;
use App\Services\InvoiceService;
use App\Services\Notifier;
use App\Services\DomainRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * bKash payment via the direct bKash Checkout (PGW popup) API v1.2.0-beta.
 *
 * Flow (see app/Services/BkashClient.php):
 *   1) /bkash/pay      -> renders the payment page with bKash's checkout script
 *   2) /bkash/create   -> AJAX: server creates the payment (token grant + /checkout/payment/create)
 *   3) bKash popup     -> customer enters wallet number, OTP and PIN
 *   4) /bkash/execute  -> AJAX: server executes the payment after authorization
 *   5) /bkash/query    -> AJAX fallback while execute reports "progressing"
 *
 * Credentials stay server-side; the browser only ever talks to this app.
 */
class BkashController extends Controller
{
    private $bkash;

    public function __construct(BkashClient $bkash)
    {
        $this->bkash = $bkash;
    }

    /**
     * Payment page: the customer completes the payment in bKash's own popup.
     */
    public function pay(Request $request)
    {
        $order = $request->session()->get('last_domain_order');
        if (!$order || ($order['customer']['payment_method'] ?? null) !== 'bkash') {
            return redirect()->route('domains.index')->withErrors(['payment' => 'Start a bKash order before paying.']);
        }

        $databaseOrder = isset($order['database_id']) ? DomainOrder::find($order['database_id']) : null;
        if ($databaseOrder && $databaseOrder->payment_status === 'paid') {
            return view('domains.confirmation', ['order' => $order, 'paid' => true]);
        }

        return view('domains.bkash-payment', [
            'order' => $order,
            'gatewayReady' => $this->bkash->isConfigured(),
        ]);
    }

    /**
     * AJAX (called by the bKash popup's createRequest): create the payment server-side.
     */
    public function create(Request $request)
    {
        $order = $request->session()->get('last_domain_order');

        if (!$order || ($order['payment']['method'] ?? null) !== 'bkash') {
            return response()->json(['errorMessage' => 'No bKash order is in progress for this session.'], 409);
        }

        $databaseOrder = isset($order['database_id']) ? DomainOrder::find($order['database_id']) : null;
        if ($databaseOrder && $databaseOrder->payment_status === 'paid') {
            return response()->json(['errorMessage' => 'This order is already paid.'], 409);
        }
        if ($databaseOrder && $databaseOrder->status === 'cancelled') {
            return response()->json(['errorMessage' => 'This order has been cancelled.'], 409);
        }

        if (!$this->bkash->isConfigured()) {
            Log::warning('bKash attempted without BKASH_* configuration');

            return response()->json(['errorMessage' => 'bKash is not configured on this server yet. Our billing team will contact you with payment instructions for reference ' . $order['reference'] . '.'], 503);
        }

        $response = $this->bkash->createPayment(
            $order['total'],
            $order['reference'],
            $order['reference']
        );

        if (!is_array($response) || empty($response['paymentID'])) {
            $reason = (string) ($response['errorMessage'] ?? $response['statusMessage'] ?? $response['msg'] ?? $response['message'] ?? $this->bkash->lastStatusMessage() ?? '');
            Log::warning('bKash payment create failed', ['order' => $order['reference'], 'response' => $response]);

            return response()->json(['errorMessage' => 'bKash could not start the payment' . ($reason !== '' ? ': ' . $reason : '.')], 502);
        }

        // Record the attempt server-side before the popup opens.
        $order['payment']['status'] = 'initiated';
        $order['payment']['paymentID'] = $response['paymentID'];
        $request->session()->put('last_domain_order', $order);
        if ($databaseOrder) {
            $databaseOrder->update([
                'payment_status' => 'initiated',
                'provider_payment_id' => $response['paymentID'],
            ]);
            Payment::where('domain_order_id', $databaseOrder->id)->update([
                'provider_payment_id' => $response['paymentID'],
                'status' => 'initiated',
            ]);
        }

        return response()->json([
            'paymentID' => $response['paymentID'],
            'amount' => $response['amount'] ?? $order['total'],
        ]);
    }

    /**
     * AJAX (called by the bKash popup's executeRequestOnAuthorization): execute the payment.
     */
    public function execute(Request $request)
    {
        $order = $request->session()->get('last_domain_order');
        $paymentId = $order['payment']['paymentID'] ?? null;

        if (!$order || ($order['payment']['method'] ?? null) !== 'bkash' || !$paymentId) {
            return response()->json(['errorMessage' => 'No bKash payment is in progress for this session.'], 409);
        }

        $databaseOrder = isset($order['database_id']) ? DomainOrder::find($order['database_id']) : null;
        if ($databaseOrder && $databaseOrder->payment_status === 'paid') {
            return response()->json(['status' => 'success']);
        }
        if ($databaseOrder && $databaseOrder->status === 'cancelled') {
            return response()->json(['errorMessage' => 'This order has been cancelled.'], 409);
        }

        $response = $this->bkash->executePayment($paymentId);
        $status = strtolower((string) ($response['transactionStatus'] ?? ''));

        if ($status === 'completed' || ($response['statusCode'] ?? null) === '0000') {
            return $this->markPaid(
                $request,
                $order,
                $databaseOrder,
                $response['trxID'] ?? ('BKASH-' . $paymentId),
                $response
            );
        }

        if (in_array($status, ['initiated', 'pending', 'progressing'], true)) {
            return response()->json(['status' => 'pending']);
        }

        $reason = (string) ($response['statusMessage'] ?? $response['errorMessage'] ?? $response['msg'] ?? $response['message'] ?? $this->bkash->lastStatusMessage() ?? '');
        Log::warning('bKash execute did not complete', ['paymentID' => $paymentId, 'order' => $order['reference'], 'response' => $response]);

        return response()->json(['errorMessage' => 'Payment was not completed' . ($reason !== '' ? ': ' . $reason : '.')], 402);
    }

    /**
     * AJAX fallback: query the payment status while execute reports progressing.
     */
    public function query(Request $request)
    {
        $order = $request->session()->get('last_domain_order');
        $paymentId = $order['payment']['paymentID'] ?? null;

        if (!$order || !$paymentId) {
            return response()->json(['errorMessage' => 'No bKash payment is in progress for this session.'], 409);
        }

        $databaseOrder = isset($order['database_id']) ? DomainOrder::find($order['database_id']) : null;
        if ($databaseOrder && $databaseOrder->payment_status === 'paid') {
            return response()->json(['status' => 'success']);
        }
        if ($databaseOrder && $databaseOrder->status === 'cancelled') {
            return response()->json(['errorMessage' => 'This order has been cancelled.'], 409);
        }

        $response = $this->bkash->queryPayment($paymentId);
        $status = strtolower((string) ($response['transactionStatus'] ?? ''));

        if ($status === 'completed') {
            return $this->markPaid(
                $request,
                $order,
                $databaseOrder,
                $response['trxID'] ?? ('BKASH-' . $paymentId),
                $response
            );
        }

        return response()->json(['status' => 'pending']);
    }

    /**
     * Cancel an unpaid bKash checkout when the customer leaves payment.
     */
    public function cancel(Request $request)
    {
        $order = $request->session()->get('last_domain_order');
        $databaseOrder = $order && isset($order['database_id'])
            ? DomainOrder::find($order['database_id'])
            : null;

        if (!$databaseOrder || ($order['payment']['method'] ?? null) !== 'bkash') {
            return response()->json(['status' => 'ignored'], 409);
        }

        if ($databaseOrder->payment_status === 'paid') {
            return response()->json(['status' => 'paid'], 409);
        }

        app(InvoiceService::class)->cancelOrder($databaseOrder);
        $request->session()->forget('last_domain_order');

        return response()->json([
            'status' => 'cancelled',
            'redirect' => route('domains.index'),
        ]);
    }

    /**
     * Record the successful payment against the order (DB + session).
     */
    private function markPaid(Request $request, array $order, $databaseOrder, $transactionId, $raw)
    {
        $order['payment']['status'] = 'paid';
        $order['payment']['transaction_id'] = $transactionId;
        $order['payment']['provider_response'] = $raw;
        $request->session()->put('last_domain_order', $order);

        if ($databaseOrder) {
            if ($databaseOrder->status === 'cancelled') {
                return response()->json(['errorMessage' => 'This order has been cancelled.'], 409);
            }
            $databaseOrder->update([
                'payment_status' => 'paid',
                'status' => 'paid',
                'provider_payment_id' => $transactionId,
                'payload' => json_encode($order),
            ]);
            Payment::where('domain_order_id', $databaseOrder->id)->update([
                'provider_payment_id' => $transactionId,
                'status' => 'paid',
                'raw_response' => json_encode($raw),
            ]);

            app(InvoiceService::class)->markOrderPaid($databaseOrder);

            app(DomainRegistrationService::class)->registerPaidOrder($databaseOrder->fresh());

            if ($databaseOrder->customer_email) {
                Notifier::send($databaseOrder->customer_email, new InvoiceMail($databaseOrder->fresh()));
            }
        }

        return response()->json([
            'status' => 'success',
            'redirect' => route('domains.confirmation', ['reference' => $order['reference']]),
        ]);
    }

}
