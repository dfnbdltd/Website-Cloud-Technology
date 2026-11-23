{{--
    Step 1 of buying hosting: which domain name is the plan for?

    Public page: a guest sees the same thing as a customer. The Continue button
    stays disabled until the typed name is a valid domain (public/js/ssl-checkout.js,
    shared with the SSL flow); the server validates again in
    HostingCheckoutController::storeDomain().

    Data: $quote (HostingOrderService::quote), $domain (prefill).
--}}
@php($sslxCssVersion = @md5_file(public_path('css/ssl-checkout.css')))
@php($sslxJsVersion = @md5_file(public_path('js/ssl-checkout.js')))
<!doctype html>
<html lang="en">

<head>
    @include('partials.head', ['title' => 'Hosting — Connect Domain Name'])
    <link rel="stylesheet" href="{{ asset('css/ssl-checkout.css') }}?v={{ $sslxCssVersion }}">
</head>

<body class="cs-page">
    @include('partials.site-navbar', ['navActive' => 'hosting'])

    <main class="sslx">
        <div class="sslx-wrap">
            <nav class="sslx-crumbs" aria-label="Breadcrumb">
                <a href="{{ route('landing') }}">Home</a><span aria-hidden="true">›</span>
                <a href="{{ $quote['plans_url'] }}">{{ $quote['product'] }}</a><span aria-hidden="true">›</span>
                <strong>Connect Domain Name</strong>
            </nav>
        </div>

        <section class="sslx-wrap sslx-hero" aria-labelledby="sslxTitle">
            <span class="sslx-shield" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M3 12h18M12 3c2.6 2.6 4 5.6 4 9s-1.4 6.4-4 9c-2.6-2.6-4-5.6-4-9s1.4-6.4 4-9z" />
                </svg>
            </span>
            <h1 id="sslxTitle">Connect Domain Name</h1>
            <p>Enter a domain name to associate with the order</p>

            <form class="sslx-form" id="sslConnectForm" method="POST" action="{{ route('hosting.connect.store') }}" novalidate>
                @csrf
                <input type="hidden" name="region" value="{{ $quote['region'] }}">
                <input type="hidden" name="type" value="{{ $quote['type'] }}">
                <input type="hidden" name="plan" value="{{ $quote['plan_id'] }}">
                <input type="hidden" name="period" value="{{ $quote['period'] }}">

                <div class="sslx-field">
                    <span class="sslx-field-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M3 12h18M12 3c2.6 2.6 4 5.6 4 9s-1.4 6.4-4 9c-2.6-2.6-4-5.6-4-9s1.4-6.4 4-9z" />
                        </svg>
                    </span>
                    <input id="sslDomain" name="domain" type="text" inputmode="url" autocomplete="off"
                        autocapitalize="none" spellcheck="false" placeholder="example.com" value="{{ $domain }}"
                        data-wildcard="0" aria-label="Domain name" aria-describedby="sslDomainHint" autofocus>
                    <span class="sslx-field-state" aria-hidden="true">✓</span>
                </div>

                <p class="sslx-hint" id="sslDomainHint" data-default="Example: example.com"
                    data-valid-prefix="This plan will be connected to"
                    data-wild-message="Enter a plain domain name, like example.com." aria-live="polite">Example: example.com</p>
                @error('domain')<p class="sslx-error" role="alert">{{ $message }}</p>@enderror

                <ul class="sslx-points" style="margin-top:26px">
                    <li>Pay with bKash or Nagad</li>
                </ul>
            </form>
        </section>

        <div class="sslx-bar">
            <div class="sslx-wrap sslx-bar-inner">
                <span class="sslx-bar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="6" rx="1.5" />
                        <rect x="3" y="14" width="18" height="6" rx="1.5" />
                        <path d="M7 7h.01M7 17h.01" />
                    </svg>
                </span>
                <div class="sslx-bar-plan">
                    <strong>{{ $quote['product'] }} - {{ $quote['name'] }}</strong>
                    @if($quote['specs'] !== '')<small>{{ $quote['specs'] }}</small>@else<small>{{ $quote['period_label'] }} billing</small>@endif
                </div>
                <div class="sslx-bar-price">
                    <strong>৳{{ \App\Services\HostingOrderService::money($quote['total']) }}</strong>
                    <small>per {{ $quote['unit'] }}</small>
                </div>
                <button class="sslx-btn" id="sslContinue" type="submit" form="sslConnectForm" disabled
                    aria-disabled="true">Continue to Cart <span aria-hidden="true">→</span></button>
            </div>
        </div>
    </main>

    @include('partials.site-footer')
    <script src="{{ asset('js/ssl-checkout.js') }}?v={{ $sslxJsVersion }}" defer></script>
    @include('partials.chat')
</body>

</html>
