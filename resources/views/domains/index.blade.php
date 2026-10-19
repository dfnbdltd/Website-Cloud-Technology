<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @include('partials.head', ['title' => 'Domain registration'])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
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
    </style>
</head>
<body class="domain-app">
    @include('partials.sidebar', ['active' => 'domains', 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
    <main class="domain-main">
        @include('partials.topbar', ['title' => 'Domain registration', 'avatar' => strtoupper(substr(session('customer.name', 'C'), 0, 2)), 'cartCount' => count($cart), 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
        <section class="domain-content no-cart">
            @if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif
            @if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
            @if(session('cart_added'))<div class="domain-alert success cart-added"><span>✓ <b>{{ session('cart_added') }}</b> was added to your cart.</span><a href="{{ route('cart.index') }}">View cart ({{ count($cart) }}) →</a></div>@endif

            <form class="domain-search" method="POST" action="{{ route('domains.search') }}">
                @csrf
                <label>Search for your domain</label>
                <div class="domain-search-row"><input name="domain" value="{{ $query }}" placeholder="yourbrand.com" required><button type="submit">Search domain <b>⌕</b></button></div>
                <small>Try a name without spaces. You can search a specific extension or compare all popular extensions.</small>
            </form>

            @if($results)
                <section class="domain-results">
                    <div class="results-heading"><div><span class="domain-kicker">SEARCH RESULTS</span><h2>Available domains for “{{ $query }}”</h2></div><span class="result-source">Live availability where enabled</span></div>
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
                                            <button class="add-domain" type="submit">{{ isset($cart[$result['domain']]) ? 'Update cart' : 'Add to cart' }} <b>+</b></button>
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
                <div class="results-heading"><div><span class="domain-kicker">TEST CHECKOUT</span><h2>Dummy domains · ৳1 each</h2></div><span class="result-source">Test orders only — not real domain registrations</span></div>
                @foreach($testDomains as $domain => $plan)
                    <div class="domain-result">
                        <div class="result-status is-available"><span>✓</span></div>
                        <div class="result-name"><strong>{{ $domain }}</strong><small>Reserved test domain · not registered externally</small></div>
                        <div class="result-price"><strong>৳ {{ number_format($plan['price']) }}</strong><small>one-year test order</small></div>
                        @if(isset($cart[$domain]))
                            <a class="add-domain in-cart" href="{{ route('cart.index') }}">In cart ✓</a>
                        @else
                            <form method="POST" action="{{ route('domains.cart') }}">@csrf<input type="hidden" name="domain" value="{{ $domain }}"><input type="hidden" name="years" value="1"><button class="add-domain" type="submit">Add to cart <b>+</b></button></form>
                        @endif
                    </div>
                @endforeach
            </section>

            <section class="catalog-section">
                <div class="results-heading"><div><span class="domain-kicker">POPULAR EXTENSIONS</span><h2>All domain pricing</h2></div><span class="result-source">Prices in BDT / year</span></div>
                <div class="catalog-grid">
                    @foreach($plans as $tld => $plan)
                        <div class="catalog-card"><span>.{{ $tld }}</span><strong>৳ {{ number_format($plan['price']) }}</strong><small>Renewal ৳ {{ number_format($plan['renewal']) }}</small>@if(isset($plan['popular']))<b>Popular</b>@endif</div>
                    @endforeach
                </div>
            </section>
        </section>
    </main>
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
