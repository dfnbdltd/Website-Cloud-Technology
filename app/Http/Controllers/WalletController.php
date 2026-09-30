<?php

namespace App\Http\Controllers;

use App\Models\Topup;
use App\Services\BkashClient;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Wallet: available balance + instant top-up through the bKash Checkout API.
 *
 * Flow mirrors BkashController (domain orders):
 *   POST /wallet/bkash          -> creates a pending Topup, opens the bKash page
 *   POST /wallet/bkash/create   -> AJAX: bKash payment create
 *   POST /wallet/bkash/execute  -> AJAX: execute after the customer authorises
 *   POST /wallet/bkash/query    -> AJAX fallback while bKash is still processing
 * A completed payment credits the balance immediately (no admin approval).
 */
class WalletController extends Controller
{
    private $bkash;

    public function __construct(BkashClient $bkash)
    {
        $this->bkash = $bkash;
    }

    public function index(Request $request)
    {
        if (!session('customer')) { return redirect()->guest(route('login')); }
        $user = User::find(session('customer.id'));
        if (!$user) { return redirect()->route('login'); }
        $this->refreshSessionBalance($request, $user);

        return view('wallet.index', [
            'balance' => (float) $user->balance,
            'topups' => Topup::where('user_id', $user->id)->latest()->take(10)->get(),
            'pendingTopup' => Topup::where('user_id', $user->id)->where('status', 'pending')->where('method', '!=', 'bkash')->count(),
            'gatewayReady' => $this->bkash->isConfigured(),
        ]);
    }

    /**
     * Start a bKash top-up: record it as pending, then show the bKash page.
     */
    public function start(Request $request)
    {
        if (!session('customer')) { return redirect()->guest(route('login')); }
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:10', 'max:100000']]);

        $topup = Topup::create([
            'user_id' => session('customer.id'),
            'reference' => 'TU-' . date('Ymd') . '-' . strtoupper(substr(sha1(uniqid('', true)), 0, 6)),
            'amount' => round((float) $data['amount'], 2),
            'method' => 'bkash',
            'status' => 'pending',
        ]);
        $request->session()->put('wallet_topup_id', $topup->id);

        return redirect()->route('wallet.bkash.pay');
    }

    public function pay(Request $request)
    {
        $topup = $this->currentTopup($request);
        if (!$topup) {
            return redirect()->route('wallet.index')->withErrors(['amount' => 'Enter an amount to add balance with bKash.']);
        }

        return view('domains.bkash-payment', [
            'order' => [
                'reference' => $topup->reference,
                'total' => $topup->amount,
                'payment' => ['transaction_id' => $topup->status === 'approved' ? $topup->transaction_id : null],
            ],
            'gatewayReady' => $this->bkash->isConfigured(),
            'summaryLabel' => 'Top-up',
            'heading' => 'Add balance with bKash.',
            'backUrl' => route('wallet.index'),
            'backLabel' => 'Back to wallet →',
            'endpoints' => [
                'create' => route('wallet.bkash.create'),
                'execute' => route('wallet.bkash.execute'),
                'query' => route('wallet.bkash.query'),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $topup = $this->currentTopup($request);
        if (!$topup || $topup->status !== 'pending') {
            return response()->json(['errorMessage' => 'No top-up is in progress for this session.'], 409);
        }
        if (!$this->bkash->isConfigured()) {
            return response()->json(['errorMessage' => 'bKash is not configured on this server yet.'], 503);
        }

        $response = $this->bkash->createPayment($topup->amount, $topup->reference, $topup->reference);
        if (!is_array($response) || empty($response['paymentID'])) {
            $reason = (string) ($response['errorMessage'] ?? $response['statusMessage'] ?? $this->bkash->lastStatusMessage() ?? '');
            Log::warning('bKash top-up create failed', ['topup' => $topup->reference, 'response' => $response]);

            return response()->json(['errorMessage' => 'bKash could not start the payment' . ($reason !== '' ? ': ' . $reason : '.')], 502);
        }

        $request->session()->put('wallet_payment_id', $response['paymentID']);

        return response()->json(['paymentID' => $response['paymentID'], 'amount' => $response['amount'] ?? $topup->amount]);
    }

    public function execute(Request $request)
    {
        $topup = $this->currentTopup($request);
        $paymentId = $request->session()->get('wallet_payment_id');
        if (!$topup || !$paymentId) {
            return response()->json(['errorMessage' => 'No bKash payment is in progress for this session.'], 409);
        }
        if ($topup->status === 'approved') {
            return $this->success();
        }

        $response = $this->bkash->executePayment($paymentId);
        $status = strtolower((string) ($response['transactionStatus'] ?? ''));

        if ($status === 'completed' || ($response['statusCode'] ?? null) === '0000') {
            return $this->credit($request, $topup, $response['trxID'] ?? ('BKASH-' . $paymentId));
        }
        if (in_array($status, ['initiated', 'pending', 'progressing'], true)) {
            return response()->json(['status' => 'pending']);
        }

        $reason = (string) ($response['statusMessage'] ?? $response['errorMessage'] ?? $this->bkash->lastStatusMessage() ?? '');
        Log::warning('bKash top-up execute did not complete', ['topup' => $topup->reference, 'response' => $response]);

        return response()->json(['errorMessage' => 'Payment was not completed' . ($reason !== '' ? ': ' . $reason : '.')], 402);
    }

    public function query(Request $request)
    {
        $topup = $this->currentTopup($request);
        $paymentId = $request->session()->get('wallet_payment_id');
        if (!$topup || !$paymentId) {
            return response()->json(['errorMessage' => 'No bKash payment is in progress for this session.'], 409);
        }
        if ($topup->status === 'approved') {
            return $this->success();
        }

        $response = $this->bkash->queryPayment($paymentId);
        if (strtolower((string) ($response['transactionStatus'] ?? '')) === 'completed') {
            return $this->credit($request, $topup, $response['trxID'] ?? ('BKASH-' . $paymentId));
        }

        return response()->json(['status' => 'pending']);
    }

    /**
     * Credit the wallet exactly once, even if execute and query race.
     */
    private function credit(Request $request, Topup $topup, $transactionId)
    {
        DB::transaction(function () use ($topup, $transactionId) {
            $locked = Topup::whereKey($topup->id)->lockForUpdate()->first();
            if ($locked->status === 'approved') {
                return;
            }
            $user = User::whereKey($locked->user_id)->lockForUpdate()->first();
            $user->forceFill(['balance' => (float) $user->balance + (float) $locked->amount])->save();
            $locked->forceFill([
                'status' => 'approved',
                'transaction_id' => $transactionId,
                'approved_at' => now(),
                'admin_note' => 'Paid instantly via bKash',
            ])->save();
        });

        $user = User::find($topup->user_id);
        $this->refreshSessionBalance($request, $user);
        $request->session()->forget(['wallet_topup_id', 'wallet_payment_id']);
        $request->session()->flash('success', '৳ ' . number_format($topup->amount, 2) . ' was added to your balance.');

        return $this->success();
    }

    private function success()
    {
        return response()->json(['status' => 'success', 'redirect' => route('wallet.index')]);
    }

    private function currentTopup(Request $request)
    {
        $id = $request->session()->get('wallet_topup_id');

        return $id ? Topup::where('id', $id)->where('user_id', session('customer.id'))->first() : null;
    }

    private function refreshSessionBalance(Request $request, User $user)
    {
        $request->session()->put('customer.balance', (float) $user->balance);
    }
}
