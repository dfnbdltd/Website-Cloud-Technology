{{--
    Step 1 of buying hosting: which domain name is the plan for?

    Public page: a guest sees the same thing as a customer. The customer types a
    domain and presses Check: the server asks ResellerClub (the same lookup the
    domain search uses) whether the domain is ALREADY registered. Only then does
    the green tick appear and Continue to Cart unlock (public/js/hosting-connect.js).
    The server checks again in HostingCheckoutController::storeDomain(), so the
    tick is feedback only.

    Three option cards above the search box decide how Check works (browser AND server;
    a hidden select #hostMode holds the value). Register is the default.
      own      Use my domain: must already be registered (ResellerClub)
      register Register a new domain: must be free; the customer then goes through the
               normal domain register flow and comes back to hosting checkout
      transfer Transfer a domain: must be eligible; same idea through the transfer flow
    The server repeats the same check in storeDomain(); the tick is feedback only.

    Data: $quote (HostingOrderService::quote), $domain (prefill),
          $verified (prefill was already checked for this plan),
          $mode (own|register|transfer), $pendingDomain (left for the domain flow, not finished).
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

            @php($modes = [
                'register' => ['title' => 'Register a New Domain', 'text' => 'Search a new name and register it with your hosting.', 'placeholder' => 'mynewbrand.com', 'hint' => 'Type the domain you want to register, then press Search.', 'button' => 'Search'],
                'transfer' => ['title' => 'Transfer Domain', 'text' => 'Move your domain from another registrar to us.', 'placeholder' => 'domain-to-transfer.com', 'hint' => 'Enter the domain you want to move to us, then press Validate.', 'button' => 'Validate'],
                'own' => ['title' => 'Use My Domain', 'text' => 'Already registered with us? Connect it to this plan.', 'placeholder' => 'example.com', 'hint' => 'Enter your registered domain, then press Check.', 'button' => 'Check'],
            ])
            <form class="sslx-form sslx-form-host" id="hostConnectForm" method="POST" action="{{ route('hosting.connect.store') }}"
                data-check-url="{{ route('hosting.connect.check') }}" data-verified="{{ !empty($verified) ? '1' : '0' }}" data-mode="{{ $mode }}" novalidate>
                @csrf
                <input type="hidden" name="region" value="{{ $quote['region'] }}">
                <input type="hidden" name="type" value="{{ $quote['type'] }}">
                <input type="hidden" name="plan" value="{{ $quote['plan_id'] }}">
                <input type="hidden" name="period" value="{{ $quote['period'] }}">

                {{-- Pick what you need. The native select below stays in sync (the JS and the form read it). --}}
                <div class="host-options" role="radiogroup" aria-label="What do you need?">
                    @foreach($modes as $key => $meta)
                        <button type="button" class="host-option {{ $mode === $key ? 'is-active' : '' }}" data-mode-card="{{ $key }}" role="radio" aria-checked="{{ $mode === $key ? 'true' : 'false' }}">
                            <span class="host-option-tick" aria-hidden="true"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3.5 8.5l3 3 6-7"/></svg></span>
                            <span class="host-option-icon" aria-hidden="true">
                                @if($key === 'register')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="12" r="8"/><path d="M3 12h16M11 4c2.4 2.4 3.6 5 3.6 8s-1.2 5.6-3.6 8c-2.4-2.4-3.6-5-3.6-8S8.6 6.4 11 4zM19 3v5M16.5 5.5h5"/></svg>
                                @elseif($key === 'transfer')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h14l-3.5-3.5M20 16H6l3.5 3.5"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></svg>
                                @endif
                            </span>
                            <strong>{{ $meta['title'] }}</strong>
                            <small>{{ $meta['text'] }}</small>
                        </button>
                    @endforeach
                </div>
                <select id="hostMode" name="mode" class="host-mode-native" tabindex="-1" aria-hidden="true">
                    @foreach($modes as $key => $meta)
                        <option value="{{ $key }}" {{ $mode === $key ? 'selected' : '' }}>{{ $meta['title'] }}</option>
                    @endforeach
                </select>

                <div class="sslx-field">
                    <span class="sslx-field-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M3 12h18M12 3c2.6 2.6 4 5.6 4 9s-1.4 6.4-4 9c-2.6-2.6-4-5.6-4-9s1.4-6.4 4-9z" />
                        </svg>
                    </span>
                    <input id="hostDomain" name="domain" type="text" inputmode="url" autocomplete="off"
                        autocapitalize="none" spellcheck="false" placeholder="{{ $modes[$mode]['placeholder'] }}" value="{{ $domain }}"
                        aria-label="Domain name" aria-describedby="hostDomainHint" autofocus>
                    <button type="button" class="sslx-field-check" id="hostCheck">{{ $modes[$mode]['button'] }}</button>
                    <span class="sslx-field-state" aria-hidden="true">✓</span>
                </div>

                {{-- Transfer only: the authorization (EPP) code from the current registrar. --}}
                <div class="host-auth" id="hostAuthRow" @if($mode !== 'transfer') hidden @endif>
                    <input id="hostAuth" name="auth_code" type="text" autocomplete="off" autocapitalize="none" spellcheck="false"
                        placeholder="Authorization (EPP) code" aria-label="Authorization code" class="{{ $errors->has('auth_code') ? 'is-invalid' : '' }}">
                    <small>Get it from your current registrar. It is only used for this transfer.</small>
                </div>

                <p class="sslx-hint" id="hostDomainHint" data-default="{{ $modes[$mode]['hint'] }}" aria-live="polite">{{ $modes[$mode]['hint'] }}</p>
                <p class="sslx-error" id="hostDomainError" role="alert" @unless($errors->has('domain') || $errors->has('auth_code')) hidden @endunless>@error('domain'){{ $message }}@enderror @error('auth_code'){{ $message }}@enderror</p>
                <p class="sslx-hint" id="hostSwitchLink" hidden><a href="#" id="hostSwitchRegister">Register this domain instead →</a></p>
                @if(!empty($pendingDomain))
                    <p class="host-pending">Your {{ $pendingDomain['mode'] === 'transfer' ? 'transfer' : 'registration' }} of <strong>{{ $pendingDomain['domain'] }}</strong> is waiting for payment or completion. Finish it, then choose <em>I already have a registered domain</em> and press Check to continue.</p>
                @endif

                {{-- Important plan details, from the plan's own data. --}}
                @if(!empty($quote['highlights']))
                    <ul class="host-highlights" aria-label="Plan details">
                        @foreach($quote['highlights'] as $line)
                            <li>{{ $line }}</li>
                        @endforeach
                    </ul>
                @endif

                <script type="application/json" id="hostModes">{!! json_encode($modes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
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
