<?php

namespace App\Services;

class PaymentGatewayManager
{
    public function methods()
    {
        return config('services.payments.methods', []);
    }

    /**
     * Whether a given gateway has real credentials configured.
     */
    public function gatewayConfigured($gateway)
    {
        if ($gateway === 'bkash') {
            return app(BkashClient::class)->isConfigured();
        }

        return false;
    }

    public function createPendingPayment(array $order, $method)
    {
        return [
            'reference' => 'PAY-' . strtoupper(substr(sha1($order['reference'] . $method . microtime()), 0, 10)),
            'status' => 'pending',
            'method' => $method,
            'amount' => $order['total'],
            'currency' => config('services.payments.currency', 'BDT'),
            'message' => $this->gatewayConfigured($method)
                ? 'Redirecting to the payment gateway.'
                : 'Payment gateway credentials are not configured yet. This order is ready for gateway handoff.',
        ];
    }
}
