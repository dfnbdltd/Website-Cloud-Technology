{{--
    Step 2 of buying hosting (same design as the SSL and domain checkouts).

    Guest      → the BDT price is already visible; sign in / create an account.
                 Both links carry return_to, so they land back here afterwards.
    Signed in  → who is it for (myself / an existing customer) and bKash / Nagad.

    PAYMENT IS NOT CONNECTED YET: Place order is locked ("Payment coming soon").
    There is no order route; nothing is saved or charged.

    Data: $quote, $domain, $user (null for guests), $methods (bkash, nagad),
          $verifiedCustomer.
--}}
@php($sslxCssVersion = @md5_file(public_path('css/ssl-checkout.css')))
@php($sslxJsVersion = @md5_file(public_path('js/ssl-checkout.js')))
@php($money = function ($amount) { return \App\Services\HostingOrderService::money($amount); })
@php($logos = ['bkash' => 'pay-bkash.svg', 'nagad' => 'pay-nagad.svg'])
@php($purchaseFor = old('purchase_for', 'myself'))
<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Hosting — Checkout'])
    <link rel="stylesheet" href="{{ asset('css/ssl-checkout.css') }}?v={{ $sslxCssVersion }}">
</head>
<body class="cs-page">
@include('partials.site-navbar', ['navActive' => 'hosting'])

<main class="sslx">
    <div class="sslx-wrap">
        <nav class="sslx-crumbs" aria-label="Breadcrumb">
            <a href="{{ route('landing') }}">Home</a><span aria-hidden="true">›</span>
            <a href="{{ $quote['plans_url'] }}">{{ $quote['product'] }}</a><span aria-hidden="true">›</span>
            <strong>Checkout</strong>
        </nav>

        <header class="sslx-head">
            <h1>Complete your hosting order</h1>
            <p>{{ $user ? 'Choose who this hosting plan is for and how you would like to pay.' : 'Sign in to place your order — your selection and price are saved.' }}</p>
        </header>

        <div class="sslx-grid">
            <div>
            @if($user)
                <form id="sslCheckoutForm" action="#" method="GET" onsubmit="return false;" data-lookup-url="{{ route('hosting.customer.lookup') }}" data-live="0" data-service="Hosting ordering" data-confirmed="{{ $verifiedCustomer ? old('customer_identifier') : '' }}" novalidate>

                    {{-- A customer-lookup error is already shown under the customer field. --}}
                    @if($errors->any() && !$errors->has('customer_identifier'))
                        <div class="sslx-alert" role="alert">{{ $errors->first() }}</div>
                    @endif

                    <section class="sslx-card">
                        <h2>Who are you buying for?</h2>
                        <p class="sslx-sub">The hosting account and invoice go to this account.</p>

                        <label class="sslx-choice">
                            <span class="sslx-choice-row">
                                <input type="radio" name="purchase_for" value="myself" {{ $purchaseFor !== 'customer' ? 'checked' : '' }}>
                                <span><strong>Myself</strong><small>{{ $user->email }}</small></span>
                            </span>
                        </label>

                        {{-- Your own details, right under "Myself" and before "Customer". --}}
                        <div class="sslx-myself" id="sslMyself">
                            <strong>{{ $user->name }}</strong>
                            <span>{{ $user->email }}</span>
                            @if($user->phone)<span>{{ $user->phone }}</span>@endif
                            <small>Using your account details</small>
                        </div>

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
                                <span class="sslx-customer-details" id="sslCustomerDetails" @if(!$verifiedCustomer) hidden @endif>
                                    <span class="sslx-customer-details-title">Customer details</span>
                                    <span class="sslx-customer-detail"><span class="sslx-customer-detail-label">Name</span><span class="sslx-customer-detail-value" id="sslCustomerName">{{ $verifiedCustomer->name ?? '' }}</span></span>
                                    <span class="sslx-customer-detail"><span class="sslx-customer-detail-label">Email</span><span class="sslx-customer-detail-value" id="sslCustomerEmail">{{ $verifiedCustomer->email ?? '' }}</span></span>
                                    <span class="sslx-customer-detail"><span class="sslx-customer-detail-label">Phone</span><span class="sslx-customer-detail-value" id="sslCustomerPhone">{{ $verifiedCustomer && $verifiedCustomer->phone ? $verifiedCustomer->phone : 'Not provided' }}</span></span>
                                </span>
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
                        <p class="sslx-note">Online payment is being connected. You will be able to place this order very soon.</p>
                        <button class="sslx-btn is-wide sslx-submit is-locked" id="sslPlaceOrder" type="button" data-locked="1" disabled aria-disabled="true">Payment coming soon</button>
                    </section>
                </form>
            @else
                <section class="sslx-card">
                    <h2>Sign in to continue</h2>
                    <p class="sslx-sub">The hosting plan is set up under your account. It takes under a minute, and your {{ $quote['name'] }} selection stays here.</p>
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
                <h2>{{ $quote['product'] }}</h2>
                <div class="sslx-line"><span>Plan name</span><b>{{ $quote['name'] }}</b></div>
                <div class="sslx-line"><span>Domain name</span><b>{{ $domain }} <a href="{{ route('hosting.connect', ['region' => $quote['region'], 'type' => $quote['type'], 'plan' => $quote['plan_id'], 'period' => $quote['period']]) }}">Change</a></b></div>
                @if($quote['region'] !== 'wordpress')
                    <div class="sslx-line"><span>Server location</span><b>{{ $quote['region_label'] }}</b></div>
                @endif
                <div class="sslx-line"><span>Billing</span><b>{{ $quote['period_label'] }}</b></div>
                <div class="sslx-line"><span>Price per {{ $quote['unit'] }}</span><b>৳{{ $money($quote['price']) }}</b></div>
                <div class="sslx-total"><span>Total due</span><strong>৳{{ $money($quote['total']) }}</strong></div>
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
