<?php

/**
 * SSL certificate plans shown on /services/ssl-certificate (public) and
 * /client/coming-soon/ssl-certificate (client area).
 *
 * MONEY RULE: every price here is already in BDT (৳) and is the single source
 * of truth. Nothing is converted at request time — a marketing price that moves
 * with the USD rate would change between the quote and the checkout, so the
 * USD figure is kept only as a reference comment next to each plan.
 *
 *   reference rate: ~৳120 per USD (matches services.exchange_rate fallback)
 *   monthly  = the effective per-month price of a 1-year term
 *   renewal  = per-month price charged from the second year onwards
 *
 * `term_discount` is a percentage taken off the full term total when the
 * customer picks a longer term (server computes it, see App\Support\SslPricing).
 *
 * Hosting/SSL checkout does not exist yet: every card's action opens the
 * shared "coming soon" dialog (.coming-soon-trigger). When SSL ordering ships,
 * give the plan a `buy_route` and the partial will link there instead.
 */
return [

    // Effective per-month price of the default (1 year) term.
    'terms' => [
        1 => '1 Year',
        2 => '2 Years',
        3 => '3 Years',
    ],

    // Percent off the whole term total. 0 = no discount for that term.
    'term_discount' => [
        1 => 0,
        2 => 5,
        3 => 10,
    ],

    // Default term highlighted in the card select.
    'default_term' => 1,

    'plans' => [

        'positive-ssl' => [
            'name' => 'Positive SSL',
            'tagline' => 'Validation & encryption on a budget',
            // ≈ $0.66/mo, renews ≈ $1.66/mo
            'monthly' => 79,
            'renewal' => 199,
            'features' => [
                'Domain Validation',
                '1 Sub-domain',
                'SHA2 & ECC 128/256-bit Encryption',
                'Trust Logo Supported',
                'Issued within 2 days',
                'Free Reissuance',
                '$10,000 Warranty',
                '30-day Money Back',
            ],
        ],

        'comodo-ssl' => [
            'name' => 'Comodo SSL',
            'tagline' => 'For businesses & enterprise',
            // ≈ $3.33/mo, renews ≈ $3.33/mo
            'monthly' => 399,
            'renewal' => 399,
            'badge' => 'Most popular',
            'features' => [
                'Domain Validation',
                '1 Sub-domain',
                'SHA2 & ECC 128/256-bit Encryption',
                'Trust Logo Supported',
                'Issued within 2 days',
                'Free Reissuance',
                '$500,000 Warranty',
                '30-day Money Back',
            ],
        ],

        'positive-ssl-wildcard' => [
            'name' => 'Positive SSL Wildcard',
            'tagline' => '1 SSL for all your subdomains',
            // ≈ $4.99/mo, renews ≈ $4.99/mo
            'monthly' => 599,
            'renewal' => 599,
            'features' => [
                'Domain Validation',
                'Unlimited Sub-domains',
                'SHA2 & ECC 128/256-bit Encryption',
                'Trust Logo Supported',
                'Issued within 2 days',
                'Free Reissuance',
                '$10,000 Warranty',
                '30-day Money Back',
            ],
        ],

        'ev-ssl' => [
            'name' => 'EV SSL',
            'tagline' => 'Complete validation for businesses',
            // ≈ $10.83/mo, renews ≈ $14.16/mo
            'monthly' => 1299,
            'renewal' => 1699,
            'features' => [
                'Full Organization Validation',
                '1 Domain + Unlimited Sub-domains',
                'SHA2 & ECC 256-bit Encryption',
                'Green Address Bar',
                'Issued within 5–7 days',
                'Free Reissuance',
                '$1,000,000 Warranty',
                '30-day Money Back',
            ],
        ],

    ],

];
