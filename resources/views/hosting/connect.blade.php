{{--
    Step 1 of buying hosting: which domain name is the plan for?

    Public page: a guest sees the same thing as a customer. The customer types a
    domain and presses Check: the server asks ResellerClub (the same lookup the
    domain search uses) whether the domain is ALREADY registered. Only then does
    the green tick appear and Continue to Cart unlock (public/js/hosting-connect.js).
    The server checks again in HostingCheckoutController::storeDomain(), so the
    tick is feedback only.

    Data: $quote (HostingOrderService::quote), $domain (prefill),
          $verified (prefill was already checked for this plan).
--}}
@php($sslxCssVersion = @md5_file(public_path('css/ssl-checkout.css')))
@php($hostJsVersion = @md5_file(public_path('js/hosting-connect.js')))
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

            <form class="sslx-form" id="hostConnectForm" method="POST" action="{{ route('hosting.connect.store') }}"
                data-check-url="{{ route('hosting.connect.check') }}" data-verified="{{ !empty($verified) ? '1' : '0' }}" novalidate>
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
                    <input id="hostDomain" name="domain" type="text" inputmode="url" autocomplete="off"
                        autocapitalize="none" spellcheck="false" placeholder="example.com" value="{{ $domain }}"
                        aria-label="Domain name" aria-describedby="hostDomainHint" autofocus>
                    <button type="button" class="sslx-field-check" id="hostCheck">Check</button>
                    <span class="sslx-field-state" aria-hidden="true">✓</span>
                </div>

                <p class="sslx-hint" id="hostDomainHint" data-default="Enter your registered domain, then press Check." aria-live="polite">Enter your registered domain, then press Check.</p>
                <p class="sslx-error" id="hostDomainError" role="alert" @unless($errors->has('domain')) hidden @endunless>@error('domain'){{ $message }}@enderror</p>
                <p class="sslx-hint" id="hostRegisterLink" hidden><a href="{{ route('domains.index') }}">Register this domain →</a></p>

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
                <button class="sslx-btn" id="hostContinue" type="submit" form="hostConnectForm" disabled
                    aria-disabled="true">Continue to Cart <span aria-hidden="true">→</span></button>
            </div>
        </div>
    </main>

    @include('partials.site-footer')
    <script src="{{ asset('js/hosting-connect.js') }}?v={{ $hostJsVersion }}" defer></script>
    @include('partials.chat')
</body>

</html>
