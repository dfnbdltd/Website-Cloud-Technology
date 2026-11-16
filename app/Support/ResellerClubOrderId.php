<?php

namespace App\Support;

/**
 * Finds the ResellerClub domain order ID in a transfer/registration response.
 *
 * The legacy transfer.json documents it as `entityid` ("Order ID of the Domain
 * Name Order"). The v2 transfer-in response is not documented for us, so the
 * usual spellings are accepted at the top level and under `data` / `result`.
 * `customerid` is deliberately NOT a candidate: it is not the order.
 *
 * Pure and static so it can be unit-tested without the framework.
 */
class ResellerClubOrderId
{
    const KEYS = ['entityid', 'entityId', 'orderid', 'orderId', 'order-id', 'order_id'];

    /**
     * @param  mixed  $response
     * @return string|null  digits only, or null when the response carries no order ID
     */
    public static function fromResponse($response)
    {
        if (!is_array($response)) {
            return null;
        }

        $candidates = [$response];
        foreach (['data', 'result'] as $wrapper) {
            if (isset($response[$wrapper]) && is_array($response[$wrapper])) {
                $candidates[] = $response[$wrapper];
            }
        }

        foreach ($candidates as $candidate) {
            foreach (self::KEYS as $key) {
                if (!isset($candidate[$key]) || !is_scalar($candidate[$key])) {
                    continue;
                }

                $value = trim((string) $candidate[$key]);
                if (ctype_digit($value) && (int) $value > 0) {
                    return $value;
                }
            }
        }

        return null;
    }
}
