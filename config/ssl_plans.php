<?php

/**
 * SSL certificate plans shown on /services/ssl-certificate (public) and
 * /client/coming-soon/ssl-certificate (client area).
 *
 * MONEY RULE (updated for live pricing)
 * -------------------------------------
 * Prices are no longer typed in by hand for plans that are linked to a
 * ResellerClub product. A plan with `ssl_plans.resellerclub_plan_id` set shows
 * the ResellerClub SELLING price (USD per month, including the reseller's
 * margin) converted to BDT at request time by App\Services\SslPlanService:
 *
 *   total (BDT) = round(usd_per_month × months × ExchangeRateService rate)
 *
 * The rate is cached for six hours, so a figure is stable within a day rather
 * than jittering on every refresh, and domains already price this way
 * (ResellerClubClient::convertLivePriceToBdt).
 *
 * The BDT values in this file are therefore the FALLBACK, used when:
 *   • ResellerClub is disabled, unreachable, or this server's IP is not
 *     whitelisted, or
 *   • the plan has no ResellerClub plan ID (a hand-priced plan), or
 *   • RESELLERCLUB_SSL_LIVE_PRICING=false (the kill switch).
 *
 *   reference rate: ~৳120 per USD (matches services.exchange_rate fallback)
 *   monthly  = the effective per-month price of a 1-year term
 *   renewal  = per-month price charged from the second year onwards
 *
 * `term_discount` is a percentage taken off the full term total when the
 * customer picks a longer term. It applies to the FALLBACK path only: in live
 * mode the terms and their savings come from ResellerClub (which sells 12 and
 * 24 months — there is no 3-year SSL tenure). App\Support\SslPricing still owns
 * the fallback arithmetic.
 *
 * WHEN SSL CHECKOUT IS BUILT it must freeze the BDT amount and the exchange
 * rate it used onto the invoice line, the way InvoiceItem.purchase_price
 * snapshots a domain's cost — otherwise a rate move would change the price
 * between the quote and the charge.
 *
 * SSL checkout does not exist yet: every card's action opens the shared
 * "coming soon" dialog (.coming-soon-trigger). When SSL ordering ships, give
 * the plan a `buy_route` and the partial will link there instead.
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
