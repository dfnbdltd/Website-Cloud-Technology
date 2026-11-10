{{--
    SSL certificate plans — four pricing cards in BDT.

    One partial, two surfaces (same pattern as partials/domain-transfer):
      * .ssl-page--public  under <main class="cs-main"> on /services/ssl-certificate
      * .ssl-page--client  inside the store layout on /client/coming-soon/ssl-certificate

    Data comes from App\Support\SslPricing (config/ssl_plans.php). Prices are
    already BDT, so the ৳ glyph is the only currency decoration needed.

    The term dropdown swaps the "/mo" price and the term total in place with a
    small inline script — no request. The values ship in data-attributes, so the
    no-JS view already reads correctly for the default term.
--}}
@php($sslPlans = \App\Support\SslPricing::plans())
@php($sslVariant = $sslVariant ?? 'public')
@php($sslDefaultTerm = (int) config('ssl_plans.default_term', 1))

<div class="ssl-page ssl-page--{{ $sslVariant }}">

    {{-- Banner: same anatomy as the hosting pages (breadcrumb → kicker → title)
         but deliberately plain — a flat tinted band with one hairline rule. The
         only ornament is the pair of slow concentric rings painted by CSS
         (.ssl-banner::before / ::after), which clip to the band. --}}
    <header class="ssl-banner">
        <div class="ssl-banner-inner">
            <nav class="ssl-crumbs" aria-label="Breadcrumb">
                <a href="{{ route('landing') }}">Home</a>
                <span aria-hidden="true">/</span>
                <strong>SSL Certificates</strong>
            </nav>

            <span class="ssl-kicker">Secure your website</span>
            <h1 class="ssl-title">SSL Certificates</h1>
        </div>
    </header>

    <div class="ssl-grid">
        @foreach($sslPlans as $sslPlan)
            @php($sslTermId = 'sslTerm-' . $sslPlan['key'])
            @php($sslDefault = $sslPlan['terms'][$sslDefaultTerm] ?? $sslPlan['default'])

            <article class="ssl-card{{ !empty($sslPlan['badge']) ? ' is-featured' : '' }}">
                @if(!empty($sslPlan['badge']))
                    <span class="ssl-badge">{{ $sslPlan['badge'] }}</span>
                @endif

                <h2 class="ssl-card-name">{{ $sslPlan['name'] }}</h2>
                <p class="ssl-card-tagline">{{ $sslPlan['tagline'] }}</p>

                {{-- The ৳ is a currency chip standing beside the figure, the way
                     the reference art sets it: its own tinted tile, vertically
                     centred on the digits rather than sitting on the baseline. --}}
                <p class="ssl-price">
                    <span class="ssl-taka" aria-hidden="true">৳</span><span class="ssl-amount" data-ssl-amount>{{ \App\Support\SslPricing::perMonth($sslDefault['per_month']) }}</span><span class="ssl-per">/mo</span>
                </p>

                <p class="ssl-renew">Renews at ৳{{ \App\Support\SslPricing::money($sslPlan['renewal']) }}/mo</p>

                <div class="ssl-term">
                    <label class="ssl-sr" for="{{ $sslTermId }}">{{ $sslPlan['name'] }} billing term</label>
                    <select id="{{ $sslTermId }}" data-ssl-term>
                        @foreach($sslPlan['terms'] as $sslTerm)
                            <option value="{{ $sslTerm['years'] }}"
                                    data-per-month="{{ \App\Support\SslPricing::perMonth($sslTerm['per_month']) }}"
                                    data-total="{{ \App\Support\SslPricing::money($sslTerm['total']) }}"
                                    data-savings="{{ \App\Support\SslPricing::money($sslTerm['savings']) }}"
                                    @if($sslTerm['years'] === $sslDefaultTerm) selected @endif>
                                {{ $sslTerm['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if(!empty($sslPlan['buy_route']))
                    <a class="ssl-buy" href="{{ route($sslPlan['buy_route']) }}" data-service="{{ $sslPlan['name'] }}">Buy Now</a>
                @else
                    <button class="ssl-buy coming-soon-trigger" type="button" data-service="{{ $sslPlan['name'] }}">Buy Now</button>
                @endif

                <p class="ssl-total" data-ssl-total>
                    ৳{{ \App\Support\SslPricing::money($sslDefault['total']) }}
                    <span>total for {{ strtolower($sslDefault['label']) }}</span>
                </p>

                <ul class="ssl-features">
                    @foreach($sslPlan['features'] as $sslFeature)
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4.5 12.5l4.5 4.5L19.5 6.5"/></svg>
                            <span>{{ $sslFeature }}</span>
                        </li>
                    @endforeach
                </ul>
            </article>
        @endforeach
    </div>

</div>

<script>
    (function () {
        var TAKA = '\u09F3';

        document.querySelectorAll('.ssl-page .ssl-card').forEach(function (card) {
            var select = card.querySelector('[data-ssl-term]');
            var amount = card.querySelector('[data-ssl-amount]');
            var total = card.querySelector('[data-ssl-total]');
            if (!select || !amount || !total) { return; }

            select.addEventListener('change', function () {
                var option = select.options[select.selectedIndex];
                if (!option) { return; }

                amount.textContent = option.getAttribute('data-per-month');

                var span = document.createElement('span');
                span.textContent = 'total for ' + option.textContent.trim().toLowerCase();

                total.textContent = TAKA + option.getAttribute('data-total') + ' ';
                total.appendChild(span);
            });
        });
    }());
</script>
