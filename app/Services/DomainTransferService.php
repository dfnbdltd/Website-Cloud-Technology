<?php

namespace App\Services;

use App\Models\DomainOrder;
use App\Support\ResellerClubDomainContacts;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DomainTransferService
{
    private $resellerClub;

    public function __construct(ResellerClubClient $resellerClub)
    {
        $this->resellerClub = $resellerClub;
    }

    public function transferPaidOrder(DomainOrder $order)
    {
        if ($order->payment_status !== 'paid') {
            return;
        }

        $order->loadMissing('items');
        $transferItems = $order->items->where('order_type', 'transfer')
            ->where('registration_status', 'pending');
        if ($transferItems->isEmpty()) {
            return;
        }

        $order = DB::transaction(function () use ($order) {
            $lockedOrder = DomainOrder::whereKey($order->id)->lockForUpdate()->first();
            if (!$lockedOrder || $lockedOrder->payment_status !== 'paid') {
                return null;
            }

            $recentlyProcessing = $lockedOrder->status === 'transfer_processing'
                && $lockedOrder->updated_at->gt(now()->subMinutes(5));
            if ($recentlyProcessing) {
                return null;
            }

            $items = $lockedOrder->items()
                ->where('order_type', 'transfer')
                ->where('registration_status', 'pending')
                ->lockForUpdate()
                ->get();
            if ($items->isEmpty()) {
                return null;
            }

            $lockedOrder->update(['status' => 'transfer_processing']);

            return $lockedOrder->load('items');
        });

        if (!$order) {
            return;
        }

        foreach ($order->items->where('order_type', 'transfer')->where('registration_status', 'pending') as $item) {
            if (!is_string($item->transfer_auth_code) || $item->transfer_auth_code === '') {
                Log::error('Paid domain transfer has no stored authorization code', [
                    'order' => $order->reference,
                    'item_id' => $item->id,
                ]);
                $item->update(['registration_status' => 'transfer_failed']);

                continue;
            }

            try {
                $authCode = Crypt::decryptString($item->transfer_auth_code);
            } catch (DecryptException $exception) {
                Log::error('Stored domain transfer authorization code could not be decrypted', [
                    'order' => $order->reference,
                    'item_id' => $item->id,
                ]);
                $item->update(['registration_status' => 'transfer_failed']);

                continue;
            }

            $response = $this->resellerClub->transferDomain($item->domain, $authCode);
            unset($authCode);

            if ($this->transferSubmitted($response)) {
                $item->update([
                    'registration_status' => 'transfer_submitted',
                    'transfer_auth_code' => null,
                ]);
                Log::info('ResellerClub domain transfer submitted', [
                    'order' => $order->reference,
                    'domain' => $item->domain,
                ]);
            } else {
                $item->update(['registration_status' => 'transfer_failed']);
                Log::warning('ResellerClub domain transfer was not accepted', [
                    'order' => $order->reference,
                    'domain' => $item->domain,
                    'response_status' => is_array($response)
                        ? ($response['actionstatus'] ?? $response['status'] ?? null)
                        : null,
                ]);
            }
        }

        $failed = $order->items->contains(function ($item) {
            return $item->order_type === 'transfer' && $item->registration_status === 'transfer_failed';
        });
        $order->update(['status' => $failed ? 'transfer_failed' : 'transfer_pending']);
    }

    public function preflightError(array $items)
    {
        $items = array_filter($items, function ($item) {
            return ($item['order_type'] ?? 'registration') === 'transfer';
        });
        if (!$items) {
            return null;
        }

        if (!$this->resellerClub->isConfigured() || !config('services.resellerclub.customer_id')) {
            return 'Domain transfer is temporarily unavailable. Please contact support.';
        }

        $required = 0.0;
        foreach ($items as $item) {
            $parts = explode('.', strtolower($item['domain']));
            if (ResellerClubDomainContacts::forDomain($item['domain'], [
                'reg' => config('services.resellerclub.reg_contact_id'),
                'admin' => config('services.resellerclub.admin_contact_id'),
                'tech' => config('services.resellerclub.tech_contact_id'),
                'billing' => config('services.resellerclub.billing_contact_id'),
            ]) === null) {
                return 'Domain transfer is not fully configured. Please contact support.';
            }

            $eligible = $this->resellerClub->validateDomainTransferIn($item['domain']);
            if ($eligible === null) {
                return 'We could not verify this domain’s transfer eligibility. No payment has been taken; please try again shortly.';
            }
            if (!$eligible) {
                return 'This domain is no longer eligible for transfer. Disable its transfer lock at the current registrar and check the 60-day transfer rule. No payment has been taken.';
            }

            $price = $this->resellerClub->liveTransferPrice(end($parts));
            if ($price === null) {
                return 'We could not verify the current ResellerClub transfer price. No payment has been taken; please try again shortly.';
            }
            if (abs($price - (float) $item['price']) > 0.01) {
                return 'The transfer price has changed. Please return to the transfer page and submit your domain again.';
            }
            $required += $price;
        }

        $wallet = $this->resellerClub->walletBalance();
        if (!$wallet) {
            return 'We could not verify the ResellerClub wallet balance. No payment has been taken; please try again shortly.';
        }

        if ($wallet['currency'] !== 'BDT') {
            $rate = app(ExchangeRateService::class)->usdToBdt()['rate'];
            if ($wallet['currency'] === 'USD') {
                $required /= $rate;
            } else {
                return 'We could not verify the ResellerClub wallet currency. No payment has been taken; please contact support.';
            }
        }

        if ($wallet['balance'] < $required) {
            return 'The ResellerClub account wallet does not have enough available funds to transfer this domain. No payment has been taken; please contact support.';
        }

        return null;
    }

    private function transferSubmitted($response)
    {
        if (!is_array($response)) {
            return false;
        }

        $status = strtoupper((string) ($response['actionstatus'] ?? $response['status'] ?? ''));

        return in_array($status, ['SUCCESS', 'NOERROR'], true)
            && strtoupper((string) ($response['status'] ?? '')) !== 'ERROR'
            && !isset($response['error'])
            && !isset($response['errorcode']);
    }
}
