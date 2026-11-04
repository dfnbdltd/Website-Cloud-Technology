<?php

namespace App\Services;

use App\Models\DomainOrder;
use App\Support\ResellerClubDomainContacts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DomainRegistrationService
{
    private $resellerClub;

    public function __construct(ResellerClubClient $resellerClub)
    {
        $this->resellerClub = $resellerClub;
    }

    public function registerPaidOrder(DomainOrder $order)
    {
        if ($order->payment_status !== 'paid') {
            Log::info('Domain registration retry skipped because order is not paid', [
                'order_id' => $order->id,
                'payment_status' => $order->payment_status,
            ]);

            return;
        }

        $order->loadMissing('items');
        if ($order->items->contains(function ($item) {
            return $item->order_type === 'transfer';
        })) {
            app(DomainTransferService::class)->transferPaidOrder($order);

            return;
        }

        $testDomains = config('services.test_domains', []);
        $registrableItems = $order->items->filter(function ($item) use ($testDomains) {
            return !isset($testDomains[$item->domain]);
        });
        if ($registrableItems->isEmpty()) {
            Log::warning('Domain registration retry skipped because order has no registrable items', [
                'order_id' => $order->id,
                'item_count' => $order->items->count(),
            ]);

            return;
        }

        $order = DB::transaction(function () use ($order) {
            $lockedOrder = DomainOrder::whereKey($order->id)->lockForUpdate()->first();
            if (!$lockedOrder || $lockedOrder->payment_status !== 'paid') {
                Log::info('Domain registration retry skipped after order lock check', [
                    'order_id' => $order->id,
                    'found' => (bool) $lockedOrder,
                    'payment_status' => $lockedOrder ? $lockedOrder->payment_status : null,
                ]);

                return null;
            }

            $staleRegistration = $lockedOrder->status === 'registration_processing'
                && $lockedOrder->updated_at->lte(now()->subMinutes(5));
            if (!in_array($lockedOrder->status, ['paid', 'registration_pending'], true) && !$staleRegistration) {
                Log::info('Domain registration retry skipped because order status is not retryable', [
                    'order_id' => $lockedOrder->id,
                    'status' => $lockedOrder->status,
                ]);

                return null;
            }

            $lockedOrder->update(['status' => 'registration_processing']);

            return $lockedOrder->load('items');
        });

        if (!$order) {
            return;
        }

        $testDomains = config('services.test_domains', []);
        $registrableItems = $order->items->filter(function ($item) use ($testDomains) {
            return !isset($testDomains[$item->domain]) && $item->registration_status !== 'registered';
        });

        if ($registrableItems->isEmpty()) {
            $order->update(['status' => 'active']);

            return;
        }

        $walletError = $this->walletPreflightError($registrableItems->toArray());
        if ($walletError) {
            Log::warning('ResellerClub registration held because wallet pre-check failed', [
                'order' => $order->reference,
                'reason' => $walletError,
            ]);
            $order->update(['status' => 'registration_pending']);

            return;
        }

        $registrationComplete = true;

        foreach ($registrableItems as $item) {
            Log::info('Attempting ResellerClub domain registration', [
                'order' => $order->reference,
                'domain' => $item->domain,
                'years' => $item->years,
            ]);
            $registration = $this->resellerClub->registerDomain($item->domain, $item->years);
            if ($this->registrationSucceeded($registration)) {
                $item->update(['registration_status' => 'registered']);
                Log::info('ResellerClub domain registration succeeded', [
                    'order' => $order->reference,
                    'domain' => $item->domain,
                ]);
            } else {
                $registrationComplete = false;
                Log::warning('ResellerClub domain registration failed', [
                    'order' => $order->reference,
                    'domain' => $item->domain,
                    'response' => $registration,
                ]);
            }
        }

        $order->update(['status' => $registrationComplete ? 'active' : 'registration_pending']);
    }

    /**
     * Refuse checkout unless the configured ResellerClub account can cover
     * a conservative estimate of the live registration prices.
     */
    public function walletPreflightError(array $items)
    {
        $testDomains = config('services.test_domains', []);
        $registrableItems = array_filter($items, function ($item) use ($testDomains) {
            return ($item['order_type'] ?? 'registration') === 'registration'
                && !isset($testDomains[$item['domain']]);
        });

        if (!$registrableItems) {
            return null;
        }

        if (!$this->resellerClub->isConfigured()) {
            return 'Domain registration is temporarily unavailable. Please contact support.';
        }

        if (!config('services.resellerclub.customer_id')) {
            return 'Domain registration account is not fully configured. Please contact support.';
        }

        $nameservers = config('services.resellerclub.nameservers', []);
        if (!is_array($nameservers) || !array_filter(array_map('trim', $nameservers))) {
            return 'Domain nameservers are not configured. Please contact support before placing this order.';
        }

        foreach ($registrableItems as $item) {
            $contacts = ResellerClubDomainContacts::forDomain($item['domain'], [
                'reg' => config('services.resellerclub.reg_contact_id'),
                'admin' => config('services.resellerclub.admin_contact_id'),
                'tech' => config('services.resellerclub.tech_contact_id'),
                'billing' => config('services.resellerclub.billing_contact_id'),
            ]);
            if ($contacts === null) {
                return 'ResellerClub contact IDs are not fully configured. Please add the required contact IDs before registering this domain.';
            }
        }

        $wallet = $this->resellerClub->walletBalance();
        if (!$wallet) {
            return 'We could not verify the ResellerClub wallet balance. No payment has been taken; please try again shortly.';
        }

        $catalog = $this->resellerClub->catalog();
        $priceCurrency = strtoupper((string) config('services.resellerclub.price_currency', 'USD'));
        $required = 0.0;

        foreach ($registrableItems as $item) {
            $parts = explode('.', strtolower($item['domain']));
            $tld = end($parts);
            $plan = $catalog[$tld] ?? null;
            $years = (int) ($item['years'] ?? 1);
            $price = $plan
                ? $this->resellerClub->liveRegistrationPrice($plan['classkey'], $years)
                : null;

            if ($price === null) {
                return 'We could not verify the live ResellerClub price for this domain. No payment has been taken; please try again shortly.';
            }

            $required += $price;
        }

        $required = $this->convertCurrency($required, $priceCurrency, $wallet['currency']);
        if ($required === null) {
            return 'We could not verify the ResellerClub wallet currency. No payment has been taken; please contact support.';
        }

        if ($wallet['balance'] < $required) {
            return 'The ResellerClub account wallet does not have enough available funds to register this domain. No payment has been taken; please contact support.';
        }

        return null;
    }

    private function convertCurrency($amount, $from, $to)
    {
        if ($from === $to) {
            return $amount;
        }

        if (!in_array($from, ['USD', 'BDT'], true) || !in_array($to, ['USD', 'BDT'], true)) {
            return null;
        }

        $rate = app(ExchangeRateService::class)->usdToBdt()['rate'];

        if ($from === 'USD') {
            return $amount * $rate;
        }

        return $amount / $rate;
    }

    private function registrationSucceeded($response)
    {
        return is_array($response)
            && !isset($response['error'])
            && !isset($response['errorcode'])
            && strtoupper((string) ($response['status'] ?? '')) !== 'ERROR';
    }
}
