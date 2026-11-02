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
        if (!config('services.resellerclub.auto_register') || $order->payment_status !== 'paid') {
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

        $registrationComplete = true;
        $hasRegistrableDomain = false;
        $testDomains = config('services.test_domains', []);

        foreach ($order->items as $item) {
            if (isset($testDomains[$item->domain])) {
                continue;
            }

            $hasRegistrableDomain = true;
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

        if ($hasRegistrableDomain) {
            $order->update(['status' => $registrationComplete ? 'active' : 'registration_pending']);
        }
    }

    private function registrationSucceeded($response)
    {
        return is_array($response)
            && !isset($response['error'])
            && !isset($response['errorcode'])
            && strtoupper((string) ($response['status'] ?? '')) !== 'ERROR';
    }
}
