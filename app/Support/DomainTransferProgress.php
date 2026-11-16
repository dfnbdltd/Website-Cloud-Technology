<?php

namespace App\Support;

/**
 * Decides, from what ResellerClub reports, how far a domain transfer-in is.
 *
 * Pure and static (no framework, no network) so it can be unit-tested against
 * response samples. Two independent signals are read:
 *
 *  1. The order's action list (DomainActivity rows, newest first). The transfer
 *     itself is one action, e.g. "Transfer of example.com from old Registrar
 *     along with 1 year Renewal" — pending while the losing registrar has not
 *     released the domain, Success once it has, Failed when it is rejected.
 *  2. The order details' `currentstatus`: "Active" means the domain now lives
 *     in the reseller account, which can only be true once the transfer is done.
 *
 * Failure wins over success: a rejected transfer must never be reported as done.
 * With nothing readable the answer is "pending" — the caller treats an
 * unreadable ResellerClub as "unknown" before it ever gets here.
 */
class DomainTransferProgress
{
    const COMPLETED = 'completed';
    const FAILED = 'failed';
    const PENDING = 'pending';

    /** ResellerClub's action type of a transfer-in (AddTransferDomain and variants). */
    const TYPE_PATTERN = '/add\s*transfer|transfer\s*-?in/i';
    /** The human description of the same action, as shown in the panel's Latest Activity. */
    const TITLE_PATTERN = '/^\s*transfer\s+of\s+\S+\s+from\b/i';

    /**
     * @param  array|null  $actions  rows from DomainActivity::merge(), newest first
     * @param  array|null  $details  decoded domains/details.json
     * @return array  ['state' => completed|failed|pending, 'expires_at' => int|null,
     *                 'completed_at' => int|null, 'reason' => string]
     */
    public static function evaluate($actions, $details)
    {
        $expiresAt = is_array($details) && isset($details['endtime'])
            ? DomainActivity::timestamp($details['endtime'])
            : null;
        $active = is_array($details)
            && strtolower(trim((string) (isset($details['currentstatus']) && is_scalar($details['currentstatus']) ? $details['currentstatus'] : ''))) === 'active';

        $transfer = self::latestTransferAction(is_array($actions) ? $actions : []);

        if ($transfer !== null && $transfer['state'] === DomainActivity::STATE_FAILED) {
            return self::result(self::FAILED, $expiresAt, null, 'transfer action failed');
        }

        if ($transfer !== null && $transfer['state'] === DomainActivity::STATE_COMPLETED) {
            return self::result(self::COMPLETED, $expiresAt, $transfer['completed_at'] ?: $transfer['at'], 'transfer action completed');
        }

        if ($active) {
            return self::result(self::COMPLETED, $expiresAt, null, 'order is active');
        }

        return self::result(self::PENDING, $expiresAt, null, $transfer === null ? 'no transfer action yet' : 'transfer action pending');
    }

    /**
     * @param  array  $actions
     * @return array|null
     */
    public static function latestTransferAction(array $actions)
    {
        foreach ($actions as $action) {
            if (!is_array($action)) {
                continue;
            }

            $type = isset($action['type']) ? (string) $action['type'] : '';
            $title = isset($action['title']) ? (string) $action['title'] : '';
            if (preg_match(self::TYPE_PATTERN, $type) || preg_match(self::TITLE_PATTERN, $title)) {
                return $action;
            }
        }

        return null;
    }

    private static function result($state, $expiresAt, $completedAt, $reason)
    {
        return [
            'state' => $state,
            'expires_at' => $expiresAt,
            'completed_at' => $completedAt,
            'reason' => $reason,
        ];
    }
}
