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
@php($sslHasDiscounts = \App\Support\SslPricing::hasTermDiscounts($sslPlans))
@php($sslDefaultTerm = (int) config('ssl_plans.default_term', 1))

<div class="ssl-page ssl-page--{{ $sslVariant }}">

    <header class="ssl-head">
        <span class="ssl-kicker">Secure your website</span>
        <h1 class="ssl-title">SSL Certificates</h1>
        <p class="ssl-subtitle">Security, trust and credibility for your website — priced in taka, no hidden conversion.</p>

        @if($sslHasDiscounts)
            <p class="ssl-note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.9-5.2-2.8-5.2 2.8 1-5.9L3.5 9.2l5.9-.9z"/></svg>
                Longer terms save up to 10%
            </p>
        @endif
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

    <p class="ssl-foot">
        All prices are in Bangladeshi Taka (BDT) and include VAT. Need help choosing?
        <a href="{{ route('support') }}">Talk to our team</a>.
    </p>
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
