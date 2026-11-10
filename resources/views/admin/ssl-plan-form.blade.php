<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'SSL plan'])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}">
    <style>
        .ssl-form{max-width:980px}.ssl-panel{margin-top:18px}.ssl-panel h2{margin:0 0 15px}.ssl-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px 18px}.ssl-grid label{font-size:12px;font-weight:700;color:#77879a;text-transform:uppercase;letter-spacing:.06em}.ssl-grid input{display:block;width:100%;box-sizing:border-box;margin-top:5px;padding:11px 13px;border:1px solid #dfe6ee;border-radius:7px;font:15px 'DM Sans',sans-serif}.ssl-checks{display:flex;gap:18px;flex-wrap:wrap;margin-top:15px}.ssl-checks label{display:inline-flex;align-items:center;gap:7px;color:#344256;font-size:13px;font-weight:700}.ssl-rows{display:flex;flex-direction:column;gap:8px}.ssl-row{display:grid;grid-template-columns:1fr 150px 70px;gap:8px;align-items:center}.ssl-row input[type=text]{width:100%;box-sizing:border-box;padding:9px 11px;border:1px solid #dfe6ee;border-radius:6px;font:14px 'DM Sans',sans-serif}.ssl-row label{display:flex;align-items:center;gap:6px;text-transform:none;font-size:13px}.ssl-row button{padding:8px;border:1px solid #dfe6ee;border-radius:6px;background:#fff;cursor:pointer}.ssl-add{margin-top:12px;padding:9px 15px;border:1px solid #dfe6ee;border-radius:7px;background:#fff;color:#1d4ed8;font-weight:700;cursor:pointer}.ssl-note{color:#8190a1;font-size:13px}.ssl-save{display:flex;gap:10px;align-items:center;margin-top:20px}@media(max-width:700px){.ssl-row{grid-template-columns:1fr}.ssl-row button{justify-self:start}}
    </style>
</head>
<body class="domain-app admin-app">
@include('partials.admin-sidebar', ['active' => 'ssl-plans'])
<main class="domain-main">
    <header class="domain-topbar"><div><span class="crumb">CMS /</span><strong>SSL plans</strong></div><a class="cart-badge" href="{{ route('admin.ssl.plans') }}">↑ Back to list</a></header>
    <div class="domain-content standalone-content ssl-form">
        <span class="domain-kicker">{{ $plan ? 'EDIT SSL PLAN' : 'ADD SSL PLAN' }}</span>
        <h1 class="workspace-title">{{ $plan ? 'Edit ' . $plan->name : 'Add an SSL plan' }}.</h1>
        <p class="workspace-subtitle">All prices are BDT. Term totals are calculated from the monthly price and discount percentages.</p>
        @if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif
        @php($features = $plan ? $plan->features : collect())
        <form method="POST" action="{{ $plan ? route('admin.ssl.plans.update', $plan) : route('admin.ssl.plans.store') }}">@csrf
            <section class="store-panel ssl-panel"><h2>Plan details</h2><div class="ssl-grid">
                <label>Plan key<input name="plan_key" value="{{ old('plan_key', $plan->plan_key ?? '') }}" placeholder="positive-ssl" required></label>
                <label>Name<input name="name" value="{{ old('name', $plan->name ?? '') }}" placeholder="Positive SSL" required></label>
                <label>Badge<input name="badge" value="{{ old('badge', $plan->badge ?? '') }}" placeholder="Most popular"></label>
                <label>Sort order<input type="number" name="sort" value="{{ old('sort', $plan->sort ?? 1) }}" min="0" max="999"></label>
                <label>Tagline<input name="tagline" value="{{ old('tagline', $plan->tagline ?? '') }}" placeholder="Validation & encryption on a budget"></label>
            </div><div class="ssl-checks"><label><input type="checkbox" name="is_visible" value="1" {{ (!$plan || $plan->is_visible) ? 'checked' : '' }}> Plan visible</label></div></section>
            <section class="store-panel ssl-panel"><h2>Pricing and terms</h2><div class="ssl-grid">
                <label>Monthly price (৳)<input type="number" step="0.01" min="0" name="monthly_price" value="{{ old('monthly_price', $plan->monthly_price ?? '') }}" required></label>
                <label>Renewal price (৳/mo)<input type="number" step="0.01" min="0" name="renewal_price" value="{{ old('renewal_price', $plan->renewal_price ?? '') }}" required></label>
                <label>1-year discount (%)<input type="number" step="0.01" min="0" max="100" name="discount_one_year" value="{{ old('discount_one_year', $plan->discount_one_year ?? 0) }}" required></label>
                <label>2-year discount (%)<input type="number" step="0.01" min="0" max="100" name="discount_two_year" value="{{ old('discount_two_year', $plan->discount_two_year ?? 5) }}" required></label>
                <label>3-year discount (%)<input type="number" step="0.01" min="0" max="100" name="discount_three_year" value="{{ old('discount_three_year', $plan->discount_three_year ?? 10) }}" required></label>
            </div><div class="ssl-checks">
                @foreach([1 => '1 Year', 2 => '2 Years', 3 => '3 Years'] as $years => $label)
                    <label><input type="checkbox" name="term_{{ $years === 1 ? 'one' : ($years === 2 ? 'two' : 'three') }}_year_active" value="1" {{ (!$plan || $plan->{'term_' . ($years === 1 ? 'one' : ($years === 2 ? 'two' : 'three')) . '_year_active'}) ? 'checked' : '' }}> {{ $label }} active</label>
                    <label><input type="checkbox" name="term_{{ $years === 1 ? 'one' : ($years === 2 ? 'two' : 'three') }}_year_visible" value="1" {{ (!$plan || $plan->{'term_' . ($years === 1 ? 'one' : ($years === 2 ? 'two' : 'three')) . '_year_visible'}) ? 'checked' : '' }}> visible</label>
                @endforeach
            </div><p class="ssl-note">Inactive terms remain in the selector as unavailable. Hidden terms are not offered to customers.</p></section>
            <section class="store-panel ssl-panel"><h2>Features</h2><div class="ssl-rows" id="sslRows">
                @foreach($features as $i => $feature)<div class="ssl-row"><input type="text" name="features[{{ $i }}][text]" value="{{ $feature->feature }}" placeholder="Domain Validation"><label><input type="checkbox" name="features[{{ $i }}][is_available]" value="1" {{ $feature->is_available ? 'checked' : '' }}> Available</label><button type="button" class="ssl-remove">Remove</button></div>@endforeach
            </div><button type="button" class="ssl-add" id="sslAdd">+ Add feature</button></section>
            <div class="ssl-save"><button class="checkout-submit" type="submit">{{ $plan ? 'Save SSL plan' : 'Create SSL plan' }} <b>→</b></button><a href="{{ route('admin.ssl.plans') }}">Cancel</a></div>
        </form>
    </div>
</main>
<script>
(function(){var rows=document.getElementById('sslRows'), add=document.getElementById('sslAdd'), index={{ $features->count() }};function wire(row){row.querySelector('.ssl-remove').addEventListener('click',function(){row.remove();});}document.querySelectorAll('.ssl-row').forEach(wire);add.addEventListener('click',function(){var row=document.createElement('div');row.className='ssl-row';row.innerHTML='<input type="text" name="features['+index+'][text]" placeholder="Domain Validation"><label><input type="checkbox" name="features['+index+'][is_available]" value="1" checked> Available</label><button type="button" class="ssl-remove">Remove</button>';rows.appendChild(row);wire(row);index++;});}());
</script>
</body>
</html>
