@php
    $features = $plan ? $plan->features : collect();
@endphp
<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => $plan ? 'Edit SSL plan' : 'Add SSL plan'])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}">
    <style>
        /* Single-column editor. The old right-hand rail (live preview,
           visibility note, order help) is gone: the plan's identity, link and
           feature list are the only things edited here, so they get the full
           width and the form reads top-to-bottom on every screen. */
        .ssl-edit{max-width:900px}

        .ssl-panel{margin:0 0 18px;padding:22px 24px;border:1px solid #e5ebf3;border-radius:14px;background:#fff;box-shadow:0 1px 2px rgba(15,35,64,.04)}
        .ssl-panel:last-child{margin-bottom:0}
        .ssl-panel-head{display:flex;align-items:center;gap:10px;margin-bottom:18px}
        .ssl-panel-num{display:grid;place-items:center;flex:none;width:24px;height:24px;border-radius:7px;background:#eef4ff;color:#1f6fe5;font:800 12px 'Manrope',sans-serif}
        .ssl-panel-head h2{margin:0;color:#0f2340;font:800 16px/1.2 'Manrope',sans-serif;letter-spacing:-.2px}
        .ssl-panel-head p{margin:0;color:#8496a9;font-size:12.5px}
        .ssl-field-error{margin:12px 0 0;padding:9px 12px;border-radius:8px;background:#fdeaea;color:#ad2222;font-size:12.5px;font-weight:600}
        .ssl-field small code{font:600 11.5px 'DM Sans',sans-serif;color:#5b7492;background:#f2f6fb;padding:1px 5px;border-radius:4px}

        .ssl-fields{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px 18px}
        .ssl-field{display:block;min-width:0}
        .ssl-field > span{display:block;margin-bottom:6px;color:#5c7590;font:700 11px 'DM Sans',sans-serif;letter-spacing:.7px;text-transform:uppercase}
        .ssl-field input{display:block;width:100%;box-sizing:border-box;padding:11px 13px;border:1px solid #d8e2ee;border-radius:9px;background:#fff;color:#182b46;font:15px 'DM Sans',sans-serif;transition:border-color .16s ease,box-shadow .16s ease}
        .ssl-field input:hover{border-color:#b9c9db}
        .ssl-field input:focus{border-color:#1f6fe5;outline:0;box-shadow:0 0 0 3px rgba(31,111,229,.15)}
        .ssl-field small{display:block;margin-top:6px;color:#93a2b3;font-size:12px;line-height:1.45}
        .ssl-field input.has-error{border-color:#e0837f;box-shadow:0 0 0 3px rgba(224,131,127,.14)}

        /* ── Feature rows ────────────────────────────────────────────────────
           text | eye | × . The two controls are ICONS ONLY: no labels, no
           helper captions. Everything a screen reader needs is aria-label and
           title, so the row stays one clean line at any width. */
        .ssl-rows{display:flex;flex-direction:column;gap:8px}
        .ssl-row{display:grid;grid-template-columns:minmax(0,1fr) 38px 38px;gap:8px;align-items:center}
        .ssl-row input[type=text]{width:100%;box-sizing:border-box;padding:11px 13px;border:1px solid #d8e2ee;border-radius:9px;background:#fff;color:#182b46;font:14px 'DM Sans',sans-serif;transition:border-color .16s ease,box-shadow .16s ease}
        .ssl-row input[type=text]:focus{border-color:#1f6fe5;outline:0;box-shadow:0 0 0 3px rgba(31,111,229,.15)}
        .ssl-row.is-hidden-feature input[type=text]{color:#a9b7c5;background:#fafcfe;text-decoration:line-through}

        /* Eye toggle: a checkbox laid over the icon so it stays keyboard- and
           screen-reader-friendly without any visible text. */
        .ssl-eye{position:relative;display:block;width:38px;height:38px}
        .ssl-eye input{position:absolute;inset:0;width:100%;height:100%;margin:0;opacity:0;cursor:pointer;z-index:2}
        .ssl-eye svg{position:absolute;inset:0;box-sizing:border-box;width:38px;height:38px;padding:9px;border:1px solid #dfe6ee;border-radius:9px;background:#fff;color:#93a2b3;fill:none;stroke:currentColor;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round;transition:border-color .16s ease,color .16s ease,background .16s ease}
        .ssl-eye:hover svg{border-color:#1f6fe5;color:#1f6fe5}
        .ssl-eye input:focus-visible + svg{box-shadow:0 0 0 3px rgba(31,111,229,.25)}
        /* Shown: green eye. Hidden: grey eye with a slash. The eye-off glyph is
           a second <path> that only appears in the unchecked state. */
        .ssl-eye input:checked + svg{border-color:#bfe0cf;background:#f2fbf6;color:#12805c}
        .ssl-eye input:checked:hover + svg{border-color:#12805c;color:#12805c}
        .ssl-eye .ssl-eye-off{display:inline}
        .ssl-eye input:checked + svg .ssl-eye-off{display:none}
        .ssl-eye .ssl-eye-dot{display:inline}
        .ssl-eye input:checked + svg .ssl-eye-dot{display:inline}

        .ssl-remove{display:grid;place-items:center;width:38px;height:38px;padding:0;border:1px solid #dfe6ee;border-radius:9px;background:#fff;color:#8496a9;font:400 20px/1 'DM Sans',sans-serif;cursor:pointer;transition:border-color .16s ease,color .16s ease,background .16s ease}
        .ssl-remove:hover{border-color:#fca5a5;color:#b91c1c;background:#fff5f5}
        .ssl-remove:focus-visible{outline:0;box-shadow:0 0 0 3px rgba(185,28,28,.2)}

        .ssl-add{display:inline-flex;align-items:center;gap:8px;margin-top:12px;padding:9px 15px;border:1px dashed #c3d3e5;border-radius:9px;background:#fbfdff;color:#1f6fe5;font:700 13px 'DM Sans',sans-serif;cursor:pointer;transition:border-color .16s ease,background .16s ease}
        .ssl-add:hover{border-color:#1f6fe5;background:#f4f9ff}
        .ssl-add:focus-visible{outline:0;box-shadow:0 0 0 3px rgba(31,111,229,.2)}

        /* Compact publish/order row. These two inputs used to live in the
           removed right rail; they stay because a missing is_visible would
           unpublish the plan and a missing sort would zero its position. */
        .ssl-meta{display:grid;grid-template-columns:minmax(0,1fr) 130px;gap:16px 18px;align-items:end}
        .ssl-switch{display:inline-flex;align-items:center;gap:10px;cursor:pointer;padding-bottom:11px}
        .ssl-switch input{position:absolute;opacity:0;width:0;height:0}
        .ssl-switch-track{position:relative;flex:none;width:40px;height:22px;border-radius:999px;background:#d8e2ee;transition:background .18s ease}
        .ssl-switch-track::after{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(15,35,64,.25);content:"";transition:transform .18s ease}
        .ssl-switch input:checked + .ssl-switch-track{background:#1f6fe5}
        .ssl-switch input:checked + .ssl-switch-track::after{transform:translateX(18px)}
        .ssl-switch input:focus-visible + .ssl-switch-track{outline:3px solid rgba(31,111,229,.3);outline-offset:2px}
        .ssl-switch span:last-child{color:#334155;font-size:13.5px;font-weight:600}

        .ssl-save{display:flex;align-items:center;gap:14px;margin-top:20px;flex-wrap:wrap}
        .ssl-save button{display:inline-flex;align-items:center;gap:9px;padding:13px 24px;border:0;border-radius:11px;background:#1f6fe5;color:#fff;font:700 14.5px 'DM Sans',sans-serif;cursor:pointer;box-shadow:0 12px 26px -14px rgba(31,111,229,.85);transition:background .18s ease,box-shadow .18s ease}
        .ssl-save button:hover{background:#175ac4}
        .ssl-save button:focus-visible{outline:0;box-shadow:0 0 0 3px rgba(31,111,229,.35)}
        .ssl-save button b{font-size:16px;line-height:1}
        .ssl-save a{color:#6f8398;font-size:13.5px;font-weight:600;text-decoration:none}
        .ssl-save a:hover{color:#1f6fe5}

        @media(max-width:640px){
            .ssl-edit{padding-left:16px;padding-right:16px}
            .ssl-panel{padding:18px 16px;border-radius:12px}
            .ssl-panel-head{gap:9px;margin-bottom:15px}
            .ssl-fields{grid-template-columns:minmax(0,1fr)}
            /* Keep the two icon columns; only the text input shrinks. */
            .ssl-row{grid-template-columns:minmax(0,1fr) 36px 36px;gap:7px}
            .ssl-eye,.ssl-eye svg,.ssl-remove{width:36px;height:36px}
            .ssl-eye svg{padding:8px}
            .ssl-meta{grid-template-columns:minmax(0,1fr)}
            .ssl-switch{padding-bottom:0}
            .ssl-save button{width:100%;justify-content:center}
        }
        @media(max-width:420px){
            .ssl-row{grid-template-columns:minmax(0,1fr) 34px 34px}
            .ssl-eye,.ssl-eye svg,.ssl-remove{width:34px;height:34px}
            .ssl-eye svg{padding:7px}
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
            <p class="workspace-subtitle" style="margin:0">The price, profit and terms come from ResellerClub and cannot be edited here. You only manage the plan's name, tagline, features and visibility.</p>
        </div>

        <form method="POST" action="{{ $plan ? route('admin.ssl.plans.update', $plan) : route('admin.ssl.plans.store') }}" id="sslForm">@csrf
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

            {{-- 2 ─ ResellerClub link --}}
            <section class="ssl-panel">
                <div class="ssl-panel-head">
                    <span class="ssl-panel-num">2</span>
                    <div><h2>ResellerClub link</h2><p>Required. The price on the card is read from ResellerClub for this plan.</p></div>
                </div>
                <div class="ssl-fields">
                    <label class="ssl-field">
                        <span>ResellerClub plan ID</span>
                        <input type="number" min="1" step="1" name="resellerclub_plan_id" required
                               value="{{ old('resellerclub_plan_id', $plan && $plan->resellerclubPlanId() ? $plan->resellerclubPlanId() : '') }}"
                               placeholder="e.g. 300" class="{{ $errors->has('resellerclub_plan_id') ? 'has-error' : '' }}">
                        <small>
                            The number in the ResellerClub panel URL
                            <code>…/ssl-certificate/sslcert/<b>299</b></code>.
                            Known IDs: <b>299</b> Premium, <b>300</b> Standard,
                            <b>301</b> Wildcard, <b>672</b> EV.
                        </small>
                    </label>
                </div>
                @if($errors->has('resellerclub_plan_id'))
                    <p class="ssl-field-error">{{ $errors->first('resellerclub_plan_id') }}</p>
                @endif
            </section>

            {{-- 3 ─ Features --}}
            <section class="ssl-panel">
                <div class="ssl-panel-head">
                    <span class="ssl-panel-num">3</span>
                    <div><h2>Features</h2><p>Shown as a tick list on the card. Order is top to bottom.</p></div>
                </div>

                <div class="ssl-rows" id="sslRows">
                    @foreach($features as $feature)
                        @php($shown = $feature->isShownToCustomers())
                        <div class="ssl-row{{ $shown ? '' : ' is-hidden-feature' }}">
                            <input type="text" name="features[{{ $loop->index }}][text]" value="{{ $feature->feature }}"
                                   placeholder="Domain Validation" aria-label="Feature {{ $loop->iteration }}">

                            {{-- Icon-only visibility toggle. No visible label: the
                                 aria-label and title carry the meaning. --}}
                            <label class="ssl-eye" title="{{ $shown ? 'Visible to customers' : 'Hidden from customers' }}">
                                {{-- Sentinel: proves this row HAS a toggle, so an
                                     unchecked box can be told apart from a row
                                     that was saved without one. --}}
                                <input type="hidden" name="features[{{ $loop->index }}][shown_marker]" value="1">
                                <input type="checkbox" name="features[{{ $loop->index }}][shown]" value="1" class="ssl-shown"
                                       aria-label="{{ $shown ? 'Hide this feature from customers' : 'Show this feature to customers' }}"
                                       @if($shown) checked @endif>
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M2.2 12S5.9 5.7 12 5.7 21.8 12 21.8 12 18.1 18.3 12 18.3 2.2 12 2.2 12Z"/>
                                    <circle class="ssl-eye-dot" cx="12" cy="12" r="3.1"/>
                                    <line class="ssl-eye-off" x1="4" y1="20" x2="20" y2="4"/>
                                </svg>
                            </label>

                            <button type="button" class="ssl-remove" aria-label="Delete this feature" title="Delete">×</button>
                        </div>
                    @endforeach
                </div>

                <button type="button" class="ssl-add" id="sslAdd"><span aria-hidden="true">+</span> Add feature</button>
            </section>

            {{-- 4 ─ Publishing --}}
            <section class="ssl-panel">
                <div class="ssl-panel-head">
                    <span class="ssl-panel-num">4</span>
                    <div><h2>Publishing</h2><p>Whether the card appears, and where it sits in the list.</p></div>
                </div>
                <div class="ssl-meta">
                    <label class="ssl-switch">
                        <input type="checkbox" name="is_visible" value="1" id="isVisible" {{ (!$plan || $plan->is_visible) ? 'checked' : '' }}>
                        <span class="ssl-switch-track" aria-hidden="true"></span>
                        <span>Show this plan</span>
                    </label>
                    <label class="ssl-field">
                        <span>Order</span>
                        <input type="number" name="sort" value="{{ old('sort', $plan->sort ?? 1) }}" min="0" max="999">
                    </label>
                </div>
            </section>

            <div class="ssl-save">
                <button type="submit">{{ $plan ? 'Save changes' : 'Create plan' }} <b>→</b></button>
                <a href="{{ route('admin.ssl.plans') }}">Cancel</a>
            </div>
        </form>
    </div>
</main>
<script>
    (function () {
        var rows = document.getElementById('sslRows');
        var add = document.getElementById('sslAdd');
        var index = {{ $features->count() }};

        /* Mirror the server state on the row: a hidden feature is struck
           through and its eye loses the green tint (.is-hidden-feature). */
        function paint(row) {
            var toggle = row.querySelector('.ssl-shown');
            if (!toggle) { return; }

            row.classList.toggle('is-hidden-feature', !toggle.checked);

            var label = toggle.closest('.ssl-eye');
            if (label) {
                label.title = toggle.checked ? 'Visible to customers' : 'Hidden from customers';
            }
            toggle.setAttribute(
                'aria-label',
                toggle.checked ? 'Hide this feature from customers' : 'Show this feature to customers'
            );
        }

        function wire(row) {
            var remove = row.querySelector('.ssl-remove');
            if (remove) {
                remove.addEventListener('click', function () { row.remove(); });
            }

            var toggle = row.querySelector('.ssl-shown');
            if (toggle) {
                toggle.addEventListener('change', function () { paint(row); });
                paint(row);
            }
        }

        [].slice.call(document.querySelectorAll('#sslRows .ssl-row')).forEach(wire);

        if (add && rows) {
            add.addEventListener('click', function () {
                var row = document.createElement('div');
                row.className = 'ssl-row';
                row.innerHTML = '<input type="text" name="features[' + index + '][text]" placeholder="Domain Validation" aria-label="Feature">'
                    + '<label class="ssl-eye" title="Visible to customers">'
                    + '<input type="hidden" name="features[' + index + '][shown_marker]" value="1">'
                    + '<input type="checkbox" name="features[' + index + '][shown]" value="1" class="ssl-shown" aria-label="Hide this feature from customers" checked>'
                    + '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
                    + '<path d="M2.2 12S5.9 5.7 12 5.7 21.8 12 21.8 12 18.1 18.3 12 18.3 2.2 12 2.2 12Z"/>'
                    + '<circle class="ssl-eye-dot" cx="12" cy="12" r="3.1"/>'
                    + '<line class="ssl-eye-off" x1="4" y1="20" x2="20" y2="4"/>'
                    + '</svg>'
                    + '</label>'
                    + '<button type="button" class="ssl-remove" aria-label="Delete this feature" title="Delete">×</button>';

                rows.appendChild(row);
                wire(row);
                row.querySelector('input[type=text]').focus();
                index++;
            });
        }
    }());
</script>
</body>
</html>
