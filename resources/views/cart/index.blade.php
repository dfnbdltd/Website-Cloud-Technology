<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Your cart'])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
    @unless(session('customer'))<link rel="stylesheet" href="{{ asset('css/home.css') }}?v={{ md5_file(public_path('css/home.css')) }}">@endunless
</head>
<body class="{{ session('customer') ? 'domain-app' : 'site domain-registration-guest' }}">
    @if(session('customer'))
        @include('partials.sidebar', ['active' => 'cart', 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
    @else
        <div class="site-top"><div class="site-wrap site-top-inner"><div class="site-top-links"><a class="coming-soon-trigger" href="#" data-service="Blog">Blog</a><a class="coming-soon-trigger" href="#" data-service="Offers">Offers</a><span>☎ {{ \App\Models\Setting::get('support_phone') }}</span></div><div class="site-top-links"><a href="{{ route('cart.index') }}">🛒 Cart <b class="site-count">{{ count($cart) }}</b></a></div></div></div>
        <header class="site-header"><div class="site-wrap site-header-inner"><a class="site-logo" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'h'])</a><nav class="site-nav"><a href="{{ route('landing') }}">Home</a><div class="site-nav-dropdown"><a class="selected" href="{{ route('domains.index') }}">Domains</a><div class="site-submenu"><a href="{{ route('domains.index') }}">Domain Registration</a><a class="coming-soon-trigger" href="#" data-service="Domain Transfer">Domain Transfer</a></div></div><div class="site-nav-dropdown"><a href="{{ route('hosting.shared') }}">Web Hosting</a><div class="site-submenu"><a href="{{ route('hosting.shared') }}">Shared Hosting</a><a href="{{ route('hosting.vps') }}">VPS Hosting</a><a href="{{ route('hosting.dedicated') }}">Dedicated Hosting</a></div></div><a class="coming-soon-trigger" href="#" data-service="Email hosting">Email</a><a class="coming-soon-trigger" href="#" data-service="SMS Service">SMS Service</a><a class="coming-soon-trigger" href="#" data-service="Support">Support</a></nav><div class="site-account"><a class="site-btn-ghost" href="{{ route('login') }}">Log in</a><a class="site-btn" href="{{ route('register') }}">Sign up</a></div></div></header>
        @include('partials.coming-soon-modal')
    @endif
    <main class="domain-main">
        @if(session('customer'))
            @include('partials.topbar', ['title' => 'Cart', 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
        @endif
        <div class="domain-content standalone-content">
            <span class="domain-kicker">YOUR ORDER</span>
            <h1 class="workspace-title">Cart</h1>
            <p class="workspace-subtitle">Review your domains, then continue to secure checkout.</p>
            @if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif

            @if($cart)
                <div class="cart-page">
                    <section class="cart-lines">
                        @foreach($cart as $item)
                            <div class="cart-line">
                                <span class="cart-line-icon">◎</span>
                                <div class="cart-line-name">
                                    <strong>{{ $item['domain'] }}</strong>
                                    <small>
                                        Domain registration · {{ $item['years'] }} year{{ $item['years'] > 1 ? 's' : '' }}
                                        @if(isset($item['first_year_price']))
                                            <br>First year ৳ {{ number_format($item['first_year_price']) }} · renews ৳ {{ number_format($item['renewal_price']) }}/year
                                        @endif
                                    </small>
                                </div>
                                <b class="cart-line-price">৳ {{ number_format($item['price'] * $item['years']) }}</b>
                                <form method="POST" action="{{ route('cart.remove') }}">
                                    @csrf
                                    <input type="hidden" name="domain" value="{{ $item['domain'] }}">
                                    <button class="cart-line-remove" type="submit" title="Remove {{ $item['domain'] }}">Remove</button>
                                </form>
                            </div>
                        @endforeach
                        <a class="cart-more" href="{{ route('domains.index') }}">＋ Search for another domain</a>
                    </section>
                    <aside class="cart-summary-box">
                        <span class="domain-kicker">SUMMARY</span>
                        <div class="cart-summary-row"><span>{{ count($cart) }} domain{{ count($cart) > 1 ? 's' : '' }}</span><span>৳ {{ number_format($total) }}</span></div>
                        <div class="cart-summary-row total"><span>Total due</span><strong>৳ {{ number_format($total) }}</strong></div>
                        <a class="checkout-button" href="{{ route('domains.checkout') }}">Continue to checkout <b>→</b></a>
                        @unless(session('customer'))<p class="cart-guest-note">You can complete your purchase without an account, or <a href="{{ route('register') }}">create one</a>.</p>@endunless
                        <div class="cart-trust"><span>✓</span><p>Secure checkout<br><small>{{ session('customer') ? 'Pay with bKash or your wallet balance' : 'Pay securely without creating an account' }}</small></p></div>
                    </aside>
                </div>
            @else
                <div class="cart-empty-state"><span>🛒</span><strong>Your cart is empty</strong><p>Search for a domain and add it to your cart to get started.</p><a class="checkout-button" href="{{ route('domains.index') }}">Search domains <b>→</b></a></div>
            @endif
        </div>
    </main>
    @unless(session('customer'))
        <footer class="site-footer"><div class="site-wrap site-footer-inner"><a class="site-logo" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'f'])</a><span>© {{ date('Y') }} {{ \App\Models\Setting::get('footer_text') }}</span><span>{{ \App\Models\Setting::get('support_phone') }} · {{ \App\Models\Setting::get('support_email') }}</span></div></footer>
    @endunless
    @include('partials.chat')
</body>
</html>
