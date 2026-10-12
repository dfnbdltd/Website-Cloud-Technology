<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'test_domains' => [
        'wallet-test.ctbd.test' => ['price' => 1.00, 'renewal' => 1.00],
        'bkash-test.ctbd.test' => ['price' => 1.00, 'renewal' => 1.00],
    ],

    'resellerclub' => [
        'enabled' => env('RESELLERCLUB_ENABLED', false),
        'auth_userid' => env('RESELLERCLUB_AUTH_USERID'),
        'api_key' => env('RESELLERCLUB_HTTP_API_KEY'),
        'legacy_api_key' => env('RESELLERCLUB_API_KEY'),
        'reseller_id' => env('RESELLERCLUB_RESELLER_ID'),
        'customer_id' => env('RESELLERCLUB_CUSTOMER_ID'),
        'auto_register' => env('RESELLERCLUB_AUTO_REGISTER', false),
        'demo_mode' => env('RESELLERCLUB_DEMO_MODE', false),
        'sandbox' => env('RESELLERCLUB_SANDBOX', false),
        'country' => env('RESELLERCLUB_COUNTRY', 'BD'),
        'phone_cc' => env('RESELLERCLUB_PHONE_CC', '880'),
        'base_url' => env('RESELLERCLUB_BASE_URL', 'https://httpapi.com/api'),
    ],

    'payments' => [
        'currency' => 'BDT',
        'methods' => [
            'bkash' => ['label' => 'bKash', 'description' => 'Pay securely with bKash'],
            'nagad' => ['label' => 'Nagad', 'description' => 'Pay securely with Nagad'],
            'bank' => ['label' => 'Bank transfer', 'description' => 'Manual verification by our billing team'],
        ],
    ],

    // -------------------------------------------------------------------------
    // (Removed) DFN ERP middleware — bKash now runs directly via services.bkash.
    // -------------------------------------------------------------------------

    // -------------------------------------------------------------------------
    // bKash Checkout (PGW popup) API — direct integration, production URLs.
    // Server-side flow (see app/Services/BkashClient.php):
    //   POST {base}/checkout/token/grant         -> id_token (headers: username, password)
    //   POST {base}/checkout/payment/create      -> paymentID (called via bKash JS popup)
    //   POST {base}/checkout/payment/execute/{id}-> finalizes after popup authorization
    // Frontend loads https://scripts.pay.bka.sh/versions/1.2.0-beta/checkout/bKash-checkout.js
    // and calls bKash.init({paymentMode:'checkout'}). Credentials stay server-side.
    // -------------------------------------------------------------------------
    'bkash' => [
        'sandbox' => env('BKASH_SANDBOX', false),
        'base_url' => env('BKASH_BASE_URL', 'https://checkout.pay.bka.sh/v1.2.0-beta'),
        'app_key' => env('BKASH_APP_KEY'),
        'app_secret' => env('BKASH_APP_SECRET'),
        'username' => env('BKASH_USERNAME'),
        'password' => env('BKASH_PASSWORD'),
    ],

    'recaptcha' => [
        'secret' => env('RECAPTCHA_SECRET_KEY'),
    ],

    'revechat' => [
        'account' => env('REVECHAT_ACCOUNT', '7494338'),
    ],

    'contact' => [
        'to' => env('CONTACT_TO_ADDRESS', env('MAIL_FROM_ADDRESS')),
    ],

    'admin' => [
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
