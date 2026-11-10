<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => $plan ? 'Edit SSL plan' : 'Add SSL plan'])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}">
    <style>
        /* Two-column editor: the form on the left, a live preview + reference
           panel on the right. Prices recompute as you type, so the discount
           percentages stop being abstract. */
        .ssl-edit{max-width:1180px}
        .ssl-edit-grid{display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:22px;align-items:start}

        .ssl-panel{margin:0 0 18px;padding:22px 24px;border:1px solid #e5ebf3;border-radius:14px;background:#fff;box-shadow:0 1px 2px rgba(15,35,64,.04)}
        .ssl-panel:last-child{margin-bottom:0}
        .ssl-panel-head{display:flex;align-items:center;gap:10px;margin-bottom:18px}
        .ssl-panel-num{display:grid;place-items:center;flex:none;width:24px;height:24px;border-radius:7px;background:#eef4ff;color:#1f6fe5;font:800 12px 'Manrope',sans-serif}
        .ssl-panel-head h2{margin:0;color:#0f2340;font:800 16px/1.2 'Manrope',sans-serif;letter-spacing:-.2px}
        .ssl-panel-head p{margin:0;color:#8496a9;font-size:12.5px}

        .ssl-fields{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px 18px}
        .ssl-field{display:block;min-width:0}
        .ssl-field > span{display:block;margin-bottom:6px;color:#5c7590;font:700 11px 'DM Sans',sans-serif;letter-spacing:.7px;text-transform:uppercase}
        .ssl-field input{display:block;width:100%;box-sizing:border-box;padding:11px 13px;border:1px solid #d8e2ee;border-radius:9px;background:#fff;color:#182b46;font:15px 'DM Sans',sans-serif;transition:border-color .16s ease,box-shadow .16s ease}
        .ssl-field input:hover{border-color:#b9c9db}
        .ssl-field input:focus{border-color:#1f6fe5;outline:0;box-shadow:0 0 0 3px rgba(31,111,229,.15)}
        .ssl-field small{display:block;margin-top:6px;color:#93a2b3;font-size:12px;line-height:1.45}
        .ssl-field input.has-error{border-color:#e0837f;box-shadow:0 0 0 3px rgba(224,131,127,.14)}
        .ssl-prefix{position:relative}
        .ssl-prefix i{position:absolute;top:50%;left:12px;transform:translateY(-50%);color:#93a2b3;font:600 14px 'DM Sans',sans-serif;font-style:normal;pointer-events:none}
        .ssl-prefix input{padding-left:28px}
        .ssl-prefix-suffix input{padding-right:34px}
        .ssl-prefix-suffix em{position:absolute;top:50%;right:11px;transform:translateY(-50%);color:#93a2b3;font:600 12px 'DM Sans',sans-serif;font-style:normal;pointer-events:none}

        .ssl-switch{display:inline-flex;align-items:center;gap:10px;margin-top:4px;cursor:pointer}
        .ssl-switch input{position:absolute;opacity:0;width:0;height:0}
        .ssl-switch-track{position:relative;flex:none;width:40px;height:22px;border-radius:999px;background:#d8e2ee;transition:background .18s ease}
        .ssl-switch-track::after{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(15,35,64,.25);content:"";transition:transform .18s ease}
        .ssl-switch input:checked + .ssl-switch-track{background:#1f6fe5}
        .ssl-switch input:checked + .ssl-switch-track::after{transform:translateX(18px)}
        .ssl-switch input:focus-visible + .ssl-switch-track{outline:3px solid rgba(31,111,229,.3);outline-offset:2px}
        .ssl-switch span:last-child{color:#334155;font-size:13.5px;font-weight:600}

        /* Feature rows */
        .ssl-rows{display:flex;flex-direction:column;gap:9px}
        .ssl-row{display:grid;grid-template-columns:minmax(0,1fr) 40px;gap:9px;align-items:center}
        .ssl-row input{width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid #d8e2ee;border-radius:8px;font:14px 'DM Sans',sans-serif}
        .ssl-row input:focus{border-color:#1f6fe5;outline:0;box-shadow:0 0 0 3px rgba(31,111,229,.15)}
        .ssl-row button{display:grid;place-items:center;width:38px;height:38px;border:1px solid #dfe6ee;border-radius:8px;background:#fff;color:#8496a9;font-size:17px;line-height:1;cursor:pointer;transition:border-color .16s ease,color .16s ease,background .16s ease}
        .ssl-row button:hover{border-color:#fca5a5;color:#b91c1c;background:#fff5f5}
        .ssl-add{display:inline-flex;align-items:center;gap:8px;margin-top:12px;padding:9px 15px;border:1px dashed #c3d3e5;border-radius:9px;background:#fbfdff;color:#1f6fe5;font:700 13px 'DM Sans',sans-serif;cursor:pointer;transition:border-color .16s ease,background .16s ease}
        .ssl-add:hover{border-color:#1f6fe5;background:#f4f9ff}
        .ssl-rows-empty{padding:14px;border:1px dashed #dbe5f0;border-radius:9px;color:#93a2b3;font-size:13px;text-align:center}

        /* Right rail */
        .ssl-aside{position:sticky;top:18px}
        .ssl-preview{border:1px solid #e5ebf3;border-radius:14px;background:#fff;padding:20px;box-shadow:0 1px 2px rgba(15,35,64,.04)}
        .ssl-preview-label{margin:0 0 12px;color:#93a2b3;font:800 10px 'DM Sans',sans-serif;letter-spacing:1.2px;text-transform:uppercase}
        .ssl-preview-name{margin:0 0 2px;color:#0f2340;font:800 16px 'Manrope',sans-serif;letter-spacing:-.2px}
        .ssl-preview-tag{margin:0 0 14px;color:#7b8ea3;font-size:12.5px;line-height:1.45;min-height:1.4em}
        .ssl-preview-price{display:flex;align-items:center;gap:6px;color:#0f2340;font-family:'Manrope',sans-serif}
        .ssl-preview-price .taka{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:7px;background:#e8f7f1;color:#0f9d76;font:800 14px 'DM Sans',sans-serif}
        .ssl-preview-price .amount{font-size:30px;font-weight:800;letter-spacing:-1px;line-height:1}
        .ssl-preview-price .per{color:#8496a9;font:600 12px 'DM Sans',sans-serif}
        .ssl-preview-terms{margin:14px 0 0;padding:12px 0 0;border-top:1px solid #eef2f7;list-style:none}
        .ssl-preview-terms li{display:flex;justify-content:space-between;gap:10px;padding:4px 0;color:#5c7590;font-size:12.5px}
        .ssl-preview-terms li b{color:#182b46;font-weight:700;font-variant-numeric:tabular-nums}
        .ssl-preview-terms li.is-save b{color:#0f9d76}
        .ssl-preview-terms .pct{color:#93a2b3;font-weight:600}

        .ssl-help{margin-top:14px;padding:16px 18px;border:1px solid #dbe9f7;border-radius:12px;background:#f6faff;color:#5c7590;font-size:12.5px;line-height:1.6}
        .ssl-help strong{display:block;margin-bottom:5px;color:#334155;font:800 12px 'DM Sans',sans-serif;letter-spacing:.4px;text-transform:uppercase}

        .ssl-save{display:flex;align-items:center;gap:14px;margin-top:20px;flex-wrap:wrap}
        .ssl-save button{display:inline-flex;align-items:center;gap:9px;padding:13px 24px;border:0;border-radius:11px;background:#1f6fe5;color:#fff;font:700 14.5px 'DM Sans',sans-serif;cursor:pointer;box-shadow:0 12px 26px -14px rgba(31,111,229,.85);transition:background .18s ease,box-shadow .18s ease}
        .ssl-save button:hover{background:#175ac4}
        .ssl-save button b{font-size:16px;line-height:1}
        .ssl-save a{color:#6f8398;font-size:13.5px;font-weight:600;text-decoration:none}
        .ssl-save a:hover{color:#1f6fe5}

        @media(max-width:1080px){
            .ssl-edit-grid{grid-template-columns:minmax(0,1fr)}
            .ssl-aside{position:static;order:-1}
        }
        @media(max-width:620px){
            .ssl-panel{padding:18px}
            .ssl-row{grid-template-columns:minmax(0,1fr) 38px}
        }
    </style>
</head>
<body class="domain-app admin-app">
@include('partials.admin-sidebar', ['active' => 'ssl-plans'])
<main class="domain-main">
    <header class="domain-topbar"><div><span class="crumb">CMS / SSL plans /</span><strong>{{ $plan ? 'Edit' : 'Add' }}</strong></div><a class="cart-badge" href="{{ route('admin.ssl.plans') }}">↑ Back to list</a></header>
    <div class="domain-content standalone-content ssl-edit">
        @if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif

        <div class="ssl-head-copy" style="margin-bottom:20px">
            <span class="domain-kicker">{{ $plan ? 'EDIT SSL PLAN' : 'ADD SSL PLAN' }}</span>
            <h1 class="workspace-title" style="margin:0 0 6px">{{ $plan ? $plan->name : 'A new SSL plan' }}.</h1>
            <p class="workspace-subtitle" style="margin:0">Prices are in Bangladeshi Taka. All three terms are always offered — the discount percentages decide what a longer term costs.</p>
        </div>

        @php($features = $plan ? $plan->features : collect())

        <form method="POST" action="{{ $plan ? route('admin.ssl.plans.update', $plan) : route('admin.ssl.plans.store') }}" id="sslForm">@csrf
            <div class="ssl-edit-grid">
                <div class="ssl-col">
                    {{-- 1 ─ Identity --}}
                    <section class="ssl-panel">
                        <div class="ssl-panel-head">
                            <span class="ssl-panel-num">1</span>
                            <div><h2>Plan details</h2><p>How the plan is named and identified.</p></div>
                        </div>
                        <div class="ssl-fields">
                            <label class="ssl-field">
                                <span>Plan key</span>
                                <input name="plan_key" value="{{ old('plan_key', $plan->plan_key ?? '') }}" placeholder="positive-ssl" required
                                       class="{{ $errors->has('plan_key') ? 'has-error' : '' }}">
                                <small>Lowercase, dashes only. Must be unique.</small>
                            </label>
                            <label class="ssl-field">
                                <span>Display name</span>
                                <input name="name" value="{{ old('name', $plan->name ?? '') }}" placeholder="Positive SSL" required>
                            </label>
                            <label class="ssl-field" style="grid-column:1/-1">
                                <span>Tagline</span>
                                <input name="tagline" value="{{ old('tagline', $plan->tagline ?? '') }}" placeholder="Validation &amp; encryption on a budget" maxlength="255">
                                <small>One short line under the name on the card.</small>
                            </label>
                        </div>
                    </section>

                    {{-- 2 ─ Money --}}
                    <section class="ssl-panel">
                        <div class="ssl-panel-head">
                            <span class="ssl-panel-num">2</span>
                            <div><h2>Pricing</h2><p>Monthly price, renewal price and the term discounts.</p></div>
                        </div>
                        <div class="ssl-fields">
                            <label class="ssl-field ssl-prefix">
                                <span>Monthly price</span>
                                <i>৳</i>
                                <input type="number" step="0.01" min="0" name="monthly_price" id="monthlyPrice"
                                       value="{{ old('monthly_price', $plan->monthly_price ?? '') }}" required>
                                <small>The card's headline price.</small>
                            </label>
                            <label class="ssl-field ssl-prefix">
                                <span>Renewal price</span>
                                <i>৳</i>
                                <input type="number" step="0.01" min="0" name="renewal_price"
                                       value="{{ old('renewal_price', $plan->renewal_price ?? '') }}" required>
                                <small>Charged per month from year two.</small>
                            </label>
                            <label class="ssl-field ssl-prefix ssl-prefix-suffix">
                                <span>1-year discount</span>
                                <i></i>
                                <input type="number" step="0.01" min="0" max="100" name="discount_one_year" data-discount="1"
                                       value="{{ old('discount_one_year', $plan->discount_one_year ?? 0) }}" required>
                                <em>%</em>
                            </label>
                            <label class="ssl-field ssl-prefix ssl-prefix-suffix">
                                <span>2-year discount</span>
                                <i></i>
                                <input type="number" step="0.01" min="0" max="100" name="discount_two_year" data-discount="2"
                                       value="{{ old('discount_two_year', $plan->discount_two_year ?? 5) }}" required>
                                <em>%</em>
                            </label>
                            <label class="ssl-field ssl-prefix ssl-prefix-suffix">
                                <span>3-year discount</span>
                                <i></i>
                                <input type="number" step="0.01" min="0" max="100" name="discount_three_year" data-discount="3"
                                       value="{{ old('discount_three_year', $plan->discount_three_year ?? 10) }}" required>
                                <em>%</em>
                            </label>
                        </div>
                    </section>

                    {{-- 3 ─ Features --}}
                    <section class="ssl-panel">
                        <div class="ssl-panel-head">
                            <span class="ssl-panel-num">3</span>
                            <div><h2>Features</h2><p>Shown as a tick list on the card. Order is top to bottom.</p></div>
                        </div>
                        <div class="ssl-rows" id="sslRows">
                            @foreach($features as $feature)
                                <div class="ssl-row">
                                    <input type="text" name="features[{{ $loop->index }}][text]" value="{{ $feature->feature }}" placeholder="Domain Validation">
                                    <button type="button" class="ssl-remove" aria-label="Remove feature">×</button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="ssl-add" id="sslAdd"><span aria-hidden="true">+</span> Add feature</button>
                    </section>

                    <div class="ssl-save">
                        <button type="submit">{{ $plan ? 'Save changes' : 'Create plan' }} <b>→</b></button>
                        <a href="{{ route('admin.ssl.plans') }}">Cancel</a>
                    </div>
                </div>

                {{-- Right rail: live preview + guidance --}}
                <aside class="ssl-aside">
                    <div class="ssl-preview">
                        <p class="ssl-preview-label">Live preview · 1 year</p>
                        <h3 class="ssl-preview-name" id="pvName">{{ $plan->name ?? 'New plan' }}</h3>
                        <p class="ssl-preview-tag" id="pvTagline">{{ $plan->tagline ?? 'Add a tagline to describe the plan.' }}</p>
                        <div class="ssl-preview-price">
                            <span class="taka" aria-hidden="true">৳</span>
                            <span class="amount" id="pvMonthly">{{ $plan ? number_format($plan->monthly_price) : '0' }}</span>
                            <span class="per">/mo</span>
                        </div>
                        <ul class="ssl-preview-terms" id="pvTerms">
                            @foreach([1 => '1 year', 2 => '2 years', 3 => '3 years'] as $years => $label)
                                <li data-term="{{ $years }}">
                                    <span>{{ $label }} <span class="pct" data-pct="{{ $years }}"></span></span>
                                    <b data-total="{{ $years }}">—</b>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="ssl-help">
                        <strong>Visibility</strong>
                        Hidden plans disappear from the public page and the customer area, but keep their settings. Use Hide in the list for a quick publish or unpublish.
                        <label class="ssl-switch" style="margin-top:12px">
                            <input type="checkbox" name="is_visible" value="1" id="isVisible" {{ (!$plan || $plan->is_visible) ? 'checked' : '' }}>
                            <span class="ssl-switch-track" aria-hidden="true"></span>
                            <span>Show this plan</span>
                        </label>
                    </div>

                    <div class="ssl-help">
                        <strong>Order</strong>
                        Lower numbers appear first. Cards with the same order fall back to creation order.
                        <label class="ssl-field" style="margin-top:10px">
                            <input type="number" name="sort" value="{{ old('sort', $plan->sort ?? 1) }}" min="0" max="999">
                        </label>
                    </div>
                </aside>
            </div>
        </form>
    </div>
</main>
<script>
    (function () {
        var rows = document.getElementById('sslRows');
        var add = document.getElementById('sslAdd');
        var index = {{ $features->count() }};

        function wire(row) {
            row.querySelector('.ssl-remove').addEventListener('click', function () { row.remove(); });
        }

        document.querySelectorAll('.ssl-row').forEach(wire);

        add.addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'ssl-row';
            row.innerHTML = '<input type="text" name="features[' + index + '][text]" placeholder="Domain Validation">'
                + '<button type="button" class="ssl-remove" aria-label="Remove feature">×</button>';
            rows.appendChild(row);
            wire(row);
            row.querySelector('input').focus();
            index++;
        });

        /* ── Live preview ────────────────────────────────────────────────────
           Mirrors SslPlanService::resolve(): total = monthly × 12 × years,
           minus the term discount, rounded to whole taka. Server stays
           authoritative; this only stops the discount fields being abstract. */
        var monthly = document.getElementById('monthlyPrice');
        var nameInput = document.querySelector('[name="name"]');
        var taglineInput = document.querySelector('[name="tagline"]');
        var discounts = { 1: 0, 2: 5, 3: 10 };
        var fmt = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 });

        function readDiscounts() {
            document.querySelectorAll('[data-discount]').forEach(function (input) {
                var years = input.getAttribute('data-discount');
                var value = parseFloat(input.value);
                if (!isFinite(value) || value < 0) { value = 0; }
                if (value > 100) { value = 100; }
                discounts[years] = value;
            });
        }

        function render() {
            readDiscounts();

            var base = parseFloat(monthly.value);
            if (!isFinite(base) || base < 0) { base = 0; }

            document.getElementById('pvMonthly').textContent = fmt.format(base);
            document.getElementById('pvName').textContent = (nameInput.value || 'New plan');
            document.getElementById('pvTagline').textContent =
                taglineInput.value || 'Add a tagline to describe the plan.';

            [1, 2, 3].forEach(function (years) {
                var discount = discounts[years] || 0;
                var full = base * 12 * years;
                var total = Math.round(full * (1 - (discount / 100)));

                var totalEl = document.querySelector('[data-total="' + years + '"]');
                var pctEl = document.querySelector('[data-pct="' + years + '"]');
                var li = totalEl.closest('li');

                totalEl.textContent = '৳' + fmt.format(total);
                pctEl.textContent = discount > 0 ? '−' + discount + '%' : '';
                li.classList.toggle('is-save', discount > 0);
            });
        }

        [monthly, nameInput, taglineInput].forEach(function (el) {
            if (el) { el.addEventListener('input', render); }
        });
        document.querySelectorAll('[data-discount]').forEach(function (el) {
            el.addEventListener('input', render);
        });

        render();
    }());
</script>
</body>
</html>
