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
.hosting-compare-wrap{margin-top:28px;background:#fff;border:1px solid var(--line);border-radius:12px;padding:24px;box-shadow:0 8px 25px #102c4f08}
.hosting-compare-title{font:800 20px 'Manrope';color:#0f172a;margin:0 0 4px}
.hosting-compare-scroll{overflow-x:auto;margin-top:16px;border:1px solid var(--line);border-radius:10px}
.hosting-compare-table{width:100%;min-width:680px;border-collapse:collapse;font-size:13.5px}
.hosting-compare-table th,.hosting-compare-table td{padding:12px 16px;border-bottom:1px solid #eef2f7;white-space:nowrap}
.hosting-compare-table thead th{background:#f8fafc;font:800 14px 'Manrope';color:#0f172a;text-align:center}
.hosting-compare-table thead th:first-child{position:sticky;left:0;z-index:2;text-align:left}
.hosting-compare-table tbody th{color:#475569;font-weight:600;background:#fff;text-align:left;position:sticky;left:0;z-index:1}
.hosting-compare-table tbody td{text-align:center;color:#0f172a}
.hosting-compare-table thead th.is-featured{box-shadow:inset 0 3px 0 #2563eb}
.hosting-compare-table th.is-featured,.hosting-compare-table td.is-featured{background:#eff6ff}
.hosting-compare-price{font:800 17px 'Manrope';color:#0f172a}
.hosting-compare-unit{color:#64748b;font-size:11px}
.hosting-compare-na{color:#cbd5e1}
.hosting-compare-table .hosting-buy-btn{min-width:0;margin-top:0;padding:9px 20px;font-size:13px}
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
    <button class="hosting-period-tab is-active" type="button" role="tab" aria-selected="true" data-period="monthly" data-soon="{{ $terms['monthly']['soon'] ? 'true' : 'false' }}">Monthly</button>
    <button class="hosting-period-tab" type="button" role="tab" aria-selected="false" data-period="yearly" data-soon="{{ $terms['yearly']['soon'] ? 'true' : 'false' }}">Yearly</button>
    <button class="hosting-period-tab" type="button" role="tab" aria-selected="false" data-period="twoYear" data-soon="{{ $terms['twoYear']['soon'] ? 'true' : 'false' }}">2 Years</button>
    <button class="hosting-period-tab" type="button" role="tab" aria-selected="false" data-period="threeYear" data-soon="{{ $terms['threeYear']['soon'] ? 'true' : 'false' }}">3 Years</button>
</div>

<div class="hosting-plan-list">
@foreach($plans as $plan)
@php
    // Per-term name + specs summary (the CMS stores separate names,
    // features and prices per billing term).
    $specLines = [];
    foreach ($plan['feature_sets'] as $specTerm => $specSet) {
        $parts = [];
        foreach ($specSet['primary'] as $f) {
            $parts[] = trim($f['lead'] . ' ' . $f['name']);
        }
        $specLines[$specTerm] = implode(' · ', $parts);
    }
@endphp
    <div class="hosting-plan-row{{ $plan['badge'] === 'Most popular' ? ' is-featured' : '' }}" data-visible-monthly="{{ $plan['visibility']['monthly'] ? 'true' : 'false' }}" data-visible-yearly="{{ $plan['visibility']['yearly'] ? 'true' : 'false' }}" data-visible-twoyear="{{ $plan['visibility']['twoYear'] ? 'true' : 'false' }}" data-visible-threeyear="{{ $plan['visibility']['threeYear'] ? 'true' : 'false' }}">
        <span class="hosting-plan-icon" aria-hidden="true">▤</span>
        <div class="hosting-plan-main">
            <strong><span class="hosting-plan-name" data-monthly-name="{{ $plan['names']['monthly'] }}" data-yearly-name="{{ $plan['names']['yearly'] }}" data-two-year-name="{{ $plan['names']['twoYear'] }}" data-three-year-name="{{ $plan['names']['threeYear'] }}">{{ $plan['names']['monthly'] }}</span>@if($plan['badge'])<span class="hosting-plan-badge">{{ $plan['badge'] }}</span>@endif</strong>
            <small>{{ $plan['description'] }}</small>
            <span class="hosting-plan-specs" data-monthly="{{ $specLines['monthly'] }}" data-yearly="{{ $specLines['yearly'] }}" data-two-year="{{ $specLines['twoYear'] }}" data-three-year="{{ $specLines['threeYear'] }}">{{ $specLines['monthly'] }}</span>
        </div>
        <div class="hosting-plan-buy{{ $plan['terms']['monthly'] ? '' : ' is-coming-soon' }}">
            <span class="hosting-plan-price">৳<span class="hosting-price-value" data-monthly="{{ $plan['prices']['monthly'] }}" data-yearly="{{ $plan['prices']['yearly'] }}" data-two-year="{{ $plan['prices']['twoYear'] }}" data-three-year="{{ $plan['prices']['threeYear'] }}" data-soon-monthly="{{ $plan['terms']['monthly'] ? 'false' : 'true' }}" data-soon-yearly="{{ $plan['terms']['yearly'] ? 'false' : 'true' }}" data-soon-twoyear="{{ $plan['terms']['twoYear'] ? 'false' : 'true' }}" data-soon-threeyear="{{ $plan['terms']['threeYear'] ? 'false' : 'true' }}">{{ $plan['terms']['monthly'] ? number_format($plan['prices']['monthly']) : 'Coming soon' }}</span><small class="hosting-price-unit"> /month</small></span>
            <a class="hosting-buy-btn" href="{{ route('support') }}" {!! $plan['terms']['monthly'] ? '' : 'aria-disabled="true"' !!}>{{ $plan['terms']['monthly'] ? 'Buy now' : 'Coming soon' }}</a>
            <a class="hosting-more-btn" href="{{ route('hosting.' . $type) }}">See more</a>
        </div>
    </div>
@endforeach
</div>
<div class="hosting-compare-wrap">
<h2 class="hosting-compare-title">Compare {{ $title }} plans</h2>
<p class="workspace-subtitle">Every spec, side by side. Prices follow the billing period you pick above.</p>
<div class="hosting-compare-scroll">
<table class="hosting-compare-table">
<thead>
<tr>
<th>Features</th>
@foreach($plans as $plan)
<th class="{{ $plan['badge'] === 'Most popular' ? 'is-featured' : '' }}"><span class="hosting-plan-name" data-monthly-name="{{ $plan['names']['monthly'] }}" data-yearly-name="{{ $plan['names']['yearly'] }}" data-two-year-name="{{ $plan['names']['twoYear'] }}" data-three-year-name="{{ $plan['names']['threeYear'] }}">{{ $plan['names']['monthly'] }}</span>@if($plan['badge'])<span class="hosting-plan-badge">{{ $plan['badge'] }}</span>@endif</th>
@endforeach
</tr>
</thead>
<tbody>
<tr>
<th>Price</th>
@php
    // Per-plan, per-term compare values: primary cells hold the bold lead
    // ("10 GB"), extra cells hold the label ("bandwidth/month") and every
    // term's own text (the CMS stores separate feature rows per term).
    $valueMaps = [];
    foreach ($plans as $pi => $plan) {
        $vm = ['primary' => [], 'extra' => []];
        foreach ($plan['feature_sets'] as $t => $set) {
            foreach ($set['primary'] as $f) {
                $name = trim((string) $f['name']);
                if ($name === '' || isset($vm['primary'][$name][$t])) {
                    continue;
                }
                $vm['primary'][$name][$t] = $f['lead'];
            }
            foreach ($set['extra'] as $f) {
                $text = trim((string) $f['text']);
                if (preg_match('/^([\d,]+(?:\s*(?:GB|TB|MB))?)(.*)$/', $text, $m) && trim($m[2]) !== '') {
                    $label = trim($m[2]);
                } else {
                    $label = $text;
                }
                if ($label !== '' && !isset($vm['extra'][$label][$t])) {
                    $vm['extra'][$label][$t] = $text;
                }
            }
        }
        $valueMaps[$pi] = $vm;
    }
@endphp
@foreach($plans as $plan)
<td class="{{ $plan['badge'] === 'Most popular' ? 'is-featured' : '' }}"><span class="hosting-compare-price" data-monthly="{{ $plan['prices']['monthly'] }}" data-yearly="{{ $plan['prices']['yearly'] }}" data-two-year="{{ $plan['prices']['twoYear'] }}" data-three-year="{{ $plan['prices']['threeYear'] }}" data-soon-monthly="{{ $plan['terms']['monthly'] ? 'false' : 'true' }}" data-soon-yearly="{{ $plan['terms']['yearly'] ? 'false' : 'true' }}" data-soon-twoyear="{{ $plan['terms']['twoYear'] ? 'false' : 'true' }}" data-soon-threeyear="{{ $plan['terms']['threeYear'] ? 'false' : 'true' }}">{{ $plan['terms']['monthly'] ? '৳' . number_format($plan['prices']['monthly']) : 'Coming soon' }}</span><small class="hosting-compare-unit"> /month</small></td>
@endforeach
</tr>
@php $primaryNames = collect($plans)->flatMap(function ($p) { return collect($p['primary_features'])->pluck('name'); })->unique()->values(); @endphp
@foreach($primaryNames as $fname)
<tr>
<th>{{ $fname }}</th>
@foreach($plans as $pi => $plan)
@php $pm = $valueMaps[$pi]['primary'][$fname] ?? null; $m0 = $pm['monthly'] ?? null; @endphp
<td class="{{ $plan['badge'] === 'Most popular' ? 'is-featured' : '' }}">@if($pm)<span class="hosting-compare-val" data-monthly="{{ $m0 }}" data-yearly="{{ $pm['yearly'] ?? $m0 }}" data-two-year="{{ $pm['twoYear'] ?? $m0 }}" data-three-year="{{ $pm['threeYear'] ?? $m0 }}">{{ $m0 }}</span>@else<span class="hosting-compare-na">—</span>@endif</td>
@endforeach
</tr>
@endforeach
@php
$extraLabels = [];
foreach ($plans[0]['extra_features'] as $ef) {
    $t = trim($ef['monthly']);
    if (preg_match('/^([\d,]+(?:\s*(?:GB|TB|MB))?)(.*)$/', $t, $m) && trim($m[2]) !== '') { $extraLabels[] = trim($m[2]); }
    else { $extraLabels[] = $t; }
}
$extraLabels = array_values(array_unique($extraLabels));
@endphp
@foreach($extraLabels as $elabel)
<tr>
<th>{{ $elabel }}</th>
@foreach($plans as $pi => $plan)
@php
$em = $valueMaps[$pi]['extra'][$elabel] ?? null;
$m0 = $em['monthly'] ?? null;
if (preg_match('/^([\d,]+(?:\s*(?:GB|TB|MB))?)(.*)$/', (string) $m0, $mx) && trim($mx[2]) !== '') { $v0 = trim($mx[1]); }
else { $v0 = '✓'; }
$y0 = $em['yearly'] ?? $m0;
$t0 = $em['twoYear'] ?? $m0;
$tr0 = $em['threeYear'] ?? $m0;
@endphp
<td class="{{ $plan['badge'] === 'Most popular' ? 'is-featured' : '' }}">@if($em)<span class="hosting-compare-val" data-monthly="{{ $m0 }}" data-yearly="{{ $y0 }}" data-two-year="{{ $t0 }}" data-three-year="{{ $tr0 }}">{{ $v0 }}</span>@else<span class="hosting-compare-na">—</span>@endif</td>
@endforeach
</tr>
@endforeach
<tr>
<th></th>
@foreach($plans as $plan)
<td class="{{ $plan['badge'] === 'Most popular' ? 'is-featured' : '' }}"><a class="hosting-buy-btn hosting-compare-buy" href="{{ route('support') }}" {!! $plan['terms']['monthly'] ? '' : 'aria-disabled="true"' !!}>{{ $plan['terms']['monthly'] ? 'Buy now' : 'Coming soon' }}</a></td>
@endforeach
</tr>
</tbody>
</table>
</div>
</div>
<p class="workspace-subtitle" style="margin-top:16px">Buying opens a support request — our team confirms and provisions your {{ strtolower($title) }} plan.</p>
</div>
</main>

<script>
(function(){
    var units={monthly:' /month',yearly:' /year',twoYear:' /2 years',threeYear:' /3 years'};
    var tabs=document.querySelectorAll('.hosting-period-tab');
    var cmpPrices=document.querySelectorAll('.hosting-compare-price');
    var cmpUnits=document.querySelectorAll('.hosting-compare-unit');
    var cmpVals=document.querySelectorAll('.hosting-compare-val');
    var cmpBuys=document.querySelectorAll('.hosting-compare-buy');
    // Every row and every compare column carries its own data-soon-* flags
    // (data-soon-monthly / -yearly / -twoyear / -threeyear), so a card
    // without the selected billing term shows "Coming soon" on its own.
    var soonKey=function(period){return 'soon'+period.charAt(0).toUpperCase()+period.slice(1).toLowerCase();};
    function select(tab){
        var period=tab.dataset.period;
        tabs.forEach(function(t){var on=(t===tab);t.classList.toggle('is-active',on);t.setAttribute('aria-selected',on?'true':'false');});
        document.querySelectorAll('.hosting-plan-row').forEach(function(row){
            var price=row.querySelector('.hosting-price-value');
            var unit=row.querySelector('.hosting-price-unit');
            var buy=row.querySelector('.hosting-buy-btn');
            var wrap=row.querySelector('.hosting-plan-buy');
            var soon=price.dataset[soonKey(period)]==='true';
            var visible=row.dataset['visible'+period.charAt(0).toUpperCase()+period.slice(1).toLowerCase()] === 'true';
            row.hidden=!visible;
            price.textContent=soon?'Coming soon':Number(price.dataset[period]).toLocaleString('en-US');
            unit.textContent=soon?'':units[period];
            wrap.classList.toggle('is-coming-soon',soon);
            buy.setAttribute('aria-disabled',soon?'true':'false');
            buy.textContent=soon?'Coming soon':'Buy now';
            // Plan name + specs summary follow the billing term too.
            var nameEl=row.querySelector('.hosting-plan-name');
            if(nameEl){var termName=nameEl.dataset[period+'Name'];if(termName!==undefined){nameEl.textContent=termName;}}
            var specsEl=row.querySelector('.hosting-plan-specs');
            if(specsEl){var termSpecs=specsEl.dataset[period];if(termSpecs!==undefined){specsEl.textContent=termSpecs;}}
        });
        // Compare-table plan names follow the selected term as well.
        document.querySelectorAll('.hosting-compare-table [data-monthly-name]').forEach(function(el){
            var termName=el.dataset[period+'Name'];
            if(termName!==undefined){el.textContent=termName;}
        });
        // Compare values carry all four terms' feature rows via
        // data-monthly / -yearly / -two-year / -three-year (the keys match
        // the tab periods), so a term shows that term's rows.
        var resPeriod=period;
        cmpPrices.forEach(function(price){
            var soon=price.dataset[soonKey(period)]==='true';
            price.textContent=soon?'Coming soon':'৳'+Number(price.dataset[period]).toLocaleString('en-US');
        });
        cmpUnits.forEach(function(unit){
            var price=unit.previousElementSibling;
            var soon=price&&price.dataset[soonKey(period)]==='true';
            unit.textContent=soon?'':units[period];
        });
        cmpVals.forEach(function(val){val.textContent=val.dataset[resPeriod];});
        cmpPrices.forEach(function(price,i){
            var soon=price.dataset[soonKey(period)]==='true';
            var buy=cmpBuys[i];
            if(!buy){return;}
            buy.setAttribute('aria-disabled',soon?'true':'false');
            buy.textContent=soon?'Coming soon':'Buy now';
        });
    }
    tabs.forEach(function(tab){tab.addEventListener('click',function(){select(tab);});});
})();
</script>
@include('partials.chat')
</body></html>
