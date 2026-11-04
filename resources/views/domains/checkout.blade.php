<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Domain checkout'])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}?v={{ md5_file(public_path('css/home.css')) }}">
    <style>
        /* ---- Entrance animations ---- */
        @keyframes co-fade-up{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
        .co-anim{animation:co-fade-up .55s cubic-bezier(.22,.61,.36,1) both}
        .co-anim.d1{animation-delay:.09s}
        .co-anim.d2{animation-delay:.18s}
        .co-anim.d3{animation-delay:.27s}
        @keyframes co-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-4px)}}
        @keyframes co-shine{to{background-position:-200% center}}

        .checkout-page { padding:34px 20px 64px; max-width:1180px; margin:0 auto; }
        .checkout-head h1 { font-size:36px; letter-spacing:-1.2px; }
        .checkout-head p { font-size:13px; }
        .checkout-head > a { color:var(--muted); font-size:13px; font-weight:700; }
        .checkout-head > a:hover { color:var(--blue); }
        .checkout-breadcrumb { display:flex; gap:12px; align-items:center; margin-bottom:22px; color:#9aabc0; font-size:12px; font-weight:700; }
        .checkout-breadcrumb a { color:#4779b5; text-decoration:none; }
        .checkout-breadcrumb a:hover { color:#2563b8; }
        .checkout-breadcrumb span { padding-left:12px; border-left:1px solid #d9e5f1; }
        .checkout-trust { display:flex; align-items:center; gap:10px; padding:11px 14px; border:1px solid #d7eee4; border-radius:12px; background:#f4fcf8; color:#217b59; }
        .checkout-trust > span { display:grid; place-items:center; width:24px; height:24px; border-radius:50%; background:#d8f4e8; font-weight:800; }
        .checkout-trust strong,.checkout-trust small { display:block; }
        .checkout-trust strong { font-size:12px; }
        .checkout-trust small { margin-top:2px; color:#6f9b88; font-size:10px; }
        .checkout-progress { display:flex; align-items:center; max-width:650px; margin:0 0 25px; color:#9aaabd; font-size:11px; font-weight:800; }
        .checkout-progress span { display:flex; align-items:center; gap:7px; white-space:nowrap; }
        .checkout-progress span:not(.is-current):not(.is-complete) { opacity:.7; }
        .checkout-progress span:first-child { color:#23815d; }
        .checkout-progress span.is-current { color:#2469c4; }
        .checkout-progress em { display:grid; place-items:center; width:24px; height:24px; border-radius:50%; background:#edf2f7; color:#7f91a5; font-style:normal; font-size:11px; }
        .checkout-progress .is-complete em { background:#d9f4e8; color:#21815d; }
        .checkout-progress .is-current em { background:#e1eeff; color:#2469c4; box-shadow:0 0 0 4px #f1f7ff; }
        .checkout-progress span b { font-size:11px; }
        .checkout-progress i { flex:1; height:1px; margin:0 12px; background:#dbe6f1; }

        /* ---- Shared card polish ---- */
        .checkout-grid { align-items:start; }
        .checkout-form, .checkout-auth-panel, .checkout-summary { border-radius:14px; border-color:#e2eaf5; box-shadow:0 18px 44px #102c4f0c; }
        .checkout-summary { position:relative; overflow:hidden; padding-top:32px; }
        .checkout-summary:before { content:''; position:absolute; top:0; left:0; right:0; height:4px; background:linear-gradient(90deg,var(--blue),var(--green)); }
        .checkout-summary .summary-item { padding:12px 0; border-bottom:1px dashed #e8eef6; }
        .checkout-summary .summary-item small { color:#90a0b4; }
        .checkout-summary .checkout-grand-total strong {
            font-size:21px;
            background:linear-gradient(90deg,#11244b,#3c7ff0,#26b883,#11244b);
            background-size:300% auto; -webkit-background-clip:text; background-clip:text; color:transparent;
            animation:co-shine 6s linear infinite;
        }

        /* ---- Guest auth panel ---- */
        .checkout-auth-panel { background:#fff; border:1px solid #e2eaf5; padding:36px 36px 30px; max-width:620px; }
        .checkout-auth-panel h2 { font-size:22px; margin-bottom:8px; }
        .checkout-auth-lead { color:var(--muted); font-size:14px; line-height:1.65; margin:0 0 22px; }
        .checkout-auth-card { display:flex; align-items:center; gap:16px; padding:18px 20px; border:1px solid #dde7f2; border-radius:11px; background:#fff; cursor:pointer; text-decoration:none; transition:border-color .18s ease, box-shadow .18s ease, background .18s ease, transform .18s ease; }
        .checkout-auth-card:hover { border-color:#bcd4f5; background:#f7faff; box-shadow:0 12px 30px #3c7ff01e; transform:translateY(-2px); }
        .checkout-auth-icon { display:grid; place-items:center; width:46px; height:46px; border-radius:11px; background:#e8f2ff; color:var(--blue); font-size:19px; flex:none; animation:co-float 3.8s ease-in-out infinite; }
        .checkout-auth-card.ghost .checkout-auth-icon { background:#e7f7f1; color:var(--green); animation-delay:.6s; }
        .checkout-auth-card strong { display:block; font:800 16px 'Manrope',sans-serif; color:var(--navy); }
        .checkout-auth-card small { display:block; margin-top:3px; color:var(--muted); font-size:12.5px; }
        .checkout-auth-card b { margin-left:auto; color:var(--blue); font-size:18px; transition:transform .18s ease; }
        .checkout-auth-card:hover b { transform:translateX(4px); }
        .checkout-auth-or { display:flex; align-items:center; gap:12px; margin:15px 0; color:#93a3b4; font-size:10.5px; font-weight:800; letter-spacing:1.2px; text-transform:uppercase; }
        .checkout-auth-or:before, .checkout-auth-or:after { content:''; height:1px; flex:1; background:var(--line); }

        /* ---- Payment methods (original brands) ---- */
        .payment-options { margin-top:19px; }
        .payment-option { transition:border-color .18s ease, box-shadow .18s ease, transform .18s ease; }
        label.payment-option:hover, .payment-option.static:hover { border-color:#c5d8f5; box-shadow:0 12px 26px #3c7ff014; transform:translateY(-2px); }
        .payment-option.static { cursor:default; }
        .payment-option .pay-tile { display:flex; align-items:center; width:auto; min-width:0; height:36px; padding:0; background:transparent; border:0; box-shadow:none; flex:none; font:800 21px 'Manrope',sans-serif; }
        .payment-option .pay-tile img { display:block; width:auto; height:100%; object-fit:contain; }
        .payment-option .pay-tile img { filter:drop-shadow(0 5px 8px rgba(32,70,110,.12)); }
        .pay-tile.is-bank img { height:64%; }
        .pay-tile.is-wallet { color:#1fa06d; }
        .payment-option.is-disabled { opacity:.5; }
        label.payment-option.is-disabled { cursor:not-allowed; }
        label.payment-option.is-disabled:hover { border-color:var(--line); background:#fff; box-shadow:none; transform:none; }
        .payment-methods-note { display:block; margin-top:13px; }

        @media(max-width:760px) {
            .checkout-auth-panel { padding:26px 20px; }
            .checkout-head h1 { font-size:28px; }
        }
        @media (prefers-reduced-motion:reduce) {
            .co-anim, .checkout-auth-icon { animation:none; }
            .checkout-summary .checkout-grand-total strong { animation:none; color:var(--navy); }
        }

        /* Premium checkout surface */
        body.site:has(.checkout-page) { background:#f4f8fd; }
        body.site:has(.checkout-page):before,
        body.site:has(.checkout-page):after {
            content:''; position:fixed; z-index:-1; border-radius:50%; pointer-events:none;
            filter:blur(2px); opacity:.58; animation:co-drift 16s ease-in-out infinite alternate;
        }
        body.site:has(.checkout-page):before { width:390px; height:390px; top:120px; left:-170px; background:#dcecff; }
        body.site:has(.checkout-page):after { width:330px; height:330px; right:-130px; bottom:20px; background:#d9f6ec; animation-delay:-4s; }
        @keyframes co-drift { from { transform:translate3d(-12px,10px,0) scale(.96); } to { transform:translate3d(18px,-12px,0) scale(1.05); } }
        .checkout-site-header .site-logo { animation:co-logo-in .7s cubic-bezier(.22,.61,.36,1) both; }
        @keyframes co-logo-in { from { opacity:0; transform:translateY(-8px) scale(.94); } to { opacity:1; transform:none; } }
        .checkout-page { position:relative; max-width:1240px; padding-top:48px; }
        .checkout-page:before { content:''; position:absolute; inset:0 12% auto; height:1px; background:linear-gradient(90deg,transparent,#cfe0f4,transparent); }
        .checkout-head { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:30px; }
        .checkout-head h1 { font-size:42px; line-height:1.08; letter-spacing:-1.8px; max-width:650px; }
        .checkout-head p { font-size:15px; color:#6f8198; margin-top:10px; }
        .checkout-grid { gap:28px; }
        .checkout-form, .checkout-auth-panel, .checkout-summary {
            background:rgba(255,255,255,.92); border:1px solid rgba(211,225,241,.95);
            box-shadow:0 24px 70px rgba(39,77,120,.12); backdrop-filter:blur(12px);
        }
        .checkout-form { padding:34px; }
        .checkout-form h2 { font-size:18px; letter-spacing:-.3px; margin-bottom:16px; }
        .checkout-contact-section { padding-bottom:25px; margin-bottom:27px; border-bottom:1px solid #edf2f8; }
        .checkout-account-summary { border:1px solid #d6ebdf; background:linear-gradient(135deg,#f3fcf8,#fff); border-radius:13px; padding:17px 18px; }
        .checkout-account-summary strong { font-size:17px; }
        .checkout-account-summary small { color:#26946b; font-weight:700; }
        .payment-options { gap:12px; }
        .payment-option { min-height:70px; padding:14px 16px; border-radius:13px; border-color:#e0e9f4; }
        .payment-option .pay-tile { width:92px; justify-content:center; padding:8px 12px; border-radius:10px; background:#f7fbff; }
        .payment-option .pay-tile img { max-width:76px; }
        .payment-option:has(input:checked) { border-color:#4389ee; background:#f5f9ff; box-shadow:0 10px 24px rgba(67,137,238,.13); }
        .payment-option input { accent-color:#347bea; }
        .checkout-submit { min-height:52px; border-radius:12px; box-shadow:0 12px 24px rgba(40,112,219,.2); transition:transform .2s ease, box-shadow .2s ease; }
        .checkout-submit:hover { transform:translateY(-2px); box-shadow:0 16px 30px rgba(40,112,219,.28); }
        .checkout-summary { padding:31px; position:sticky; top:24px; }
        .checkout-summary h2 { font-size:25px; letter-spacing:-.7px; }
        .checkout-summary .summary-item { padding:17px 0; }
        .checkout-summary .summary-item strong { font-size:15px; }
        .checkout-summary .summary-total { margin-top:12px; padding-top:22px; border-top:1px solid #e6edf6; }
        @media(max-width:760px) {
            .checkout-page { padding:28px 16px 48px; }
            .checkout-head h1 { font-size:32px; }
            .checkout-form { padding:24px 20px; }
            .checkout-summary { position:static; padding:24px 20px; }
            .checkout-head { display:block; }
            .checkout-trust { width:max-content; margin-top:18px; }
            .checkout-progress { margin-bottom:20px; }
            .checkout-progress span b { display:none; }
        }
        @media(prefers-reduced-motion:reduce) {
            body.site:has(.checkout-page):before, body.site:has(.checkout-page):after { animation:none; }
            .checkout-submit, .checkout-site-header .site-logo { animation:none; transition:none; }
        }
    </style>
</head>
<body class="site">
@php($cartCount = count(session('domain_cart', [])))
@include('partials.site-navbar', ['navActive' => 'domains'])

<main class="checkout-page">
    <div class="checkout-head co-anim">
        <div>
            <div class="checkout-breadcrumb"><a href="{{ route('cart.index') }}">← Back to cart</a><span>Checkout</span></div>
            <span class="domain-kicker">SECURE CHECKOUT</span>
            <h1>Complete your domain order.</h1>
            <p>Your order is processed after payment is confirmed.</p>
        </div>
        <div class="checkout-trust"><span>✓</span><div><strong>Safe &amp; secure</strong><small>Protected checkout</small></div></div>
    </div>
    <div class="checkout-progress co-anim" aria-label="Checkout progress"><span class="is-complete"><em>1</em><b>Cart</b></span><i></i><span class="is-current"><em>2</em><b>Payment</b></span><i></i><span><em>3</em><b>Confirmation</b></span></div>
    <div class="checkout-grid">
        @if(session('customer'))
            <form class="checkout-form checkout-card co-anim d1" method="POST" action="{{ route('domains.place-order') }}">
                @csrf
                <input type="hidden" name="purchase_for" value="myself">
                @if(session('cart_added'))<div class="domain-alert success cart-added"><span>✓ <b>{{ session('cart_added') }}</b> was added to your cart.</span></div>@endif
                @if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif
                <section
                    class="checkout-contact-section"
                    data-account-details-complete="{{ $accountDetailsComplete ? 'true' : 'false' }}"
                >
                    @if($accountDetailsComplete)
                        <div class="checkout-account-summary" id="checkoutAccountSummary">
                            <strong>{{ $accountDetails['name'] }}</strong>
                            <span>{{ $accountDetails['email'] }}</span>
                            @if($accountDetails['phone'])<span>{{ $accountDetails['phone'] }}</span>@endif
                            <small>Using your account details</small>
                        </div>
                    @else
                        <h2>Contact details</h2>
                        <div class="checkout-fields" id="checkoutContactFields">
                            <label>Full name<input name="full_name" value="{{ old('full_name', $accountDetails['name']) }}" required></label>
                            <label>Email address<input name="email" type="email" value="{{ old('email', $accountDetails['email']) }}" autocomplete="email" required></label>
                            <label>Phone number<input name="phone" type="tel" value="{{ old('phone', $accountDetails['phone']) }}" placeholder="01XXXXXXXXX" required></label>
                        </div>
                    @endif
                </section>
                <h2>Choose payment method</h2>
                <div class="payment-options">
                    @php($walletEmpty = (float) session('customer.balance', 0) <= 0)
                    @foreach($methods as $key => $method)
                        @php($walletDisabled = $key === 'wallet' && $walletEmpty)
                        <label class="payment-option {{ $walletDisabled ? 'is-disabled' : '' }}">
                            <input type="radio" name="payment_method" value="{{ $key }}" {{ $key === $defaultPaymentMethod ? 'checked' : '' }} {{ $walletDisabled ? 'disabled' : '' }}>
                            <span class="pay-tile {{ 'is-' . $key }}">@if($key === 'bkash')<img src="{{ asset('image/payments/pay-bkash.svg') }}" alt="bKash">@elseif($key === 'nagad')<img src="{{ asset('image/payments/pay-nagad.svg') }}" alt="Nagad">@elseif($key === 'bank')<img src="{{ asset('image/payments/pay-bank.svg') }}" alt="Bank transfer">@elseif($key === 'wallet')৳@endif</span>
                            <span><strong>{{ $method['label'] }}</strong></span>
                            <b>›</b>
                        </label>
                    @endforeach
                </div>
                <button class="checkout-submit" type="submit">Place order · ৳ {{ number_format($total) }} <b>→</b></button>
                @if(isset($gatewayReady) && !$gatewayReady)
                    <small class="checkout-note payment-methods-note" style="color:#c9822f">bKash gateway is not configured on this server yet — bKash orders will be saved and processed manually by our billing team.</small>
                @else
                    <small class="checkout-note payment-methods-note">bKash orders use the configured live gateway and will charge a real payment.</small>
                @endif
            </form>
        @else
            <div class="co-anim d1">
                <section class="checkout-form checkout-auth-panel">
                    <h2>Confirm with your account</h2>
                    <p class="checkout-auth-lead">Log in so your domain is registered in your own name — or create an account in under a minute. Your order stays safe in your cart.</p>
                    <a class="checkout-auth-card" href="{{ route('login', ['return_to' => request()->getRequestUri()]) }}">
                        <span class="checkout-auth-icon">👤</span>
                        <span><strong>Log in to confirm</strong><small>Use your saved details and continue to payment</small></span>
                        <b>→</b>
                    </a>
                    <div class="checkout-auth-or">Or</div>
                    <a class="checkout-auth-card ghost" href="{{ route('register', ['return_to' => request()->getRequestUri()]) }}">
                        <span class="checkout-auth-icon">✚</span>
                        <span><strong>Create new account</strong><small>The domain will be assigned to your new account</small></span>
                        <b>→</b>
                    </a>
                </section>
                <section class="checkout-form checkout-auth-panel pay-methods-preview">
                    <h2>How you'll pay</h2>
                    <div class="payment-options">
                        @foreach($methods as $key => $method)
                            <div class="payment-option static">
                                <span class="pay-tile {{ 'is-' . $key }}">@if($key === 'bkash')<img src="{{ asset('image/payments/pay-bkash.svg') }}" alt="bKash">@elseif($key === 'nagad')<img src="{{ asset('image/payments/pay-nagad.svg') }}" alt="Nagad">@elseif($key === 'bank')<img src="{{ asset('image/payments/pay-bank.svg') }}" alt="Bank transfer">@elseif($key === 'wallet')৳@endif</span>
                                <span><strong>{{ $method['label'] }}</strong></span>
                                <b>›</b>
                            </div>
                        @endforeach
                    </div>
                    <small class="checkout-note payment-methods-note">Select your payment method after confirming your account — nothing is charged until you do.</small>
                </section>
            </div>
        @endif
        <aside class="checkout-summary co-anim d2">
            <div class="checkout-summary-heading"><span class="domain-kicker">ORDER SUMMARY</span></div>
            <h2>{{ count($cart) }} domain{{ count($cart) > 1 ? 's' : '' }}</h2>
            @foreach($cart as $item)
                <div class="summary-item">
                    <span>
                        {{ $item['domain'] }}
                        <small>
                            @if(($item['order_type'] ?? 'registration') === 'transfer')
                                Transfer + one-year extension
                            @else
                                × {{ $item['years'] }} year{{ $item['years'] > 1 ? 's' : '' }}
                                <br>renews ৳ {{ number_format($item['renewal_price'] ?? $item['price']) }}/year
                            @endif
                        </small>
                    </span>
                    <b>৳ {{ number_format($item['price'] * $item['years']) }}</b>
                </div>
            @endforeach
            <div class="summary-total"><span>Subtotal</span><strong>৳ {{ number_format($total) }}</strong></div>
            <div class="checkout-summary-tax"><span>Tax (incl. VAT)</span><span>৳ 0</span></div>
            <div class="summary-total checkout-grand-total"><span>Total due</span><strong>৳ {{ number_format($total) }}</strong></div>
        </aside>
    </div>
</main>

<footer class="site-footer"><div class="site-wrap site-footer-inner"><a class="site-logo" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'f'])</a><span>© {{ date('Y') }} {{ \App\Models\Setting::get('footer_text') }}</span><span>{{ \App\Models\Setting::get('support_phone') }} · {{ \App\Models\Setting::get('support_email') }}</span></div></footer>
@include('partials.chat')
<script>
document.addEventListener('click', function (e) {
    var m = document.getElementById('accountMenu');
    if (m && !m.contains(e.target)) { m.classList.remove('open'); }
});
</script>
</body>
</html>
