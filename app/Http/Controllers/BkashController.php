<?php

namespace App\Http\Controllers;

use App\Services\BkashClient;
use App\Models\DomainOrder;
use App\Models\Payment;
use App\Services\ResellerClubClient;
use Illuminate\Http\Request;

class BkashController extends Controller
{
    private $bkash;
    private $resellerClub;

    public function __construct(BkashClient $bkash, ResellerClubClient $resellerClub)
    {
        $this->bkash = $bkash;
        $this->resellerClub = $resellerClub;
    }

    public function createPayment(Request $request)
    {
        if (!$request->session()->get('customer')) {
            return redirect()->route('login');
        }

        $order = $request->session()->get('last_domain_order');
        if (!$order || ($order['customer']['payment_method'] ?? null) !== 'bkash') {
            return redirect()->route('domains.index')->withErrors(['domain' => 'Start a bKash order before paying.']);
        }
        if ($request->query('invoice') && $request->query('invoice') !== $order['reference']) {
            return redirect()->route('domains.index')->withErrors(['payment' => 'The payment invoice does not match the order.']);
        }
        if (!$this->bkash->isConfigured()) {
            return redirect()->route('domains.index')->withErrors(['payment' => 'bKash is not configured on this server yet.']);
        }

        $response = $this->bkash->createPayment($order['total'], $order['reference'], $order['customer']['phone']);
        $paymentUrl = $response['bkashURL'] ?? ($response['paymentURL'] ?? null);
        if (!$paymentUrl || empty($response['paymentID'])) {
            $reason = trim($response['statusMessage'] ?? $response['errorMessage'] ?? $this->bkash->lastStatusMessage() ?? '');
            \Log::warning('bKash createPayment failed', ['response' => $response, 'order' => $order['reference']]);

            $hint = stripos($reason, 'invalid or unrecognized') !== false
                ? ' The bKash API rejected the configured credentials — they may have expired. Please generate fresh credentials in the bKash developer portal and update BKASH_* values in .env.'
                : '';

            return view('domains.confirmation', [
                'order' => $order,
                'gatewayWarning' => 'bKash could not start the payment session'
                    . ($reason ? '. Gateway said: “' . $reason . '”' : '.')
                    . $hint
                    . ' Your order is saved and our billing team will contact you with payment instructions for reference ' . $order['reference'] . '.',
            ]);
        }

        $order['payment']['payment_id'] = $response['paymentID'];
        $order['payment']['status'] = 'initiated';
        $request->session()->put('last_domain_order', $order);

        return redirect()->away($paymentUrl);
    }

    public function callback(Request $request)
    {
        $paymentId = $request->query('paymentID');
        $order = $request->session()->get('last_domain_order');
        if (!$paymentId || !$order || ($request->query('invoice') && $request->query('invoice') !== $order['reference'])) {
            return redirect()->route('domains.index')->withErrors(['payment' => 'The bKash callback could not be matched to an order.']);
        }

        $status = strtolower($request->query('status', ''));
        $response = $status === 'success' ? $this->bkash->executePayment($paymentId) : null;
        if (!$this->isSuccessful($response)) {
            $response = $this->bkash->queryPayment($paymentId);
        }
        $order['payment']['payment_id'] = $paymentId;
        $order['payment']['status'] = $this->isSuccessful($response) ? 'paid' : ($status === 'cancel' ? 'cancelled' : 'failed');
        $order['payment']['provider_response'] = $response;
        $request->session()->put('last_domain_order', $order);
        $databaseOrder = isset($order['database_id']) ? DomainOrder::find($order['database_id']) : null;
        if ($databaseOrder) {
            $databaseOrder->update([
                'payment_status' => $order['payment']['status'],
                'status' => $order['payment']['status'] === 'paid' ? 'paid' : 'payment_failed',
                'provider_payment_id' => $paymentId,
                'payload' => json_encode($order),
            ]);
            Payment::where('domain_order_id', $databaseOrder->id)->update([
                'provider_payment_id' => $paymentId,
                'status' => $order['payment']['status'],
                'raw_response' => json_encode($response),
            ]);
            if ($order['payment']['status'] === 'paid' && config('services.resellerclub.auto_register')) {
                $registrationComplete = true;
                foreach ($databaseOrder->items as $item) {
                    $registration = $this->resellerClub->registerDomain($item->domain, $item->years);
                    if (!is_array($registration) || isset($registration['error'])) {
                        $registrationComplete = false;
                    }
                }
                $databaseOrder->update(['status' => $registrationComplete ? 'active' : 'registration_pending']);
            }
        }

        return view('domains.confirmation', ['order' => $order]);
    }

    public function query(Request $request, $paymentId)
    {
        if (!$request->session()->get('customer')) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        if (!$this->bkash->isConfigured()) {
            return response()->json(['message' => 'bKash is not configured'], 503);
        }

        return response()->json($this->bkash->queryPayment($paymentId) ?: ['message' => 'Unable to query bKash']);
    }

    private function isSuccessful($response)
    {
        if (!is_array($response)) {
            return false;
        }

        return strtolower($response['transactionStatus'] ?? '') === 'completed'
            || strtolower($response['statusCode'] ?? '') === '0000';
    }
}