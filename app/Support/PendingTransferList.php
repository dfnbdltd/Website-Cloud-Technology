<?php

namespace App\Support;

/**
 * Reads ResellerClub's v2 "List pending domain transfers" answer
 * (GET /domain-transfers/pending): the inbound transfers that are still in
 * progress. A domain on this list is, by definition, not finished yet; a
 * domain missing from it has either completed or failed and must be checked.
 *
 * Documented response:
 *   { "transfers": [ { "actionId", "actionStatus", "addedOn", "customerId",
 *                      "domainName", "lastUpdatedOn", "orderId", "resellerId" } ],
 *     "totalItems": 0, "itemsOnPage": 0, "status": "...", "message": "..." }
 *
 * Pure and static (no framework, no network) so it can be unit-tested.
 */
class PendingTransferList
{
    /** actionStatus text that means the transfer has already ended badly. */
    const FAILED_PATTERN = '/fail|reject|cancel|declin|expire/i';

    /**
     * @param  mixed  $response  decoded JSON
     * @return array|null  ['transfers' => [domain => row], 'total' => int, 'count' => int]
     *                     or null when the answer cannot be trusted as a list
     */
    public static function parse($response)
    {
        if (!is_array($response)) {
            return null;
        }

        // An error body must never read as "nothing is pending".
        $status = strtolower(trim((string) (isset($response['status']) && is_scalar($response['status']) ? $response['status'] : '')));
        if (in_array($status, ['error', 'failed', 'failure'], true) || isset($response['error'])) {
            return null;
        }

        if (isset($response['transfers']) && is_array($response['transfers'])) {
            $rows = $response['transfers'];
        } elseif (isset($response['totalItems']) && (int) $response['totalItems'] === 0) {
            $rows = []; // some answers omit the empty array
        } else {
            return null;
        }

        $transfers = [];
        foreach ($rows as $row) {
            if (!is_array($row) || empty($row['domainName']) || !is_scalar($row['domainName'])) {
                continue;
            }

            $orderId = isset($row['orderId']) && is_scalar($row['orderId']) ? trim((string) $row['orderId']) : '';
            $transfers[strtolower(trim((string) $row['domainName']))] = [
                'domain' => strtolower(trim((string) $row['domainName'])),
                'order_id' => ctype_digit($orderId) && (int) $orderId > 0 ? $orderId : null,
                'action_status' => isset($row['actionStatus']) && is_scalar($row['actionStatus']) ? (string) $row['actionStatus'] : '',
                'added_on' => isset($row['addedOn']) && is_scalar($row['addedOn']) ? (string) $row['addedOn'] : '',
                'last_updated_on' => isset($row['lastUpdatedOn']) && is_scalar($row['lastUpdatedOn']) ? (string) $row['lastUpdatedOn'] : '',
            ];
        }

        return [
            'transfers' => $transfers,
            'total' => isset($response['totalItems']) && is_numeric($response['totalItems']) ? (int) $response['totalItems'] : count($transfers),
            'count' => count($rows),
        ];
    }

    /**
     * @param  string  $actionStatus
     * @return bool
     */
    public static function looksFailed($actionStatus)
    {
        return (bool) preg_match(self::FAILED_PATTERN, (string) $actionStatus);
    }
}
