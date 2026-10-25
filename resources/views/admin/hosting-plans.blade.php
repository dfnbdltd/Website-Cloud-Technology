<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Hosting plans'])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}">
    <style>
        /* Full available width on this page (standalone-content caps at 1000px). */
        .domain-content.standalone-content { max-width:none; padding-right:42px; }
        .standalone-content .domain-kicker { font-size:11px; }
        .standalone-content .workspace-title { font-size:40px; }
        .standalone-content .workspace-subtitle { font-size:15px; max-width:720px; }
        .hp-tabs { display:flex; gap:8px; margin:16px 0 4px; }
        .hp-tabs a { border:1px solid var(--line); background:#fff; color:#475569; border-radius:999px; padding:10px 22px; font-size:15px; font-weight:700; text-decoration:none; }
        .hp-tabs a.is-active { background:#2563eb; color:#fff; border-color:#2563eb; }
        .hp-subtabs { display:flex; gap:6px; margin:10px 0 0; flex-wrap:wrap; }
        .hp-subtabs span { align-self:center; font-size:11px; font-weight:800; letter-spacing:.7px; text-transform:uppercase; color:#8190a1; margin-right:2px; }
        .hp-subtabs a { border:1px solid var(--line); background:#fff; color:#475569; border-radius:999px; padding:7px 16px; font-size:13px; font-weight:700; text-decoration:none; }
        .hp-subtabs a.is-active { background:#1d4ed8; color:#fff; border-color:#1d4ed8; }
        .hp-note { margin:10px 2px 0; color:#8190a1; font-size:13px; }
        .hp-table-wrap { overflow-x:auto; background:#fff; border:1px solid #e5ebf3; border-radius:12px; box-shadow:0 8px 24px rgba(31,41,55,.04); padding:4px 2px 2px; }
        table.hp-table { width:100%; min-width:960px; border-collapse:collapse; font-size:14px; }
        .hp-table th { padding:14px 14px; background:#f7f9fc; border-bottom:1px solid #e5ebf3; color:#77879a; font-size:11px; font-weight:800; letter-spacing:.8px; text-align:left; text-transform:uppercase; white-space:nowrap; }
        .hp-table td { padding:16px 14px; border-bottom:1px solid #edf1f5; color:#344256; vertical-align:top; }
        .hp-table tr:last-child td { border-bottom:0; }
        .hp-plan-name { font-weight:800; font-size:16px; color:#1f2937; }
        .hp-plan-name small { display:block; margin-top:4px; font-weight:400; font-size:13px; color:#8190a1; }
        .hp-badge { display:inline-block; padding:3px 10px; border-radius:999px; background:#eff6ff; color:#1d4ed8; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; vertical-align:middle; }
        .hp-main-name { display:block; color:#8190a1; font-size:12px; }
        .hp-table tr.is-hidden-card td { opacity:.55; }
        .hp-table tr.is-hidden-card td:first-child { position:relative; }
        .hp-table tr.is-hidden-card td:first-child::before { content:"Hidden — not on any page"; position:absolute; top:-2px; right:0; font-size:10px; color:#b0651f; background:#fbf3e4; border-radius:999px; padding:2px 8px; font-weight:700; }
        .hp-class { color:#8190a1; font-size:12px; }
        .hp-price { white-space:nowrap; font-weight:700; font-size:16px; }
        .hp-sub { color:#8190a1; font-size:12px; }
        .hp-state { display:inline-block; padding:5px 11px; border-radius:999px; font-size:12px; font-weight:800; }
        .hp-state.visible { color:#166534; background:#dcfce7; }
        .hp-state.hidden { color:#92400e; background:#fef3c7; }
        .hp-order { font-size:15px; font-weight:800; color:#344256; }
        .hp-actions { display:flex; gap:7px; flex-wrap:wrap; }
        .hp-actions a, .hp-actions button { display:inline-block; padding:8px 14px; border:1px solid #dfe6ee; border-radius:7px; background:#fff; color:#334155; font-size:12px; font-weight:700; text-decoration:none; cursor:pointer; }
        .hp-actions a:hover, .hp-actions button:hover { border-color:#93b4e7; color:#1d4ed8; background:#f7faff; }
        .hp-actions .danger:hover { border-color:#fca5a5; color:#b91c1c; background:#fff5f5; }
        .hp-add { margin:16px 0; }
        .hp-add a { display:inline-block; padding:12px 22px; border:1px solid #2563eb; border-radius:8px; background:#2563eb; color:#fff; font-size:14px; font-weight:700; text-decoration:none; }
        .hp-add a:hover { background:#1d4ed8; }
        .hp-empty { padding:40px; text-align:center; color:#8190a1; font-size:14px; }
        @media(max-width:1200px){ .domain-content.standalone-content { padding-right:24px; padding-left:24px; } }
    </style>
</head>
<body class="domain-app admin-app">
    @include('partials.admin-sidebar', ['active' => 'hosting-plans'])
    <main class="domain-main">
        <header class="domain-topbar"><div><span class="crumb">CMS /</span><strong>Hosting plans</strong></div><a class="cart-badge" href="{{ route('landing') }}" target="_blank">View website ↗</a></header>
        <div class="domain-content standalone-content">
            <span class="domain-kicker">CONTENT MANAGEMENT</span>
            <h1 class="workspace-title">Hosting plans.</h1>
            <p class="workspace-subtitle">Edit the plan cards on the Shared / VPS / Dedicated hosting pages and in the client area: prices, features, check/cross availability and billing terms.</p>
            @if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif

            <div class="hp-tabs">
                @foreach(\App\Models\HostingPlan::TYPES as $availableType)
                <a href="{{ route('admin.hosting.plans', ['type' => $availableType, 'term' => $term]) }}" {!! $type === $availableType ? 'class="is-active"' : '' !!}>{{ ucfirst($availableType) }}</a>
                @endforeach
            </div>
            <div class="hp-subtabs">
                <span>Billing term</span>
                @foreach(['monthly' => 'Monthly', 'yearly' => 'Yearly', 'twoYear' => '2 Years', 'threeYear' => '3 Years'] as $termKey => $termLabel)
                <a href="{{ route('admin.hosting.plans', ['type' => $type, 'term' => $termKey]) }}" {!! $term === $termKey ? 'class="is-active"' : '' !!}>{{ $termLabel }}</a>
                @endforeach
            </div>
            <p class="hp-note">Showing <strong>{{ $termMeta['label'] }}</strong> per card. Each card &times; billing term is edited separately — click Edit (price, availability, and features for this term are inside) and save there.</p>

            <div class="hp-add"><a href="{{ route('admin.hosting.plans.create', ['type' => $type]) }}?term={{ $term }}">+ Add {{ ucfirst($type) }} plan card</a></div>

            @if($plans->isEmpty())
            <div class="hp-table-wrap">
                <div class="hp-empty">No plan cards yet for {{ ucfirst($type) }} hosting. Add the first one above.</div>
            </div>
            @else
            <div class="hp-table-wrap">
                <table class="hp-table">
                    <thead>
                        <tr>
                            <th>Card</th>
                            <th>{{ $termMeta['label'] }} price (৳)</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plans as $plan)
                        <tr{{ $plan->visibleFor($term) ? '' : ' class="is-hidden-card"' }}>
                            <td class="hp-plan-name">
                                {{ $plan->nameFor($term) }}
                                @if($plan->badge)<span class="hp-badge">{{ $plan->badge }}</span>@endif
                                @if($term !== 'monthly')
                                <small class="hp-main-name">&mdash; Monthly name: {{ $plan->name }}</small>
                                @endif
                                <small>{{ $plan->description }}</small>
                                <span class="hp-class">{{ $plan->style_class }}</span>
                            </td>
                            <td>
                                <span class="hp-price">{{ $plan->{$termMeta['column']} !== null ? '৳' . number_format((float) $plan->{$termMeta['column']}) : '&mdash;' }}</span>
                            </td>
                            <td class="hp-order">{{ $plan->sort }}</td>
                            <td><span class="hp-state {{ $plan->visibleFor($term) ? 'visible' : 'hidden' }}">{{ $plan->visibleFor($term) ? 'Visible' : 'Hidden' }}</span></td>
                            <td>
                                <div class="hp-actions">
                                    <a href="{{ route('admin.hosting.plans.edit', $plan) }}?term={{ $term }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.hosting.plans.visibility', $plan) }}?term={{ $term }}">@csrf<button type="submit">{{ $plan->visibleFor($term) ? 'Hide' : 'Show' }}</button></form>
                                    <form method="POST" action="{{ route('admin.hosting.plans.delete', $plan) }}?term={{ $term }}" onsubmit="return confirm('Delete plan card {{ $plan->name }}? This removes its features too.')">@csrf<button type="submit" class="danger">Delete</button></form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </main>
</body>
</html>
