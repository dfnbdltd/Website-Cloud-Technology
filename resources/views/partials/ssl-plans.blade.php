{{--
    SSL certificate plans — four pricing cards in BDT.

    One partial, two surfaces (same pattern as partials/domain-transfer):
      * .ssl-page--public  under <main class="cs-main"> on /services/ssl-certificate
      * .ssl-page--client  inside the store layout on /client/coming-soon/ssl-certificate

    Data comes from App\Services\SslPlanService with config fallback. Prices are
    already BDT, so the ৳ glyph is the only currency decoration needed.

    YEARLY QUOTING
    --------------
    An SSL certificate is bought for a term by the year, so every figure on the
    card is annual:

      headline   = that term's total ÷ its years   (`per_year`)
      renewal    = the renew selling price × 12     (`renewal_year`)
      total line = what is actually charged         (`total`)

    The headline comes from the SELLING price, never from cost, and never from
    the monthly figure: `per_month` still exists in the service array (term
    maths and the admin editor use it) but nothing here prints it. Because a
    2-year term is offered at a lower monthly rate, its yearly headline is
    genuinely cheaper (৳1,300/year vs ৳1,368/year) while the one-off total is
    larger — which is the honest way to compare terms.

    The term dropdown swaps the "/year" price and the term total in place with a
    small inline script — no request. The values ship in data-attributes, so the
    no-JS view already reads correctly for the default (1-year) term.
--}}
@php($sslPlans = $sslPlans ?? app(\App\Services\SslPlanService::class)->plans())
@php($sslVariant = $sslVariant ?? 'public')

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
            {{-- `default` is resolved by SslPlanService: always the 1-year term. --}}
            @php($sslDefault = $sslPlan['default'])

            <article class="ssl-card">
                <h2 class="ssl-card-name">{{ $sslPlan['name'] }}</h2>
                <p class="ssl-card-tagline">{{ $sslPlan['tagline'] }}</p>

                {{-- The ৳ is a currency chip standing beside the figure, the way
                     the reference art sets it: its own tinted tile, vertically
                     centred on the digits rather than sitting on the baseline. --}}
                <p class="ssl-price">
                    <span class="ssl-taka" aria-hidden="true">৳</span><span class="ssl-amount" data-ssl-amount>{{ \App\Support\SslPricing::money($sslDefault['per_year']) }}</span><span class="ssl-per">/year</span>
                </p>

                <p class="ssl-renew">Renews at ৳{{ \App\Support\SslPricing::money($sslPlan['renewal_year']) }}/year</p>

                <div class="ssl-term">
                    <label class="ssl-sr" for="{{ $sslTermId }}">{{ $sslPlan['name'] }} billing term</label>
                    <select id="{{ $sslTermId }}" data-ssl-term>
                        @foreach($sslPlan['terms'] as $sslTerm)
                            <option value="{{ $sslTerm['years'] }}"
                                    data-per-year="{{ \App\Support\SslPricing::money($sslTerm['per_year']) }}"
                                    data-total="{{ \App\Support\SslPricing::money($sslTerm['total']) }}"
                                    data-savings="{{ \App\Support\SslPricing::money($sslTerm['savings']) }}"
                                    @if($sslTerm['years'] === $sslDefault['years']) selected @endif>
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

                {{-- SslPlanService already drops features the admin hid for this
                     plan, so every row reaching here is meant to be printed. A
                     hidden row is different from an "unavailable" one, which
                     still shows with a red cross. --}}
                <ul class="ssl-features">
                    @foreach($sslPlan['features'] as $sslFeature)
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $sslFeature['available'] ? 'M4.5 12.5l4.5 4.5L19.5 6.5' : 'M6 6l12 12M18 6L6 18' }}"/></svg>
                            <span class="{{ $sslFeature['available'] ? '' : 'ssl-feature-unavailable' }}">{{ $sslFeature['text'] }}</span>
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

            function apply(option) {
                if (!option || option.disabled) { return; }

                amount.textContent = option.getAttribute('data-per-year');

                var span = document.createElement('span');
                span.textContent = 'total for ' + option.textContent.trim().toLowerCase();

                total.textContent = TAKA + option.getAttribute('data-total') + ' ';
                total.appendChild(span);
            }

            // Belt-and-braces: if the selected option is a term the admin has
            // switched off, move the select to the first one that can be bought
            // before the values are ever shown.
            if (select.selectedOptions[0] && select.selectedOptions[0].disabled) {
                var firstUsable = [].slice.call(select.options).filter(function (o) { return !o.disabled; })[0];
                if (firstUsable) {
                    select.value = firstUsable.value;
                    apply(firstUsable);
                }
            }

            select.addEventListener('change', function () {
                apply(select.options[select.selectedIndex]);
            });
        });
    }());
</script>
