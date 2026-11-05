<?php

namespace App\Support;

class DomainTransferStatus
{
    public static function forOrderItem($orderStatus, $paymentStatus, $itemStatus)
    {
        if ($orderStatus === 'active') {
            return [
                'key' => 'success',
                'label' => 'Success',
                'message' => 'The domain transfer is complete.',
            ];
        }

        if ($orderStatus === 'transfer_failed' || $itemStatus === 'transfer_failed') {
            return [
                'key' => 'failed',
                'label' => 'Failed',
                'message' => 'Please contact support with your order reference.',
            ];
        }

        if ($paymentStatus !== 'paid') {
            return [
                'key' => 'payment-pending',
                'label' => 'Payment Pending',
                'message' => 'Transfer processing starts after payment is confirmed.',
            ];
        }

        if ($orderStatus === 'transfer_pending' || $itemStatus === 'transfer_submitted') {
            return [
                'key' => 'pending',
                'label' => 'Pending',
                'message' => 'The request was accepted by the registrar; completion may take 5–7 days.',
            ];
        }

        return [
            'key' => 'initialized',
            'label' => 'Transfer Initialized',
            'message' => 'The transfer request is being submitted to the registrar.',
        ];
    }
}
