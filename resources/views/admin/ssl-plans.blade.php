<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'SSL plans'])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}">
    <style>
        .ssl-admin-wrap{max-width:none}.ssl-admin-table{overflow:auto;background:#fff;border:1px solid #e5ebf3;border-radius:12px}.ssl-admin-table table{width:100%;min-width:980px;border-collapse:collapse}.ssl-admin-table th{padding:13px;text-align:left;background:#f7f9fc;color:#77879a;font-size:11px;text-transform:uppercase;letter-spacing:.7px}.ssl-admin-table td{padding:15px 13px;border-top:1px solid #edf1f5;vertical-align:top}.ssl-admin-name{font-weight:800;font-size:16px}.ssl-admin-name small{display:block;color:#8190a1;font-weight:400;margin-top:3px}.ssl-admin-badge{display:inline-block;margin-left:6px;padding:3px 8px;border-radius:999px;background:#eff6ff;color:#1d4ed8;font-size:10px;font-weight:800}.ssl-admin-price{font-weight:800;white-space:nowrap}.ssl-admin-features{margin:0;padding:0;list-style:none;color:#53657a;font-size:13px}.ssl-admin-features li{margin:3px 0}.ssl-admin-features .off{text-decoration:line-through;color:#b91c1c}.ssl-admin-state{display:inline-block;padding:5px 9px;border-radius:999px;font-size:12px;font-weight:800}.ssl-admin-state.on{background:#dcfce7;color:#166534}.ssl-admin-state.off{background:#fef3c7;color:#92400e}.ssl-admin-actions{display:flex;gap:7px;flex-wrap:wrap}.ssl-admin-actions a,.ssl-admin-actions button{padding:8px 11px;border:1px solid #dfe6ee;border-radius:7px;background:#fff;color:#334155;font-size:12px;font-weight:700;text-decoration:none;cursor:pointer}.ssl-admin-actions .danger:hover{color:#b91c1c;border-color:#fca5a5}
    </style>
</head>
<body class="domain-app admin-app">
@include('partials.admin-sidebar', ['active' => 'ssl-plans'])
<main class="domain-main">
    <header class="domain-topbar"><div><span class="crumb">CMS /</span><strong>SSL plans</strong></div><a class="cart-badge" href="{{ route('services.coming-soon', 'ssl-certificate') }}" target="_blank">View website ↗</a></header>
    <div class="domain-content standalone-content ssl-admin-wrap">
        <span class="domain-kicker">CONTENT MANAGEMENT</span>
        <h1 class="workspace-title">SSL plans.</h1>
        <p class="workspace-subtitle">Control SSL card names, pricing, term discounts, features, visibility and ordering. Changes appear on the public page and customer area.</p>
        @if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif
        <p><a class="checkout-submit" href="{{ route('admin.ssl.plans.create') }}">+ Add SSL plan</a></p>
        <div class="ssl-admin-table">
            <table>
                <thead><tr><th>Plan</th><th>Price</th><th>Term discounts</th><th>Features</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($plans as $plan)
                    <tr>
                        <td><span class="ssl-admin-name">{{ $plan->name }} @if($plan->badge)<span class="ssl-admin-badge">{{ $plan->badge }}</span>@endif</span><small>{{ $plan->plan_key }} · order {{ $plan->sort }}</small><small>{{ $plan->tagline }}</small></td>
                        <td><span class="ssl-admin-price">৳{{ number_format($plan->monthly_price) }}/mo</span><br><small>Renew ৳{{ number_format($plan->renewal_price) }}/mo</small></td>
                        <td>1 year: {{ number_format($plan->discount_one_year, 2) }}%<br>2 years: {{ number_format($plan->discount_two_year, 2) }}%<br>3 years: {{ number_format($plan->discount_three_year, 2) }}%</td>
                        <td><ul class="ssl-admin-features">@foreach($plan->features as $feature)<li class="{{ $feature->is_available ? '' : 'off' }}">{{ $feature->is_available ? '✓' : '×' }} {{ $feature->feature }}</li>@endforeach</ul></td>
                        <td><span class="ssl-admin-state {{ $plan->is_visible ? 'on' : 'off' }}">{{ $plan->is_visible ? 'Visible' : 'Hidden' }}</span><br><small>1y {{ $plan->term_one_year_active ? 'on' : 'soon' }} · 2y {{ $plan->term_two_year_active ? 'on' : 'soon' }} · 3y {{ $plan->term_three_year_active ? 'on' : 'soon' }}</small></td>
                        <td><div class="ssl-admin-actions"><a href="{{ route('admin.ssl.plans.edit', $plan) }}">Edit</a><form method="POST" action="{{ route('admin.ssl.plans.visibility', $plan) }}">@csrf<button type="submit">{{ $plan->is_visible ? 'Hide' : 'Show' }}</button></form><form method="POST" action="{{ route('admin.ssl.plans.delete', $plan) }}" onsubmit="return confirm('Delete this SSL plan?')">@csrf<button class="danger" type="submit">Delete</button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6">No SSL plans yet. Add the first plan above.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>
