<?php

namespace App\Support;

use App\User;

/**
 * "Buying for a customer": find an existing, email-verified CloudTechnology
 * customer by email address or customer ID (CT-000123).
 *
 * One rule for every checkout (domains and SSL) so the two can never disagree
 * about who counts as an existing customer.
 */
class CustomerLookup
{
    /** Shown in red under the field when nobody matches. */
    const NOT_FOUND = 'Customer does not exist. Check the email or customer ID and try again.';

    /**
     * @param  mixed  $identifier  email address or customer ID
     * @return \App\User|null
     */
    public static function find($identifier)
    {
        if (!is_scalar($identifier)) {
            return null;
        }

        $identifier = trim((string) $identifier);
        if ($identifier === '') {
            return null;
        }

        $query = User::whereNotNull('email_verified_at');

        return filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? $query->where('email', $identifier)->first()
            : $query->where('customer_uid', $identifier)->first();
    }
}
