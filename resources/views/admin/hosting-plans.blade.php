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
        .hp-tabs { display:flex; gap:8px; margin:18px 0 4px; flex-wrap:wrap; }
        .hp-tabs a { border:1px solid var(--line); background:#fff; color:#475569; border-radius:999px; padding:10px 22px; font-size:15px; font-weight:700; text-decoration:none; }
        .hp-tabs a small { margin-left:6px; opacity:.7; font-weight:700; }
        .hp-tabs a.is-active { background:#2563eb; color:#fff; border-color:#2563eb; }
        .hp-subtabs { display:flex; gap:6px; margin:10px 0 0; flex-wrap:wrap; }
        .hp-subtabs a { border:1px solid var(--line); background:#fff; color:#475569; border-radius:999px; padding:7px 16px; font-size:13px; font-weight:700; text-decoration:none; }
        .hp-subtabs a.is-active { background:#1d4ed8; color:#fff; border-color:#1d4ed8; }
        .hp-note { margin:12px 2px 0; color:#8190a1; font-size:13px; }
        .hp-warn { margin:16px 0 0; padding:12px 16px; border:1px solid #f3d9a4; border-radius:8px; background:#fff8e6; color:#8a5a00; font-size:13px; }
        .hp-bar { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin:16px 0; }
        .hp-btn { display:inline-block; padding:11px 20px; border:1px solid #dfe6ee; border-radius:8px; background:#fff; color:#334155; font:700 14px 'DM Sans',sans-serif; text-decoration:none; cursor:pointer; }
        .hp-btn.primary { background:#2563eb; border-color:#2563eb; color:#fff; }
        .hp-btn.primary:hover { background:#1d4ed8; }
        .hp-table-wrap { overflow-x:auto; background:#fff; border:1px solid #e5ebf3; border-radius:12px; box-shadow:0 8px 24px rgba(31,41,55,.04); }
        table.hp-table { width:100%; min-width:980px; border-collapse:collapse; font-size:14px; }
        .hp-table th { padding:12px 12px; background:#f7f9fc; border-bottom:1px solid #e5ebf3; color:#77879a; font-size:11px; font-weight:800; letter-spacing:.8px; text-align:left; text-transform:uppercase; white-space:nowrap; }
        .hp-table td { padding:12px; border-bottom:1px solid #edf1f5; color:#344256; vertical-align:middle; }
        .hp-table tr:last-child td { border-bottom:0; }
        .hp-table tr.is-hidden-card td { background:#fafafa; }
        .hp-table tr.is-hidden-card .hp-plan-name { opacity:.6; }
        .hp-plan-name { font-weight:800; font-size:15px; color:#1f2937; min-width:150px; }
        .hp-plan-name small { display:block; margin-top:3px; font-weight:400; font-size:12px; color:#8190a1; }
        .hp-badge { display:inline-block; margin-left:6px; padding:2px 9px; border-radius:999px; background:#eff6ff; color:#1d4ed8; font-size:10px; font-weight:800; text-transform:uppercase; vertical-align:middle; }
        .hp-term { min-width:140px; }
        .hp-term input[type=number], .hp-term select, .hp-sort { box-sizing:border-box; width:100%; padding:8px 9px; border:1px solid #dfe6ee; border-radius:6px; font:14px 'DM Sans',sans-serif; background:#fff; }
        .hp-sort { width:64px; }
        .hp-show { display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:700; }
        .hp-actions { display:flex; gap:6px; }
        .hp-actions a, .hp-actions button { display:inline-block; padding:7px 12px; border:1px solid #dfe6ee; border-radius:7px; background:#fff; color:#334155; font:700 12px 'DM Sans',sans-serif; text-decoration:none; cursor:pointer; }
        .hp-actions a:hover { border-color:#93b4e7; color:#1d4ed8; }
        .hp-actions .danger:hover { border-color:#fca5a5; color:#b91c1c; background:#fff5f5; }
        .hp-empty { padding:44px 20px; text-align:center; color:#8190a1; font-size:14px; }
        .hp-empty p { margin:0 0 14px; }
        .hp-legend { margin:12px 2px 0; color:#8190a1; font-size:12px; }
        @media(max-width:1200px){ .domain-content.standalone-content { padding-right:24px; padding-left:24px; } }
    </style>
</head>
<body class="domain-app admin-app">
    @include('partials.admin-sidebar', ['active' => 'hosting-plans'])
    <main class="domain-main">
        <header class="domain-topbar"><div><span class="crumb">CMS /</span><strong>Hosting plans</strong></div><a class="cart-badge" href="{{ route('landing') }}" target="_blank">View website &#8599;</a></header>
        <div class="domain-content standalone-content">
            <span class="domain-kicker">CONTENT MANAGEMENT</span>
            <h1 class="workspace-title">Hosting plans.</h1>
            <p class="workspace-subtitle">Pick a section, change prices in the table, then press Save. Use Edit to change a card&rsquo;s text and features.</p>
            @if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="domain-alert error">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif
            @unless($regionReady)
            <div class="hp-warn"><strong>Run the region SQL first.</strong> The database has no <code>region</code> column yet, so only Local plans work. Run <code>database/hosting_regions.sql</code> in phpMyAdmin (or <code>php artisan migrate</code>), then reload this page.</div>
            @endunless

            @php
                $sectionLabel = $region === 'wordpress' ? 'WordPress' : (\App\Models\HostingPlan::REGION_SHORT[$region] . ' ' . \App\Models\HostingPlan::TYPE_LABELS[$type]);
                $regionTotals = [];
                foreach ($counts as $r => $byType) { $regionTotals[$r] = array_sum($byType); }
            @endphp
            <div class="hp-tabs">
                @foreach(\App\Models\HostingPlan::REGIONS as $regionKey => $regionName)
                <a href="{{ route('admin.hosting.plans', ['region' => $regionKey, 'type' => $regionKey === 'wordpress' ? 'wordpress' : ($region === $regionKey ? $type : 'shared')]) }}" {!! $region === $regionKey ? 'class="is-active"' : '' !!}>{{ $regionName }}<small>{{ $regionTotals[$regionKey] ?? 0 }}</small></a>
                @endforeach
            </div>
            @if($region !== 'wordpress')
            <div class="hp-subtabs">
                @foreach(\App\Models\HostingPlan::TYPES as $typeKey)
                <a href="{{ route('admin.hosting.plans', ['region' => $region, 'type' => $typeKey]) }}" {!! $type === $typeKey ? 'class="is-active"' : '' !!}>{{ \App\Models\HostingPlan::TYPE_LABELS[$typeKey] }} ({{ $counts[$region][$typeKey] ?? 0 }})</a>
                @endforeach
            </div>
            @endif

            <div class="hp-bar">
                <a class="hp-btn primary" href="{{ route('admin.hosting.plans.create', ['region' => $region, 'type' => $type]) }}">+ Add {{ $sectionLabel }} card</a>
                @if($canCopy)
                <form method="POST" action="{{ route('admin.hosting.plans.copy', ['region' => $region, 'type' => $type]) }}" onsubmit="return confirm('Copy the Local {{ \App\Models\HostingPlan::TYPE_LABELS[$copyFromType] }} cards into {{ $sectionLabel }}? The copies start hidden.')">@csrf
                    <button type="submit" class="hp-btn">Copy from Local {{ \App\Models\HostingPlan::TYPE_LABELS[$copyFromType] }}</button>
                </form>
                @endif
            </div>

            @if($plans->isEmpty())
            <div class="hp-table-wrap">
                <div class="hp-empty">
                    <p>No cards yet for <strong>{{ $sectionLabel }}</strong>. Visitors see a &ldquo;Plans coming soon&rdquo; message.</p>
                    <p>Add the first card{{ $canCopy ? ', or copy the Local cards as a starting point' : '' }}.</p>
                </div>
            </div>
            @else
            <form method="POST" action="{{ route('admin.hosting.plans.save', ['region' => $region, 'type' => $type]) }}">
                @csrf
                <div class="hp-table-wrap">
                    <table class="hp-table">
                        <thead>
                            <tr>
                                <th>Card</th>
                                <th>Order</th>
                                <th>Show</th>
                                @foreach($terms as $termKey => $termMeta)
                                <th>{{ $termMeta['label'] }} (&#2547;)</th>
                                @endforeach
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plans as $plan)
                            <tr{!! $plan->is_visible ? '' : ' class="is-hidden-card"' !!}>
                                <td class="hp-plan-name">
                                    {{ $plan->name }}
                                    @if($plan->badge)<span class="hp-badge">{{ $plan->badge }}</span>@endif
                                </td>
                                <td><input class="hp-sort" type="number" min="0" max="999" name="rows[{{ $plan->id }}][sort]" value="{{ $plan->sort }}"></td>
                                <td><label class="hp-show"><input type="checkbox" name="rows[{{ $plan->id }}][visible]" value="1" {{ $plan->is_visible ? 'checked' : '' }}> Show</label></td>
                                @foreach($terms as $termKey => $termMeta)
                                <td class="hp-term">
                                    <input type="number" step="0.01" min="0" name="rows[{{ $plan->id }}][price][{{ $termKey }}]" value="{{ $plan->{$termMeta['column']} !== null ? rtrim(rtrim(number_format((float) $plan->{$termMeta['column']}, 2, '.', ''), '0'), '.') : '' }}" placeholder="Price">
                                </td>
                                @endforeach
                                <td>
                                    <div class="hp-actions">
                                        <a href="{{ route('admin.hosting.plans.edit', $plan) }}">Edit</a>
                                        <button type="submit" class="danger" formaction="{{ route('admin.hosting.plans.delete', $plan) }}" formnovalidate onclick="return confirm('Delete card {{ addslashes($plan->name) }}? This also removes its features.')">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="hp-bar"><button type="submit" class="hp-btn primary">Save changes</button></div>
            </form>
            @endif
        </div>
    </main>
</body>
</html>
