<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @include('partials.head', ['title' => 'Domain registration'])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
    @unless(session('customer'))<link rel="stylesheet" href="{{ asset('css/home.css') }}?v={{ md5_file(public_path('css/home.css')) }}">@endunless
    <style>
        .domain-result .result-price { min-width:190px; }
        .result-price strong { display:block; }
        .result-price .term-period { margin-left:4px; color:#8190a1; font-size:12px; font-weight:500; }
        .result-price small { display:block; }
        .result-term-column { min-width:140px; }
        .result-term-column label { display:block; margin-bottom:5px; color:#8190a1; font-size:10px; font-weight:700; }
        .domain-years { width:100%; padding:9px 8px; border:1px solid #dfe6ee; border-radius:6px; background:#fff; color:#344256; font-size:11px; }
        .domain-add-form { display:flex; align-items:center; gap:8px; }
        .domain-years:focus { outline:none; border-color:#3c7ff0; }
        @media(max-width:720px) {
            .domain-result .result-price { min-width:0; }
            .result-term-column { min-width:120px; }
            .domain-add-form { flex-wrap:wrap; }
        }

        /* Lucrative search-result rows: headline-sized name + big price */
        .domain-result { padding:20px 24px; border-radius:12px; border-color:#e3ebf5; background:linear-gradient(180deg,#ffffff 0%,#fbfdff 100%); transition:border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
        .domain-result:hover { border-color:#c8dcf7; box-shadow:0 14px 34px #2b5ea016; transform:translateY(-1px); }
        .result-status { width:38px; height:38px; font-size:18px; }
        .result-name strong { font-size:23px; font-weight:800; letter-spacing:-.4px; color:var(--navy); display:block; }
        .result-name small { font-size:13px; font-weight:600; }
        .result-status.is-available ~ .result-name small { color:#0fa371; }
        .result-status.is-taken ~ .result-name small { color:#b38080; }
        .result-price strong { font-size:15px; }
        .result-price strong .term-price { font-size:28px; font-weight:800; color:var(--blue); letter-spacing:-.6px; }
        .result-price .term-period { font-size:13px; font-weight:700; color:#74869b; }
        .result-price small { font-size:12.5px; color:#90a0b4; }
        .add-domain { padding:16px 24px; font-size:13px; border-radius:9px; box-shadow:0 10px 22px #3c7ff038; }
        .catalog-section .result-name strong { font-size:19px; }
        .catalog-section .result-price strong .term-price { font-size:22px; }
        @media(max-width:760px) {
            .result-name strong { font-size:19px; }
            .result-price strong .term-price { font-size:22px; }
            .result-status { width:32px; height:32px; font-size:15px; }
            .add-domain { padding:14px 20px; }
        }
    </style>
</head>
<body class="{{ session('customer') ? 'domain-app' : 'site domain-registration-guest' }}">
    @if(session('customer'))
        @include('partials.sidebar', ['active' => 'domains', 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
    @else
        <div class="site-top"><div class="site-wrap site-top-inner"><div class="site-top-links"><a class="coming-soon-trigger" href="#" data-service="Blog">Blog</a><a class="coming-soon-trigger" href="#" data-service="Offers">Offers</a><span>☎ {{ \App\Models\Setting::get('support_phone') }}</span></div><div class="site-top-links"><a href="{{ route('cart.index') }}">🛒 Cart <b class="site-count">{{ count($cart) }}</b></a></div></div></div>
        <header class="site-header"><div class="site-wrap site-header-inner"><a class="site-logo" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'h'])</a><nav class="site-nav"><a href="{{ route('landing') }}">Home</a><div class="site-nav-dropdown"><a class="selected" href="{{ route('domains.index') }}">Domains</a><div class="site-submenu"><a href="{{ route('domains.index') }}">Domain Registration</a><a class="coming-soon-trigger" href="#" data-service="Domain Transfer">Domain Transfer</a></div></div><div class="site-nav-dropdown"><a href="{{ route('hosting.shared') }}">Web Hosting</a><div class="site-submenu"><a href="{{ route('hosting.shared') }}">Shared Hosting</a><a href="{{ route('hosting.vps') }}">VPS Hosting</a><a href="{{ route('hosting.dedicated') }}">Dedicated Hosting</a></div></div><a class="coming-soon-trigger" href="#" data-service="Email hosting">Email</a><a class="coming-soon-trigger" href="#" data-service="SMS Service">SMS Service</a><a class="coming-soon-trigger" href="#" data-service="Support">Support</a></nav><div class="site-account"><a class="site-btn-ghost" href="{{ route('login') }}">Log in</a><a class="site-btn" href="{{ route('register') }}">Sign up</a></div></div></header>
        @include('partials.coming-soon-modal')
    @endif
    <main class="domain-main">
        @if(session('customer'))
            @include('partials.topbar', ['title' => 'Domain registration', 'avatar' => strtoupper(substr(session('customer.name', 'C'), 0, 2)), 'cartCount' => count($cart), 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
        @endif
        <section class="domain-content no-cart">
            @if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif
            @if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
            @if(session('cart_added'))<div class="domain-alert success cart-added"><span>✓ <b>{{ session('cart_added') }}</b> was added to your cart.</span><a href="{{ route('cart.index') }}">View cart ({{ count($cart) }}) →</a></div>@endif

            <section class="domain-page-intro" aria-labelledby="domain-page-title">
                <div class="domain-page-intro-copy">
                    <span class="domain-kicker">FIND YOUR PLACE ONLINE</span>
                    <h1 id="domain-page-title">Choose a domain<br><em>that feels like you.</em></h1>
                    <p>Search your perfect name, compare popular extensions, and add it to your cart in seconds.</p>
                </div>
                <div class="domain-page-intro-points" aria-label="Domain registration benefits">
                    <span><b>✓</b> Fast search</span>
                    <span><b>✓</b> Clear pricing</span>
                    <span><b>✓</b> Secure checkout</span>
                </div>
            </section>

            <form class="domain-search domain-search-primary" method="POST" action="{{ route('domains.search') }}">
                @csrf
                <label for="domain-search-input">Search for your domain</label>
                <div class="domain-search-row"><span aria-hidden="true">⌕</span><input id="domain-search-input" name="domain" value="{{ $query }}" placeholder="yourbrand.com" autocomplete="off" required><button type="submit">Search domain <b>→</b></button></div>
                <small>Try a name without spaces. Search a specific extension or compare all popular extensions.</small>
            </form>

            @if($results)
                <section class="domain-results">
                    <div class="results-heading"><div><span class="domain-kicker">SEARCH RESULTS</span><h2>Available domains for “{{ $query }}”</h2><p class="results-heading-note">Pick a name below, choose your term, and continue when you’re ready.</p></div><span class="result-source">Live availability where enabled</span></div>
                    @foreach($results as $result)
                        @php($selectedYears = isset($cart[$result['domain']]) ? (int) $cart[$result['domain']]['years'] : 1)
                        @php($selectedTermPrice = $result['year_prices'][$selectedYears] ?? $result['year_prices'][1] ?? $result['price'])
                        <div class="domain-result" data-first-year="{{ $result['price'] }}" data-renewal="{{ $result['renewal'] }}">
                            <div class="result-status {{ $result['available'] ? 'is-available' : 'is-taken' }}"><span>{{ $result['available'] ? '✓' : ($result['available'] === null ? '?' : '×') }}</span></div>
                            <div class="result-name"><strong>{{ $result['domain'] }}</strong><small>{{ $result['available'] ? 'Available to register' : ($result['available'] === null ? 'Could not check availability — please try again' : 'Already registered') }}</small></div>
                            <div class="result-price">
                                <strong>
                                    <span class="term-price">৳ {{ number_format($selectedTermPrice) }}</span>
                                    <span class="term-period">@if($selectedYears === 1)/first year @else /{{ $selectedYears }} years @endif</span>
                                </strong>
                                <small class="term-caption">
                                    @if($selectedYears === 1)
                                        Renews ৳ {{ number_format($result['renewal']) }}/year
                                    @else
                                        First year ৳ {{ number_format($result['price']) }} · renews ৳ {{ number_format($result['renewal']) }}/year
                                    @endif
                                </small>
                            </div>
                            @if(count($result['year_prices']) > 1)
                                <div class="result-term-column">
                                    <label for="years-{{ $loop->index }}">No. of years</label>
                                    @if($result['available'])
                                        <form class="domain-add-form" method="POST" action="{{ route('domains.cart') }}">
                                            @csrf
                                            <input type="hidden" name="domain" value="{{ $result['domain'] }}">
                                            <select id="years-{{ $loop->index }}" class="domain-years" name="years" aria-label="No. of years" onchange="updateTermPrice(this)">
                                            @foreach($result['year_prices'] as $years => $termPrice)
                                                <option value="{{ $years }}" data-price="{{ $termPrice }}" @if((int) $years === $selectedYears) selected @endif>{{ $years }} year{{ $years > 1 ? 's' : '' }} · ৳ {{ number_format($termPrice) }}</option>
                                            @endforeach
                                            </select>
                                            <button class="add-domain" type="submit">{{ isset($cart[$result['domain']]) ? 'Update cart' : (session('customer') ? 'Add to cart' : 'Purchase now') }} <b>+</b></button>
                                        </form>
                                    @else
                                        <select id="years-{{ $loop->index }}" class="domain-years" aria-label="No. of years" onchange="updateTermPrice(this)">
                                            @foreach($result['year_prices'] as $years => $termPrice)
                                                <option value="{{ $years }}" data-price="{{ $termPrice }}">{{ $years }} year{{ $years > 1 ? 's' : '' }} · ৳ {{ number_format($termPrice) }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                            @endif
                            @if(!$result['available'])
                                <span class="taken-label" @if($result['available'] === null) title="ResellerClub could not confirm availability. Pricing is still available." @endif>{{ $result['available'] === null ? 'Not verified' : 'Unavailable' }}</span>
                            @endif
                        </div>
                    @endforeach
                </section>
            @endif

            <section class="catalog-section">
                <div class="results-heading"><div><span class="domain-kicker">TRY THE FLOW</span><h2>Test checkout · ৳1 each</h2><p class="results-heading-note">Use a reserved test domain to preview the checkout experience.</p></div><span class="result-source">Test orders only — not real domain registrations</span></div>
                @foreach($testDomains as $domain => $plan)
                    <div class="domain-result">
                        <div class="result-status is-available"><span>✓</span></div>
                        <div class="result-name"><strong>{{ $domain }}</strong><small>Reserved test domain · not registered externally</small></div>
                        <div class="result-price"><strong>৳ {{ number_format($plan['price']) }}</strong><small>one-year test order</small></div>
                        @if(isset($cart[$domain]))
                            <a class="add-domain in-cart" href="{{ route('cart.index') }}">In cart ✓</a>
                        @else
                            <form method="POST" action="{{ route('domains.cart') }}">@csrf<input type="hidden" name="domain" value="{{ $domain }}"><input type="hidden" name="years" value="1"><button class="add-domain" type="submit">{{ session('customer') ? 'Add to cart' : 'Purchase now' }} <b>+</b></button></form>
                        @endif
                    </div>
                @endforeach
            </section>

            <section class="catalog-section">
                <div class="results-heading"><div><span class="domain-kicker">POPULAR EXTENSIONS</span><h2>All domain pricing</h2></div><span class="result-source">Prices in BDT / year</span></div>
                <div class="catalog-table-wrap">
                    <table class="catalog-table">
                        <caption class="sr-only">Domain extension pricing in Bangladeshi Taka</caption>
                        <thead>
                            <tr>
                                <th scope="col">Extension</th>
                                <th scope="col">First year</th>
                                <th scope="col">Renewal / year</th>
                                <th scope="col"><span class="sr-only">Popularity</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plans as $tld => $plan)
                                <tr>
                                    <th scope="row"><span class="catalog-extension">.{{ $tld }}</span></th>
                                    <td data-label="First year"><strong>৳ {{ number_format($plan['price']) }}</strong></td>
                                    <td data-label="Renewal / year"><span>৳ {{ number_format($plan['renewal']) }}</span></td>
                                    <td data-label="Popularity">@if(isset($plan['popular']))<span class="catalog-popular">Popular</span>@else<span class="catalog-standard">Standard</span>@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </section>
    </main>
    @unless(session('customer'))
        <footer class="site-footer"><div class="site-wrap site-footer-inner"><a class="site-logo" href="{{ route('landing') }}">@include('partials.logo', ['gid' => 'f'])</a><span>© {{ date('Y') }} {{ \App\Models\Setting::get('footer_text') }}</span><span>{{ \App\Models\Setting::get('support_phone') }} · {{ \App\Models\Setting::get('support_email') }}</span></div></footer>
    @endunless
    @include('partials.chat')
    <script>
        function updateTermPrice(select) {
            var row = select.closest('.domain-result');
            var selected = select.options[select.selectedIndex];
            var total = Number(selected.getAttribute('data-price')) || 0;
            var years = Number(selected.value) || 1;
            var formatPrice = function (price) {
                return price.toLocaleString('en-US', { maximumFractionDigits: 0 });
            };
            row.querySelector('.term-price').textContent = '৳ ' + formatPrice(total);
            row.querySelector('.term-period').textContent = years === 1 ? '/first year' : '/' + years + ' years';
            row.querySelector('.term-caption').textContent = years === 1
                ? 'Renews ৳ ' + formatPrice(Number(row.getAttribute('data-renewal'))) + '/year'
                : 'First year ৳ ' + formatPrice(Number(row.getAttribute('data-first-year'))) + ' · renews ৳ ' + formatPrice(Number(row.getAttribute('data-renewal'))) + '/year';
        }
    </script>
</body>
</html>
