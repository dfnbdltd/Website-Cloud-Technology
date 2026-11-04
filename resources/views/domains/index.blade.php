<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @include('partials.head', ['title' => 'Domain registration'])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ @md5_file(public_path('css/domains.css')) }}">
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
        @include('partials.site-navbar', ['navActive' => 'domains'])
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
                    <h1 id="domain-page-title">Choose a domain <em>that feels like you.</em></h1>
                </div>
                <form class="domain-search domain-search-primary domain-search-banner" method="POST" action="{{ route('domains.search') }}" data-spinner="search">
                    @csrf
                    <label for="domain-search-input">Search your perfect domain</label>
                    <div class="domain-search-row"><span class="domain-search-icon" aria-hidden="true"></span><input id="domain-search-input" name="domain" value="{{ $query }}" placeholder="yourbrand.com" autocomplete="off" required><button type="submit">Search domain <span class="search-button-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M10 2a8 8 0 105.29 14.29l4.71 4.7 1.41-1.41-4.7-4.71A8 8 0 0010 2zm0 2a6 6 0 110 12 6 6 0 010-12z"/></svg></span></button></div>
                    <small>Try a name without spaces. Search a specific extension or compare all popular extensions.</small>
                </form>
            </section>

            @if($results)
                <section class="domain-results">
                    <div class="results-heading"><div><span class="domain-kicker">SEARCH RESULTS</span><h2>Available domains for “{{ $query }}”</h2><p class="results-heading-note">Pick a name below, choose your term, and continue when you’re ready.</p></div><span class="result-source">Live availability where enabled</span></div>
                    @foreach($results as $result)
                        @php($selectedYears = isset($cart[$result['domain']]) ? (int) $cart[$result['domain']]['years'] : 1)
                        @php($selectedTermPrice = $result['year_prices'][$selectedYears] ?? $result['year_prices'][1] ?? $result['price'])
                        <div class="domain-result{{ $result['available'] === false ? ' is-registered' : '' }}" data-first-year="{{ $result['price'] }}" data-renewal="{{ $result['renewal'] }}">
                            <div class="result-status {{ $result['available'] ? 'is-available' : 'is-taken' }}"><span>{{ $result['available'] ? '✓' : ($result['available'] === null ? '?' : '×') }}</span></div>
                            <div class="result-name"><strong>{{ $result['domain'] }}</strong><small>{{ $result['available'] ? 'Available to register' : ($result['available'] === null ? 'Could not check availability — please try again' : 'Already registered') }}</small></div>
                            {{-- A registered domain (available === false) shows no price at all.
                                 available === null means the lookup failed, not that the name is
                                 taken, so pricing and the term selector stay visible there. --}}
                            @if($result['available'] !== false)
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
                                </div>
                            @endif
                            @endif
                            @if(!$result['available'])
                                <span class="taken-label{{ $result['available'] === null ? ' is-unverified' : '' }}" @if($result['available'] === null) title="Pricing is still available." @endif>{{ $result['available'] === null ? 'Not verified' : 'Unavailable' }}</span>
                            @endif
                        </div>
                    @endforeach
                </section>
            @endif

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
            // Unavailable rows render no price block at all, so bail out safely.
            if (!row || !selected || !row.querySelector('.term-price')) {
                return;
            }
            var total = Number(selected.getAttribute('data-price')) || 0;
            var years = Number(select.value) || 1;
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
