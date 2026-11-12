<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'SSL plans'])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}">
    <style>
        /* Three columns: the plan (+ its copy), its ResellerClub pricing, and what
           you can do to it. Term discounts, features and the per-term flag matrix now
           live on the Edit page, so the list stays scannable. */
        .ssl-admin-wrap{max-width:none}

        .ssl-head{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;flex-wrap:wrap;margin-bottom:22px}
        .ssl-head-copy .domain-kicker{display:block;margin-bottom:10px}
        .ssl-head-copy h1{margin:0 0 6px}
        .ssl-head-copy p{margin:0;max-width:62ch}
        .ssl-new{display:inline-flex;align-items:center;gap:9px;padding:12px 20px;border-radius:11px;background:#1f6fe5;color:#fff;font:700 14px 'DM Sans',sans-serif;text-decoration:none;box-shadow:0 12px 26px -14px rgba(31,111,229,.85);transition:background .18s ease,box-shadow .18s ease,transform .18s ease}
        .ssl-new:hover{background:#175ac4;box-shadow:0 16px 30px -14px rgba(31,111,229,.95)}
        .ssl-new:active{transform:translateY(1px)}
        .ssl-new b{font-size:17px;line-height:1}

        .ssl-card-list{background:#fff;border:1px solid #e5ebf3;border-radius:14px;overflow:hidden;box-shadow:0 1px 2px rgba(15,35,64,.04)}

        .ssl-list-head,.ssl-row{display:grid;grid-template-columns:minmax(0,.9fr) minmax(0,1.9fr) 150px;gap:20px;align-items:center}
        .ssl-list-head{padding:12px 22px;background:#f7f9fc;border-bottom:1px solid #edf1f5;color:#77879a;font-size:11px;font-weight:800;letter-spacing:.7px;text-transform:uppercase}
        .ssl-list-head span:nth-child(3){text-align:right}

        .ssl-row{padding:18px 22px;border-top:1px solid #edf1f5;transition:background .16s ease}
        .ssl-row:first-child{border-top:0}
        .ssl-row:hover{background:#fbfdff}
        .ssl-row.is-hidden{background:#fcfcfd}
        .ssl-row.is-hidden .ssl-name{color:#8496a9}

        .ssl-plan{min-width:0}
        .ssl-name{display:flex;align-items:center;gap:9px;flex-wrap:wrap;color:#0f2340;font:800 17px/1.3 'Manrope',sans-serif;letter-spacing:-.2px}
        .ssl-badge{padding:3px 9px;border-radius:999px;background:#eff6ff;color:#1d4ed8;font:800 10px 'DM Sans',sans-serif;letter-spacing:.4px;text-transform:uppercase}
        .ssl-pill-hidden{padding:3px 9px;border-radius:999px;background:#fef3c7;color:#92400e;font:800 10px 'DM Sans',sans-serif;letter-spacing:.4px;text-transform:uppercase}
        .ssl-tagline{margin:5px 0 0;color:#6f8398;font-size:13.5px;line-height:1.5}
        .ssl-meta{margin:7px 0 0;color:#93a2b3;font-size:12px}
        .ssl-meta code{font:600 12px 'DM Sans',sans-serif;color:#7b8ea3}

        .ssl-money{text-align:right;white-space:nowrap}
        .ssl-money strong{display:block;color:#0f2340;font:800 21px/1.1 'Manrope',sans-serif;letter-spacing:-.5px}
        .ssl-money small{display:block;margin-top:3px;color:#93a2b3;font-size:12px}

        .ssl-actions{display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap}
        .ssl-actions form{margin:0}
        .ssl-actions a,.ssl-actions button{display:inline-flex;align-items:center;justify-content:center;min-width:74px;padding:9px 14px;border:1px solid #dfe6ee;border-radius:9px;background:#fff;color:#334155;font:700 13px 'DM Sans',sans-serif;text-decoration:none;cursor:pointer;transition:border-color .16s ease,color .16s ease,background .16s ease}
        .ssl-actions a:hover{border-color:#1f6fe5;color:#1f6fe5;background:#f5f9ff}
        .ssl-actions button:hover{border-color:#b9c9db;background:#f8fafc}
        .ssl-actions .danger:hover{border-color:#fca5a5;color:#b91c1c;background:#fff5f5}

        .ssl-actions{flex-direction:column;align-items:stretch}
        .ssl-actions a,.ssl-actions button{width:100%}

        /* ── ResellerClub pricing (read-only) ─────────────────────────────────
           Cost and profit exist ONLY on this admin page. Customer pages get the
           selling price alone, from SslPlanService. */
        .ssl-rc{min-width:0}
        .ssl-rc-scroll{overflow-x:auto}
        .ssl-rc table{width:100%;min-width:520px;border-collapse:collapse}
        .ssl-rc th,.ssl-rc td{padding:7px 10px;text-align:right;font-size:12.5px;white-space:nowrap}
        .ssl-rc th{color:#8496a9;font:800 10px 'DM Sans',sans-serif;letter-spacing:.6px;text-transform:uppercase;border-bottom:1px solid #edf2f8}
        .ssl-rc th:first-child,.ssl-rc td:first-child{text-align:left;color:#334155;font-weight:700}
        .ssl-rc tbody tr + tr td{border-top:1px solid #eef3f9}
        .ssl-rc .is-null{color:#b6c3d0}
        .ssl-rc .is-profit{color:#16865d;font-weight:800}
        .ssl-rc .is-loss{color:#b91c1c;font-weight:800}
        .ssl-rc .is-sell{color:#0f2340;font-weight:800}
        .ssl-rc .is-bdt{color:#0f2340;font:800 13.5px 'Manrope',sans-serif}
        .ssl-rc-note{margin:8px 0 0;color:#8ea0b3;font-size:11.5px;line-height:1.55}
        .ssl-rc-note b{color:#5d7491}
        .ssl-rc-warn{padding:10px 12px;border:1px solid #f2e0c0;border-radius:9px;background:#fffaf1;color:#8a5f10;font-size:12.5px;line-height:1.55}
        .ssl-rc-warn.is-bad{border-color:#f4d6d6;background:#fff7f7;color:#a32b2b}

        .ssl-empty{padding:56px 24px;text-align:center;color:#8496a9}
        .ssl-empty strong{display:block;margin-bottom:6px;color:#334155;font:800 16px 'Manrope',sans-serif}

        @media(max-width:900px){
            .ssl-list-head{display:none}
            .ssl-row{grid-template-columns:minmax(0,1fr);gap:14px;padding:18px}
            .ssl-actions{flex-direction:row;justify-content:flex-start}
            .ssl-actions a,.ssl-actions button{width:auto}
        }
    </style>
</head>
<body class="domain-app admin-app">
@include('partials.admin-sidebar', ['active' => 'ssl-plans'])
<main class="domain-main">
    <header class="domain-topbar"><div><span class="crumb">CMS /</span><strong>SSL plans</strong></div><a class="cart-badge" href="{{ route('services.coming-soon', 'ssl-certificate') }}" target="_blank">View website ↗</a></header>
    <div class="domain-content standalone-content ssl-admin-wrap">
        @if(session('success'))<div class="domain-alert success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif
        @if(!empty($cmsUnavailable))<div class="domain-alert error">{{ $cmsUnavailable }}</div>@endif

        <div class="ssl-head">
            <div class="ssl-head-copy">
                <span class="domain-kicker">CONTENT MANAGEMENT</span>
                <h1 class="workspace-title">SSL plans.</h1>
                <p class="workspace-subtitle">Prices, profit and selling price come from ResellerClub and cannot be edited here. Open a plan to change its name, tagline, features or visibility.</p>
            </div>
            <a class="ssl-new" href="{{ route('admin.ssl.plans.create') }}"><b>+</b> Add SSL plan</a>
        </div>

        @php
            // Money format helpers for this page only.
            $usd = function ($v) {
                return $v === null ? '—' : '$' . number_format((float) $v, 2, '.', '');
            };
        @endphp

        <div class="ssl-card-list">
            <div class="ssl-list-head">
                <span>Plan</span>
                <span>ResellerClub pricing (USD per month)</span>
                <span>Actions</span>
            </div>

            @forelse($plans as $plan)
                @php
                    $planRcId = $plan->resellerclubPlanId();
                    $planPrices = $planRcId !== null && isset($rcPricing[$planRcId]) ? $rcPricing[$planRcId] : null;

                    // Only the new-order price list is shown (what a customer
                    // pays when buying). One row per tenure ResellerClub sells.
                    $rcTenures = [];
                    if ($planPrices) {
                        foreach ((array) ($planPrices['add'] ?? []) as $rcMonths => $rcCell) {
                            if (isset($rcCell['selling']) && (float) $rcCell['selling'] > 0) {
                                $rcTenures[] = (int) $rcMonths;
                            }
                        }
                        sort($rcTenures);
                    }
                @endphp
                <div class="ssl-row{{ $plan->is_visible ? '' : ' is-hidden' }}">
                    <div class="ssl-plan">
                        <span class="ssl-name">
                            {{ $plan->name }}
                            @if($plan->badge)<span class="ssl-badge">{{ $plan->badge }}</span>@endif
                            @unless($plan->is_visible)<span class="ssl-pill-hidden">Hidden</span>@endunless
                        </span>
                        @if($plan->tagline)<p class="ssl-tagline">{{ $plan->tagline }}</p>@endif
                        <p class="ssl-meta"><code>{{ $plan->plan_key }}</code> · {{ $plan->features->count() }} {{ \Illuminate\Support\Str::plural('feature', $plan->features->count()) }} · order {{ $plan->sort }}@if($planRcId !== null) · ResellerClub ID {{ $planRcId }}@endif</p>
                    </div>

                    <div class="ssl-rc">
                        @if($planRcId === null)
                            <div class="ssl-rc-warn">
                                <b>Not linked to ResellerClub.</b> Edit the plan and enter its ResellerClub plan ID
                                (299 Premium, 300 Standard, 301 Wildcard, 672 EV) to get its price.
                            </div>
                        @elseif(!$rcTenures)
                            <div class="ssl-rc-warn{{ $rcStatus === 'unavailable' ? ' is-bad' : '' }}">
                                @if($rcStatus === 'not_configured')
                                    <b>ResellerClub price not loaded.</b> The integration is switched off on this server.
                                @else
                                    <b>No price returned for plan ID {{ $planRcId }}.</b> Check the ID and that this server's IP is whitelisted in ResellerClub.
                                @endif
                                @if((float) $plan->monthly_price > 0)
                                    Customers currently see the last saved price, ৳{{ number_format($plan->monthly_price) }}/mo.
                                @else
                                    This plan is hidden from customers until a price is available.
                                @endif
                            </div>
                        @else
                            <div class="ssl-rc-scroll">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Term</th>
                                            <th>Reseller price</th>
                                            <th>Profit %</th>
                                            <th>Revenue</th>
                                            <th>Selling price</th>
                                            <th>Customer pays</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($rcTenures as $rcMonths)
                                            @php
                                                $rcCell = $planPrices['add'][$rcMonths];
                                                $rcCost = $rcCell['cost'] ?? null;
                                                $rcSelling = (float) $rcCell['selling'];
                                                $rcPercent = $rcCell['profit_percent'] ?? null;
                                                $rcProfit = $rcCell['profit_amount'] ?? null;
                                                $rcLoss = $rcCost !== null && $rcSelling < (float) $rcCost;
                                                $rcBdt = $usdRate > 0 ? round($rcSelling * $rcMonths * $usdRate) : null;
                                            @endphp
                                            <tr>
                                                <td>{{ $rcMonths % 12 === 0 ? ($rcMonths / 12) . ' ' . \Illuminate\Support\Str::plural('year', $rcMonths / 12) : $rcMonths . ' months' }}</td>
                                                <td><span class="{{ $rcCost === null ? 'is-null' : '' }}">{{ $usd($rcCost) }}</span></td>
                                                <td>
                                                    @if($rcPercent === null)
                                                        <span class="is-null" title="ResellerClub did not return a cost for this term.">—</span>
                                                    @else
                                                        <span class="{{ $rcLoss ? 'is-loss' : 'is-profit' }}">{{ rtrim(rtrim(number_format((float) $rcPercent, 2, '.', ''), '0'), '.') }}%</span>
                                                    @endif
                                                </td>
                                                <td><span class="{{ $rcProfit === null ? 'is-null' : ($rcLoss ? 'is-loss' : 'is-profit') }}">{{ $usd($rcProfit) }}</span></td>
                                                <td class="is-sell">{{ $usd($rcSelling) }}</td>
                                                <td class="is-bdt">{{ $rcBdt !== null ? '৳' . number_format($rcBdt) : '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="ssl-rc-note">
                                Revenue = selling − reseller price. Profit % = revenue ÷ reseller price (worked out from the API
                                prices, so it can differ by a fraction from the % set in the panel because ResellerClub rounds to
                                whole cents). “Customer pays” is the selling price for the whole term in Taka at 1 USD = ৳{{ number_format($usdRate, 2) }}.
                            </p>
                        @endif
                    </div>

                    <div class="ssl-actions">
                        <a href="{{ route('admin.ssl.plans.edit', $plan) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.ssl.plans.visibility', $plan) }}">@csrf<button type="submit">{{ $plan->is_visible ? 'Hide' : 'Show' }}</button></form>
                        <form method="POST" action="{{ route('admin.ssl.plans.delete', $plan) }}" onsubmit="return confirm('Delete the “{{ $plan->name }}” plan? This cannot be undone.')">@csrf<button class="danger" type="submit">Delete</button></form>
                    </div>
                </div>
            @empty
                <div class="ssl-empty">
                    <strong>No SSL plans yet</strong>
                    Add your first plan and it will appear here and on the public page.
                </div>
            @endforelse
        </div>
    </div>
</main>
</body>
</html>
