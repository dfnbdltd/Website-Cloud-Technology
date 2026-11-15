<?php

namespace App\Support;

/**
 * Turns ResellerClub's "search actions" responses into plain activity rows.
 *
 * Pure and static (no framework, no network) so it can be unit-tested against
 * real response samples.
 *
 * Endpoints (legacy HTTP API):
 *   GET /api/actions/search-current.json   pending actions + completed in the last 24h
 *   GET /api/actions/search-archived.json  completed actions older than 24h
 *
 * A response is a hash keyed by record number, plus counters:
 *   { "recsonpage": "2", "recsindb": "2",
 *     "1": { "eaqid": "…", "entityid": "<order id>", "actiontype": "ModContact",
 *            "actiontypedesc": "Modification of Contact Details of example.com",
 *            "actionstatus": "Success", "actionstatusdesc": "…",
 *            "actionadded": "1794308804", "actionstarted": "…", "actioncompleted": "…" },
 *     "2": { … } }
 *
 * Timestamps are Unix epoch seconds (UTC).
 */
class DomainActivity
{
    const STATE_COMPLETED = 'completed';
    const STATE_FAILED = 'failed';
    const STATE_PENDING = 'pending';

    /**
     * @param  mixed  $response  decoded JSON from one search-actions call
     * @return array|null  ['total' => int|null, 'actions' => array of rows],
     *                     or null when the response is an error / not usable
     */
    public static function parseResponse($response)
    {
        if (!is_array($response)) {
            return null;
        }

        if (strtoupper((string) (isset($response['status']) && is_scalar($response['status']) ? $response['status'] : '')) === 'ERROR') {
            return null;
        }

        $actions = [];
        foreach ($response as $key => $record) {
            if (!is_array($record) || !ctype_digit((string) $key)) {
                continue;
            }

            $row = self::normalize($record);
            if ($row !== null) {
                $actions[] = $row;
            }
        }

        $total = isset($response['recsindb']) && is_numeric($response['recsindb'])
            ? (int) $response['recsindb']
            : null;

        return ['total' => $total, 'actions' => $actions];
    }

    /**
     * Merge several parsed lists (current + archived): drop repeats, newest first.
     *
     * @param  array  ...$lists  arrays of rows from parseResponse()
     * @return array
     */
    public static function merge(array ...$lists)
    {
        $unique = [];
        foreach ($lists as $list) {
            foreach ($list as $row) {
                $unique[$row['id'] !== '' ? 'eaq:' . $row['id'] : 'row:' . md5(json_encode($row))] = $row;
            }
        }

        $rows = array_values($unique);
        usort($rows, function ($a, $b) {
            $byTime = ($b['at'] ?: 0) <=> ($a['at'] ?: 0);
            if ($byTime !== 0) {
                return $byTime;
            }

            return (int) $b['id'] <=> (int) $a['id'];
        });

        return $rows;
    }

    /**
     * @param  array  $record  one numbered record of the response
     * @return array|null
     */
    public static function normalize(array $record)
    {
        $type = self::text($record, 'actiontype');
        $title = self::text($record, 'actiontypedesc');
        if ($title === '') {
            $title = $type !== '' ? $type : 'Domain action';
        }

        $status = self::text($record, 'actionstatus');
        $statusDesc = self::text($record, 'actionstatusdesc');
        $added = self::timestamp(isset($record['actionadded']) ? $record['actionadded'] : null);
        $started = self::timestamp(isset($record['actionstarted']) ? $record['actionstarted'] : null);
        $completed = self::timestamp(isset($record['actioncompleted']) ? $record['actioncompleted'] : null);

        if ($type === '' && $title === 'Domain action' && !$added && !$started && !$completed) {
            return null;
        }

        $state = self::state($status, $statusDesc, $completed);

        return [
            'id' => self::text($record, 'eaqid'),
            'order_id' => self::text($record, 'entityid'),
            'type' => $type,
            'title' => $title,
            'status' => $status,
            'description' => $statusDesc,
            'state' => $state,
            'state_label' => self::stateLabel($state),
            'added_at' => $added,
            'started_at' => $started,
            'completed_at' => $completed,
            'at' => $completed ?: ($started ?: $added),
        ];
    }

    public static function state($status, $statusDesc, $completedAt)
    {
        $status = strtolower(trim((string) $status));
        $both = $status . ' ' . strtolower(trim((string) $statusDesc));

        if (preg_match('/fail|error|reject|denied|cancel/', $both)) {
            return self::STATE_FAILED;
        }

        if (preg_match('/success|complete/', $status)) {
            return self::STATE_COMPLETED;
        }

        return $completedAt ? self::STATE_COMPLETED : self::STATE_PENDING;
    }

    public static function stateLabel($state)
    {
        switch ($state) {
            case self::STATE_FAILED:
                return 'Failed';
            case self::STATE_PENDING:
                return 'Pending';
            default:
                return 'Completed';
        }
    }

    /**
     * Epoch seconds (or a date string) to an int, or null for "not set" (0, empty).
     *
     * @param  mixed  $value
     * @return int|null
     */
    public static function timestamp($value)
    {
        if (!is_scalar($value) || trim((string) $value) === '') {
            return null;
        }

        $value = trim((string) $value);
        if (is_numeric($value)) {
            $seconds = (float) $value;
            if ($seconds > 100000000000) {
                $seconds = $seconds / 1000;
            }

            return $seconds > 0 ? (int) $seconds : null;
        }

        $parsed = strtotime($value);

        return $parsed === false || $parsed <= 0 ? null : $parsed;
    }

    private static function text(array $record, $key)
    {
        return isset($record[$key]) && is_scalar($record[$key]) ? trim((string) $record[$key]) : '';
    }
}
