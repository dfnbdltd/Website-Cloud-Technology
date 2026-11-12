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

        /* The plan column is now just a name, so it needs far less room and the
           pricing table gets the space the deleted lines used to take. */
        .ssl-list-head,.ssl-row{display:grid;grid-template-columns:minmax(150px,.6fr) minmax(0,2.4fr) 132px;gap:20px;align-items:center}
        .ssl-list-head{padding:12px 22px;background:#f7f9fc;border-bottom:1px solid #edf1f5;color:#77879a;font-size:11px;font-weight:800;letter-spacing:.7px;text-transform:uppercase}
        .ssl-list-head span:nth-child(3){text-align:right}

        .ssl-row{padding:14px 22px;border-top:1px solid #edf1f5;transition:background .16s ease}
        .ssl-row:first-child{border-top:0}
        .ssl-row:hover{background:#fbfdff}
        .ssl-row.is-hidden{background:#fcfcfd}
        .ssl-row.is-hidden .ssl-name{color:#8496a9}

        .ssl-plan{min-width:0}
        .ssl-name{display:flex;align-items:center;gap:9px;flex-wrap:wrap;color:#0f2340;font:800 17px/1.3 'Manrope',sans-serif;letter-spacing:-.2px}
        .ssl-pill-hidden{padding:3px 9px;border-radius:999px;background:#fef3c7;color:#92400e;font:800 10px 'DM Sans',sans-serif;letter-spacing:.4px;text-transform:uppercase}

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
        .ssl-rc table{width:100%;min-width:600px;border-collapse:collapse}
        .ssl-rc th,.ssl-rc td{padding:7px 10px;text-align:right;font-size:12.5px;white-space:nowrap}
        .ssl-rc th{color:#8496a9;font:800 10px 'DM Sans',sans-serif;letter-spacing:.6px;text-transform:uppercase;border-bottom:1px solid #edf2f8}
        .ssl-rc th:first-child,.ssl-rc td:first-child{text-align:left;color:#334155;font-weight:700}
        .ssl-rc tbody tr + tr td{border-top:1px solid #eef3f9}
        .ssl-rc .is-null{color:#b6c3d0}
        .ssl-rc .is-profit{color:#16865d;font-weight:800}
        .ssl-rc .is-loss{color:#b91c1c;font-weight:800}
        .ssl-rc .is-sell{color:#0f2340;font-weight:800}
        .ssl-rc .is-bdt{color:#0f2340;font:800 13.5px 'Manrope',sans-serif}

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
            /* Taka, not dollars.
               ResellerClub quotes every figure in USD per month (see
               ResellerClubClient::buildSslPricing()), and the owner reads this
               page in Taka — the currency the customer pays in. The conversion
               lives here, in the view, so the API client and the pricing
               service keep exactly the numbers and the logic they had.

               Same order of operations as SslPlanService (and as the customer
               cards): USD x months x rate, rounded ONCE for the whole term, and
               only then divided for the per-month display. Rounding the USD
               figure first would print a taka amount that disagrees with the
               charge. The rate is ExchangeRateService — the live
               hexarate.paikama.co USD->BDT mid rate, cached for six hours and
               the same value the sidebar shows. */
            $termTaka = function ($usdPerMonth, $months) use ($usdRate) {
                if ($usdPerMonth === null || $usdRate <= 0) {
                    return null;
                }

                return round((float) $usdPerMonth * (int) $months * $usdRate);
            };

            // That same term's money spread back over its months, rounded to a
            // whole taka: SslPlanService uses exactly this rule for the
            // customer card's `per_month`, so the admin column and the card can
            // never quote a different figure for the same plan.
            $perMonthTaka = function ($usdPerMonth, $months) use ($termTaka) {
                $term = $termTaka($usdPerMonth, $months);

                return $term === null ? null : round($term / (int) $months);
            };

            $taka = function ($v) {
                return $v === null ? '—' : '৳' . \App\Support\SslPricing::money($v);
            };
        @endphp

        <div class="ssl-card-list">
            <div class="ssl-list-head">
                <span>Plan</span>
                <span title="Converted from ResellerClub's USD price at the live rate of ৳{{ number_format($usdRate, 2) }} per USD.">ResellerClub pricing (৳ per month)</span>
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
                    {{-- Name only. The tagline, the plan_key / feature-count / order /
                         ResellerClub-ID line and the "Most popular" badge were removed at
                         the owner's request: they made every row three lines tall for
                         information that is either already on the Edit page or visible in
                         the pricing table beside it. "Hidden" stays because it is a state
                         you must be able to see at a glance in the list. --}}
                    <div class="ssl-plan">
                        <span class="ssl-name">
                            {{ $plan->name }}
                            @unless($plan->is_visible)<span class="ssl-pill-hidden">Hidden</span>@endunless
                        </span>
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
                                    Customers currently see the last saved price, ৳{{ number_format((float) $plan->monthly_price * 12) }}/year.
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
                                            <th title="What ResellerClub charges you, per month for this term.">Reseller price</th>
                                            <th title="Your markup on the reseller price: (selling − reseller price) ÷ reseller price.">Profit %</th>
                                            <th title="Your profit per month on this term, in Taka.">Revenue</th>
                                            <th title="What you charge the customer, per month for this term.">Selling price</th>
                                            <th title="The whole term in one figure — the amount the customer is actually charged.">Customer pays</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($rcTenures as $rcMonths)
                                            @php
                                                $rcCell = $planPrices['add'][$rcMonths];
                                                $rcCost = $rcCell['cost'] ?? null;
                                                $rcSelling = (float) $rcCell['selling'];
                                                // Profit % is a ratio, so it needs no conversion
                                                // and keeps coming from the API untouched.
                                                $rcPercent = $rcCell['profit_percent'] ?? null;
                                                $rcLoss = $rcCost !== null && $rcSelling < (float) $rcCost;

                                                // Every money cell in taka: the per-month figure
                                                // and the whole-term total.
                                                $rcCostMonth = $perMonthTaka($rcCost, $rcMonths);
                                                $rcSellMonth = $perMonthTaka($rcSelling, $rcMonths);
                                                $rcBdt = $termTaka($rcSelling, $rcMonths);
                                                $rcProfitMonth = ($rcCostMonth === null || $rcSellMonth === null)
                                                    ? null
                                                    : $rcSellMonth - $rcCostMonth;
                                            @endphp
                                            <tr>
                                                <td>{{ $rcMonths % 12 === 0 ? ($rcMonths / 12) . ' ' . \Illuminate\Support\Str::plural('year', $rcMonths / 12) : $rcMonths . ' months' }}</td>
                                                <td><span class="{{ $rcCostMonth === null ? 'is-null' : '' }}">{{ $taka($rcCostMonth) }}</span></td>
                                                <td>
                                                    @if($rcPercent === null)
                                                        <span class="is-null" title="ResellerClub did not return a cost for this term.">—</span>
                                                    @else
                                                        <span class="{{ $rcLoss ? 'is-loss' : 'is-profit' }}">{{ rtrim(rtrim(number_format((float) $rcPercent, 2, '.', ''), '0'), '.') }}%</span>
                                                    @endif
                                                </td>
                                                <td><span class="{{ $rcProfitMonth === null ? 'is-null' : ($rcLoss ? 'is-loss' : 'is-profit') }}">{{ $taka($rcProfitMonth) }}</span></td>
                                                <td class="is-sell">{{ $taka($rcSellMonth) }}</td>
                                                <td class="is-bdt">{{ $taka($rcBdt) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

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
