{{--
    Step 1 of buying SSL: which domain is the certificate for?

    Public page: a guest sees the same thing as a customer. The Continue button
    stays disabled until the typed name is a valid domain (public/js/ssl-checkout.js);
    the server validates again in SslCheckoutController::storeDomain().

    Data: $quote (SslOrderService::quote), $wildcard (bool), $domain (prefill).
--}}
@php($sslxCssVersion = @md5_file(public_path('css/ssl-checkout.css')))
@php($sslxJsVersion = @md5_file(public_path('js/ssl-checkout.js')))
@php($plan = $quote['plan'])
@php($term = $quote['term'])
<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'SSL Certificate — Connect your domain'])
    <link rel="stylesheet" href="{{ asset('css/ssl-checkout.css') }}?v={{ $sslxCssVersion }}">
</head>
<body class="cs-page">
@include('partials.site-navbar', ['navActive' => null])

<main class="sslx">
    <div class="sslx-wrap">
        <nav class="sslx-crumbs" aria-label="Breadcrumb">
            <a href="{{ route('landing') }}">Home</a><span aria-hidden="true">›</span>
            <a href="{{ route('services.coming-soon', 'ssl-certificate') }}">SSL Certificates</a><span aria-hidden="true">›</span>
            <strong>Connect domain</strong>
        </nav>
    </div>

    <section class="sslx-wrap sslx-hero" aria-labelledby="sslxTitle">
        <span class="sslx-shield" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 4.5-3 7.6-7 9-4-1.4-7-4.5-7-9V6z"/><path d="M9.2 12l2 2 3.6-3.8"/></svg>
        </span>
        <h1 id="sslxTitle">Which website is this certificate for?</h1>
        <p>Enter the domain name you want to secure.</p>

        <form class="sslx-form" id="sslConnectForm" method="POST" action="{{ route('ssl.connect.store') }}" novalidate>
            @csrf
            <input type="hidden" name="plan" value="{{ $plan['key'] }}">
            <input type="hidden" name="term" value="{{ $quote['years'] }}">

            <div class="sslx-field">
                <span class="sslx-field-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.6 2.6 4 5.6 4 9s-1.4 6.4-4 9c-2.6-2.6-4-5.6-4-9s1.4-6.4 4-9z"/></svg>
                </span>
                <input id="sslDomain" name="domain" type="text" inputmode="url" autocomplete="off" autocapitalize="none" spellcheck="false"
                       placeholder="example.com" value="{{ $domain }}" data-wildcard="{{ $wildcard ? '1' : '0' }}"
                       aria-label="Domain name" aria-describedby="sslDomainHint" autofocus>
                <span class="sslx-field-state" aria-hidden="true">✓</span>
            </div>

            @php($defaultHint = $wildcard ? 'Wildcard plan: we will secure every subdomain, like *.example.com.' : 'Example: example.com or www.example.com')
            <p class="sslx-hint" id="sslDomainHint" data-default="{{ $defaultHint }}" aria-live="polite">{{ $defaultHint }}</p>
            @error('domain')<p class="sslx-error" role="alert">{{ $message }}</p>@enderror

            <ul class="sslx-points" style="margin-top:26px">
                <li>Price shown in Taka</li>
                <li>No domain purchase needed</li>
                <li>Pay with bKash or Nagad</li>
            </ul>
        </form>
    </section>

    <div class="sslx-bar">
        <div class="sslx-wrap sslx-bar-inner">
            <span class="sslx-bar-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v5c0 4.5-3 7.6-7 9-4-1.4-7-4.5-7-9V6z"/></svg>
            </span>
            <div class="sslx-bar-plan">
                <strong>{{ $plan['name'] }}</strong>
                <small>{{ $term['label'] }}</small>
            </div>
            <div class="sslx-bar-price">
                <strong>৳{{ \App\Support\SslPricing::money($quote['total']) }}</strong>
                <small>total for {{ strtolower($term['label']) }}</small>
            </div>
            <button class="sslx-btn" id="sslContinue" type="submit" form="sslConnectForm" disabled aria-disabled="true">Continue to Cart <span aria-hidden="true">→</span></button>
        </div>
    </div>
</main>

@include('partials.site-footer')
<script src="{{ asset('js/ssl-checkout.js') }}?v={{ $sslxJsVersion }}" defer></script>
@include('partials.chat')
</body>
</html>
