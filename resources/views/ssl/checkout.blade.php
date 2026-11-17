{{--
    Step 2 of buying SSL.

    Guest      → the BDT price is already visible; sign in / create an account.
                 Both links carry return_to, so they land back here afterwards
                 (the same mechanism domain checkout uses).
    Signed in  → who is it for (myself / an existing customer) and bKash / Nagad.

    Data: $quote, $domain, $user (null for guests), $methods (bkash, nagad).
--}}
@php($sslxCssVersion = @md5_file(public_path('css/ssl-checkout.css')))
@php($sslxJsVersion = @md5_file(public_path('js/ssl-checkout.js')))
@php($plan = $quote['plan'])
@php($term = $quote['term'])
@php($money = function ($amount) { return \App\Support\SslPricing::money($amount); })
@php($logos = ['bkash' => 'pay-bkash.svg', 'nagad' => 'pay-nagad.svg'])
@php($purchaseFor = old('purchase_for', 'myself'))
<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'SSL Certificate — Checkout'])
    <link rel="stylesheet" href="{{ asset('css/ssl-checkout.css') }}?v={{ $sslxCssVersion }}">
</head>
<body class="cs-page">
@include('partials.site-navbar', ['navActive' => null])

<main class="sslx">
    <div class="sslx-wrap">
        <nav class="sslx-crumbs" aria-label="Breadcrumb">
            <a href="{{ route('landing') }}">Home</a><span aria-hidden="true">›</span>
            <a href="{{ route('services.coming-soon', 'ssl-certificate') }}">SSL Certificates</a><span aria-hidden="true">›</span>
            <strong>Checkout</strong>
        </nav>

        <header class="sslx-head">
            <h1>Complete your SSL order</h1>
            <p>{{ $user ? 'Choose who this certificate is for and how you would like to pay.' : 'Sign in to place your order — your selection and price are saved.' }}</p>
        </header>

        <div class="sslx-grid">
            <div>
            @if($user)
                <form id="sslCheckoutForm" method="POST" action="{{ route('ssl.place-order') }}" data-lookup-url="{{ route('ssl.customer.lookup') }}" novalidate>
                    @csrf

                    {{-- A customer-lookup error is already shown under the customer field. --}}
                    @if($errors->any() && !$errors->has('customer_identifier'))
                        <div class="sslx-alert" role="alert">{{ $errors->first() }}</div>
                    @endif

                    <section class="sslx-card">
                        <h2>Who are you buying for?</h2>
                        <p class="sslx-sub">The certificate and invoice go to this account.</p>

                        <label class="sslx-choice">
                            <span class="sslx-choice-row">
                                <input type="radio" name="purchase_for" value="myself" {{ $purchaseFor !== 'customer' ? 'checked' : '' }}>
                                <span><strong>Myself</strong><small>{{ $user->email }}</small></span>
                            </span>
                        </label>

                        <label class="sslx-choice">
                            <span class="sslx-choice-row">
                                <input type="radio" name="purchase_for" value="customer" {{ $purchaseFor === 'customer' ? 'checked' : '' }}>
                                <span><strong>Customer</strong></span>
                            </span>
                            <span class="sslx-customer">
                                <span class="sslx-customer-row">
                                    <input id="sslCustomerId" name="customer_identifier" type="text" autocomplete="off" autocapitalize="none" spellcheck="false"
                                           placeholder="Email or ID of existing customer" value="{{ old('customer_identifier') }}"
                                           class="{{ $errors->has('customer_identifier') ? 'is-invalid' : '' }}" aria-label="Customer email or ID">
                                    <button class="sslx-btn" id="sslCustomerConfirm" type="button">Confirm</button>
                                </span>
                                <span class="sslx-customer-status {{ $errors->has('customer_identifier') ? 'is-bad' : '' }}" id="sslCustomerStatus" role="status" aria-live="polite">{{ $errors->first('customer_identifier') }}</span>
                            </span>
                        </label>
                    </section>

                    <section class="sslx-card">
                        <h2>Choose payment method</h2>
                        <p class="sslx-sub">You will pay in Taka.</p>
                        <div class="sslx-pay">
                            @foreach($methods as $key => $method)
                                <label>
                                    <input type="radio" name="payment_method" value="{{ $key }}" {{ old('payment_method', 'bkash') === $key ? 'checked' : '' }}>
                                    @if(isset($logos[$key]))<img src="{{ asset('image/payments/' . $logos[$key]) }}" alt="">@endif
                                    <span>{{ $method['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="sslx-note">Your order is saved first and starts after the payment is confirmed.</p>
                        <button class="sslx-btn is-wide sslx-submit" id="sslPlaceOrder" type="submit">Place order · ৳{{ $money($quote['total']) }} <span aria-hidden="true">→</span></button>
                    </section>
                </form>
            @else
                <section class="sslx-card">
                    <h2>Sign in to continue</h2>
                    <p class="sslx-sub">The certificate is issued under your account. It takes under a minute, and your {{ $plan['name'] }} selection stays here.</p>
                    <div class="sslx-auth">
                        <a href="{{ route('login', ['return_to' => \App\Support\ReturnTo::path()]) }}">
                            <span class="sslx-auth-icon" aria-hidden="true">👤</span>
                            <span><strong>Log in to continue</strong><small>Use your saved details and pick a payment method</small></span>
                            <b aria-hidden="true">→</b>
                        </a>
                        <a class="is-alt" href="{{ route('register', ['return_to' => \App\Support\ReturnTo::path()]) }}">
                            <span class="sslx-auth-icon" aria-hidden="true">✚</span>
                            <span><strong>Create new account</strong><small>You will come straight back to this order</small></span>
                            <b aria-hidden="true">→</b>
                        </a>
                    </div>
                </section>

                <section class="sslx-card">
                    <h2>How you'll pay</h2>
                    <p class="sslx-sub">Choose your method after signing in — nothing is charged until then.</p>
                    <div class="sslx-pay">
                        @foreach($methods as $key => $method)
                            <div class="sslx-pay-static">
                                @if(isset($logos[$key]))<img src="{{ asset('image/payments/' . $logos[$key]) }}" alt="">@endif
                                <span>{{ $method['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
            </div>

            <aside class="sslx-card sslx-summary" aria-label="Order summary">
                <span class="sslx-kicker">Order summary</span>
                <h2>{{ $plan['name'] }}</h2>
                <div class="sslx-line"><span>Domain</span><b>{{ $domain }} <a href="{{ route('ssl.connect', ['plan' => $plan['key'], 'term' => $quote['years']]) }}">Change</a></b></div>
                <div class="sslx-line"><span>Term</span><b>{{ $term['label'] }}</b></div>
                <div class="sslx-line"><span>Price per year</span><b>৳{{ $money($term['per_year']) }}</b></div>
                <div class="sslx-total"><span>Total due</span><strong>৳{{ $money($quote['total']) }}</strong></div>
                <p class="sslx-renew">Renews at ৳{{ $money($plan['renewal_year']) }}/year</p>
                <div class="sslx-secure"><span aria-hidden="true">🔒</span> Safe &amp; secure checkout</div>
            </aside>
        </div>
    </div>
</main>

@include('partials.site-footer')
<script src="{{ asset('js/ssl-checkout.js') }}?v={{ $sslxJsVersion }}" defer></script>
@include('partials.chat')
</body>
</html>
