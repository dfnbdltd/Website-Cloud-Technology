<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'SSL plans'])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}">
    <style>
        /* Three columns only: the plan (+ its copy), the money, and what you can
           do to it. Term discounts, features and the per-term flag matrix now
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

        .ssl-list-head,.ssl-row{display:grid;grid-template-columns:minmax(0,1fr) 190px 250px;gap:20px;align-items:center}
        .ssl-list-head{padding:12px 22px;background:#f7f9fc;border-bottom:1px solid #edf1f5;color:#77879a;font-size:11px;font-weight:800;letter-spacing:.7px;text-transform:uppercase}
        .ssl-list-head span:nth-child(2){text-align:right}
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

        .ssl-empty{padding:56px 24px;text-align:center;color:#8496a9}
        .ssl-empty strong{display:block;margin-bottom:6px;color:#334155;font:800 16px 'Manrope',sans-serif}

        @media(max-width:900px){
            .ssl-list-head{display:none}
            .ssl-row{grid-template-columns:minmax(0,1fr);gap:14px;padding:18px}
            .ssl-money{text-align:left}
            .ssl-actions{justify-content:flex-start}
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
                <p class="workspace-subtitle">Each plan publishes a card on the public page and in the customer area. Open a plan to change its price, discounts or features.</p>
            </div>
            <a class="ssl-new" href="{{ route('admin.ssl.plans.create') }}"><b>+</b> Add SSL plan</a>
        </div>

        <div class="ssl-card-list">
            <div class="ssl-list-head">
                <span>Plan</span>
                <span>Price</span>
                <span>Actions</span>
            </div>

            @forelse($plans as $plan)
                <div class="ssl-row{{ $plan->is_visible ? '' : ' is-hidden' }}">
                    <div class="ssl-plan">
                        <span class="ssl-name">
                            {{ $plan->name }}
                            @if($plan->badge)<span class="ssl-badge">{{ $plan->badge }}</span>@endif
                            @unless($plan->is_visible)<span class="ssl-pill-hidden">Hidden</span>@endunless
                        </span>
                        @if($plan->tagline)<p class="ssl-tagline">{{ $plan->tagline }}</p>@endif
                        <p class="ssl-meta"><code>{{ $plan->plan_key }}</code> · {{ $plan->features->count() }} {{ \Illuminate\Support\Str::plural('feature', $plan->features->count()) }} · order {{ $plan->sort }}</p>
                    </div>

                    <div class="ssl-money">
                        <strong>৳{{ number_format($plan->monthly_price) }}<span style="font:600 13px 'DM Sans',sans-serif;color:#8496a9">/mo</span></strong>
                        <small>Renews at ৳{{ number_format($plan->renewal_price) }}/mo</small>
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
