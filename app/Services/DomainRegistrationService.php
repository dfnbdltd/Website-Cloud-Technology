<?php

namespace App\Services;

use App\Models\DomainOrder;
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
            return;
        }

        $order->loadMissing('items');
        $testDomains = config('services.test_domains', []);
        $registrableItems = $order->items->filter(function ($item) use ($testDomains) {
            return !isset($testDomains[$item->domain]);
        });
        if ($registrableItems->isEmpty()) {
            return;
        }

        $order = DB::transaction(function () use ($order) {
            $lockedOrder = DomainOrder::whereKey($order->id)->lockForUpdate()->first();
            if (!$lockedOrder || $lockedOrder->payment_status !== 'paid' || $lockedOrder->status !== 'paid') {
                return null;
            }

            $lockedOrder->update(['status' => 'registration_pending']);

            return $lockedOrder->load('items');
        });

        if (!$order) {
            return;
        }

        $walletError = $this->walletPreflightError($order->items->toArray());
        if ($walletError) {
            Log::warning('ResellerClub registration held because wallet pre-check failed', [
                'order' => $order->reference,
                'reason' => $walletError,
            ]);

            return;
        }

        $registrationComplete = true;
        $testDomains = config('services.test_domains', []);

        foreach ($order->items as $item) {
            if (isset($testDomains[$item->domain])) {
                continue;
            }

            $registration = $this->resellerClub->registerDomain($item->domain, $item->years);
            if (!$this->registrationSucceeded($registration)) {
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
            return !isset($testDomains[$item['domain']]);
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
