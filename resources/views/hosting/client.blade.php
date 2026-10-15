<!doctype html><html lang="en"><head>@include('partials.head', ['title' => $title . ' plans'])<link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}"><link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}">
<style>
.hosting-plan-list{display:grid;gap:12px;margin-top:18px}
.hosting-plan-row{display:flex;align-items:center;gap:16px;flex-wrap:wrap;background:#fff;border:1px solid var(--line);border-radius:12px;padding:18px 20px;box-shadow:0 8px 25px #102c4f08}
.hosting-plan-row.is-featured{border-color:#2563eb;box-shadow:0 12px 30px #2563eb22}
.hosting-plan-icon{display:grid;place-items:center;width:46px;height:46px;flex:none;border-radius:12px;background:#eff6ff;color:#2563eb;font-size:22px}
.hosting-plan-main{flex:1 1 220px;min-width:0}
.hosting-plan-main strong{display:block;font:800 17px 'Manrope';color:#0f172a}
.hosting-plan-badge{display:inline-block;margin-left:8px;padding:2px 10px;border-radius:999px;background:#2563eb;color:#fff;font-size:10px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;vertical-align:middle}
.hosting-plan-main small{display:block;color:#64748b;font-size:13px;margin-top:2px}
.hosting-plan-specs{display:block;color:#475569;font-size:12.5px;margin-top:6px}
.hosting-plan-buy{margin-left:auto;text-align:right}
.hosting-plan-price{display:block;font:800 26px 'Manrope';color:#0f172a;letter-spacing:-.5px}
.hosting-plan-price small{font-size:12px;font-weight:500;color:#64748b;letter-spacing:0}
.hosting-plan-buy.is-coming-soon .hosting-plan-price{font-size:19px}
.hosting-buy-btn{display:inline-block;margin-top:8px;min-width:130px;text-align:center;padding:11px 22px;border-radius:9px;background:#2563eb;color:#fff;font-weight:700;font-size:14px}
.hosting-buy-btn:hover{background:#1d4ed8}
.hosting-buy-btn[aria-disabled="true"]{background:#e8edf4;color:#667085;cursor:not-allowed;pointer-events:none}
.hosting-more-btn{display:inline-block;margin-top:8px;margin-left:8px;min-width:130px;text-align:center;padding:10px 22px;border-radius:9px;border:1px solid var(--line);background:#fff;color:#1d4ed8;font-weight:700;font-size:14px}
.hosting-more-btn:hover{background:#eff6ff}
.hosting-period-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:14px}
.hosting-period-tab{border:1px solid var(--line);background:#fff;color:#475569;border-radius:999px;padding:9px 18px;font-size:13px;font-weight:700;cursor:pointer}
.hosting-period-tab.is-active{background:#0f172a;color:#fff;border-color:#0f172a}
.hosting-type-row{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}
.hosting-type-row a{border:1px solid var(--line);background:#fff;color:#475569;border-radius:999px;padding:9px 18px;font-size:13px;font-weight:700}
.hosting-type-row a.is-active{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}
@media(max-width:640px){.hosting-plan-buy{margin-left:0;text-align:left}}
</style>
</head><body class="domain-app">@include('partials.sidebar', ['active' => $active, 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])
<main class="domain-main">@include('partials.topbar', ['title' => $title, 'avatar' => strtoupper(substr(session('customer.name', 'C'), 0, 2)), 'cartCount' => count(session('domain_cart', [])), 'customerName' => session('customer.name', 'Client'), 'customerEmail' => session('customer.email', '')])<div class="domain-content standalone-content">
<span class="domain-kicker">{{ $kicker }}</span><h1 class="workspace-title">{{ $title }} plans.</h1><p class="workspace-subtitle">{{ $subtitle }}</p>

<div class="hosting-type-row" aria-label="Hosting type">
    <a href="{{ route('client.hosting', 'shared') }}" {!! $type === 'shared' ? 'class="is-active"' : '' !!}>Shared</a>
    <a href="{{ route('client.hosting', 'vps') }}" {!! $type === 'vps' ? 'class="is-active"' : '' !!}>VPS</a>
    <a href="{{ route('client.hosting', 'dedicated') }}" {!! $type === 'dedicated' ? 'class="is-active"' : '' !!}>Dedicated</a>
</div>

<div class="hosting-period-row" role="tablist" aria-label="Billing period">
    <button class="hosting-period-tab is-active" type="button" role="tab" aria-selected="true" data-period="monthly">Monthly</button>
    <button class="hosting-period-tab" type="button" role="tab" aria-selected="false" data-period="yearly">Yearly</button>
    <button class="hosting-period-tab" type="button" role="tab" aria-selected="false" data-period="twoYear">2 Years</button>
    <button class="hosting-period-tab" type="button" role="tab" aria-selected="false" data-period="threeYear">3 Years</button>
</div>

<div class="hosting-plan-list">
@foreach($plans as $plan)
    <div class="hosting-plan-row{{ $plan['badge'] === 'Most popular' ? ' is-featured' : '' }}">
        <span class="hosting-plan-icon" aria-hidden="true">▤</span>
        <div class="hosting-plan-main">
            <strong>{{ $plan['name'] }}@if($plan['badge'])<span class="hosting-plan-badge">{{ $plan['badge'] }}</span>@endif</strong>
            <small>{{ $plan['description'] }}</small>
            <span class="hosting-plan-specs">{{ collect($plan['primary_features'])->map(function ($f) { return $f['monthly'] . ' ' . $f['name']; })->implode(' · ') }}</span>
        </div>
        <div class="hosting-plan-buy">
            <span class="hosting-plan-price">৳<span class="hosting-price-value" data-monthly="{{ $plan['prices']['monthly'] }}" data-yearly="{{ $plan['prices']['yearly'] }}" data-two-year="{{ $plan['prices']['twoYear'] }}" data-three-year="{{ $plan['prices']['threeYear'] }}">{{ number_format($plan['prices']['monthly']) }}</span><small class="hosting-price-unit"> /month</small></span>
            <a class="hosting-buy-btn" href="{{ route('support') }}">Buy now</a>
            <a class="hosting-more-btn" href="{{ route('hosting.' . $type) }}">See more</a>
        </div>
    </div>
@endforeach
</div>
<p class="workspace-subtitle" style="margin-top:16px">Buying opens a support request — our team confirms and provisions your {{ strtolower($title) }} plan.</p>
</div>
</main>

<script>
(function(){
    var units={monthly:' /month',yearly:' /year',twoYear:' /2 years',threeYear:' /3 years'};
    var tabs=document.querySelectorAll('.hosting-period-tab');
    function select(tab){
        var period=tab.dataset.period;
        var soon=(period==='twoYear'||period==='threeYear');
        tabs.forEach(function(t){var on=(t===tab);t.classList.toggle('is-active',on);t.setAttribute('aria-selected',on?'true':'false');});
        document.querySelectorAll('.hosting-plan-row').forEach(function(row){
            var price=row.querySelector('.hosting-price-value');
            var unit=row.querySelector('.hosting-price-unit');
            var buy=row.querySelector('.hosting-buy-btn');
            var wrap=row.querySelector('.hosting-plan-buy');
            price.textContent=soon?'Coming soon':Number(price.dataset[period]).toLocaleString('en-US');
            unit.textContent=soon?'':units[period];
            wrap.classList.toggle('is-coming-soon',soon);
            buy.setAttribute('aria-disabled',soon?'true':'false');
            buy.textContent=soon?'Coming soon':'Buy now';
        });
    }
    tabs.forEach(function(tab){tab.addEventListener('click',function(){select(tab);});});
})();
</script>
@include('partials.chat')
</body></html>
