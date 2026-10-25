<!doctype html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Hosting plan'])
    <link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}">
    <style>
        .hp-form-panel { margin-top:18px; }
        .hp-form-panel h2 { margin:0 0 14px; }
        .hp-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:14px 20px; }
        .hp-grid label { font-size:12px; font-weight:700; color:#77879a; text-transform:uppercase; letter-spacing:.06em; }
        .hp-grid input[type=text], .hp-grid input[type=number], .hp-grid select { display:block; width:100%; box-sizing:border-box; margin-top:5px; padding:11px 13px; border:1px solid #dfe6ee; border-radius:7px; font:15px 'DM Sans',sans-serif; }
        .hp-checks { display:flex; gap:18px; flex-wrap:wrap; margin-top:10px; }
        .hp-checks label { display:inline-flex; align-items:center; gap:8px; font-size:14px; font-weight:700; color:#344256; }
        .hp-rows { display:flex; flex-direction:column; gap:9px; margin-top:8px; }
        .hp-group-title { margin:18px 0 4px; font-size:14px; font-weight:800; color:#1f2937; }
        .hp-group-title:first-of-type { margin-top:0; }
        .hp-row-head { display:grid; grid-template-columns:minmax(300px,1fr) 150px 118px; gap:7px; padding:0 12px 2px; align-items:end; }
        .hp-row-head span { font-size:10px; font-weight:800; letter-spacing:.7px; text-transform:uppercase; color:#77879a; }
        .hp-row-head span:last-child { visibility:hidden; }
        .hp-row { display:grid; grid-template-columns:minmax(300px,1fr) 150px 118px; gap:7px; align-items:start; background:#f8fafc; border:1px solid #e5ebf3; border-radius:9px; padding:10px 12px; }
        .hp-line-cell { position:relative; }
        .hp-preview { position:absolute; top:100%; left:0; padding:3px 2px 0; font-size:11px; color:#8190a1; white-space:nowrap; pointer-events:none; }
        .hp-preview b { color:#0f172a; font-weight:800; }
        .hp-row input[type=text] { box-sizing:border-box; width:100%; padding:9px 11px; border:1px solid #dfe6ee; border-radius:6px; font:14px 'DM Sans',sans-serif; }
        .hp-row .hp-avail { display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:800; color:#166534; white-space:nowrap; padding:9px 0; }
        .hp-row .hp-avail input:not(:checked) ~ .hp-avail-text { color:#b91c1c; }
        .hp-row .hp-btn-group { display:flex; gap:4px; }
        .hp-row .hp-btn-group button { padding:8px 10px; border:1px solid #dfe6ee; border-radius:6px; background:#fff; cursor:pointer; font-size:13px; }
        .hp-row .hp-del:hover { border-color:#fca5a5; color:#b91c1c; background:#fff5f5; }
        .hp-addrow { display:flex; gap:8px; margin-top:14px; }
        .hp-addrow button { padding:10px 18px; border:1px solid #dfe6ee; border-radius:7px; background:#fff; color:#1d4ed8; font-weight:700; font-size:14px; cursor:pointer; }
        .hp-addrow button:hover { background:#f7faff; border-color:#93b4e7; }
        .hp-form-note { margin:10px 2px 0; color:#8190a1; font-size:13px; }
        .hp-save { position:sticky; bottom:0; padding:16px 0; background:linear-gradient(#f7f9fc00,#f7f9fc 40%); display:flex; gap:10px; align-items:center; }
        @media(max-width:900px){ .hp-row { grid-template-columns:1fr 1fr; } .hp-row-head { display:none; } }
    </style>
</head>
<body class="domain-app admin-app">
    @include('partials.admin-sidebar', ['active' => 'hosting-plans'])
<main class="domain-main">
    <header class="domain-topbar"><div><span class="crumb">CMS /</span><strong>Hosting plans</strong></div><a class="cart-badge" href="{{ route('admin.hosting.plans', ['type' => $type, 'term' => $returnTerm ?? 'monthly']) }}">↑ Back to list</a></header>
    <div class="domain-content standalone-content">
        <span class="domain-kicker">{{ $plan ? 'EDIT PLAN CARD' : 'ADD PLAN CARD' }}</span>
        <h1 class="workspace-title">{{ $plan ? 'Edit ' . ($termName !== '' ? $termName : $plan->name) : 'Add a ' . ucfirst($type) . ' plan card' }}.</h1>
        <p class="workspace-subtitle">Everything here renders on the public hosting page and in the client area. Unavailable features show the red cross &times;; available ones show the check icon.</p>
        @if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif

        <form method="POST" action="{{ $plan ? route('admin.hosting.plans.update', $plan) : route('admin.hosting.plans.store') }}">@csrf
            <input type="hidden" name="return_term" value="{{ $returnTerm ?? 'monthly' }}">
            <section class="store-panel hp-form-panel">
                <h2>Card</h2>
                <div class="hp-grid">
                    <label>Hosting type
                        <input type="hidden" name="type" value="{{ $plan ? $plan->type : $type }}">
                        <input type="text" value="{{ ucfirst($plan ? $plan->type : $type) }}" disabled>
                    </label>
                    <label>Plan name &mdash; {{ $termMeta['label'] }}
                        <input type="text" name="name" value="{{ old('name', $plan ? $termName : '') }}" placeholder="Plan A" required>
                    </label>
                    <label>Circle style
                        <select name="style_class">
                            @foreach(\App\Models\HostingPlan::STYLE_CLASSES as $classValue => $classLabel)
                            <option value="{{ $classValue }}" {!! ($plan->style_class ?? 'plan-circle-basic') === $classValue ? 'selected' : '' !!}>{{ $classLabel }} ({{ $classValue }})</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Badge <span style="text-transform:none">(Cheapest / Most popular)</span>
                        <input type="text" name="badge" value="{{ old('badge', $plan->badge ?? '') }}" placeholder="leave empty for no badge">
                    </label>
                    <label>Sort order
                        <input type="number" name="sort" value="{{ old('sort', $plan->sort ?? 0) }}" min="0" max="999" required>
                    </label>
                    <label>Description
                        <input type="text" name="description" value="{{ old('description', $plan->description ?? '') }}" placeholder="For small websites.">
                    </label>
                </div>
@if($plan && $termMeta['slug'] === 'monthly')
                <p class="hp-form-note">Monthly is this card&rsquo;s main name &mdash; every billing term without its own name follows it. Rename a term separately from its own Edit page.</p>
@elseif($plan && $nameInherited)
                <p class="hp-form-note">This card has no separate <strong>{{ strtolower($termMeta['label']) }}</strong> name yet, so it shows the Monthly name. Saving below gives {{ strtolower($termMeta['label']) }} its own name &mdash; Monthly and other terms stay untouched.</p>
@elseif($plan)
                <p class="hp-form-note">Saving below renames <strong>only this card&rsquo;s {{ strtolower($termMeta['label']) }}</strong> view &mdash; Monthly and every other term keep their own name.</p>
@else
                <p class="hp-form-note">A new card shows this name on every billing term until a term gets its own name from its Edit page.</p>
@endif
                <div class="hp-checks">
                    <label><input type="checkbox" name="{{ $termMeta['visibility'] }}" value="1" {!! ($plan === null || $plan->{$termMeta['visibility']}) ? 'checked' : '' !!}> Card is visible for {{ $termMeta['label'] }}</label>
                </div>
            </section>

            <section class="store-panel hp-form-panel">
                <h2>{{ $termMeta['label'] }} price &amp; billing</h2>
                @php $flagDefault = $plan ? $plan->{$termMeta['flag']} : in_array($termMeta['flag'], ['term_monthly_active', 'term_yearly_active']); @endphp
                <div class="hp-grid">
                    <label>{{ $termMeta['label'] }} price (৳)
                        <input type="number" step="1" min="0" name="{{ $termMeta['column'] }}" value="{{ old($termMeta['column'], $plan && $plan->{$termMeta['column']} !== null ? $plan->{$termMeta['column']} : '') }}" placeholder="&mdash;">
                    </label>
                </div>
                <div class="hp-checks">
                    <label><input type="checkbox" name="{{ $termMeta['flag'] }}" value="1" {!! $flagDefault ? 'checked' : '' !!}> {{ $termMeta['label'] }} active</label>
                </div>
                <p class="hp-form-note">Only this billing term ({{ strtolower($termMeta['label']) }}) is saved here. Untick to show &ldquo;Coming soon&rdquo; on this card&rsquo;s {{ strtolower($termMeta['label']) }} price &amp; button. Every other term is edited from its own section on the list page.</p>
            </section>

            <section class="store-panel hp-form-panel">
                <h2>Features &mdash; {{ $termMeta['label'] }}</h2>
                <input type="hidden" name="features_term" value="{{ $termMeta['slug'] }}">
@if(!$plan)
                <p class="hp-form-note">These feature rows are saved as this new card&rsquo;s <strong>{{ strtolower($termMeta['label']) }}</strong> features (the other billing terms show the Monthly rows until you edit them).</p>
@elseif($featuresInherited && $termMeta['slug'] !== 'monthly')
                <p class="hp-form-note">This card has no separate <strong>{{ strtolower($termMeta['label']) }}</strong> features yet, so it shows the Monthly rows. Saving below gives {{ strtolower($termMeta['label']) }} its own feature list &mdash; Monthly stays untouched.</p>
@else
                <p class="hp-form-note">Saving below replaces <strong>only this card&rsquo;s {{ strtolower($termMeta['label']) }}</strong> feature rows.</p>
@endif
@php
    $hpIndex = 0;
    $groups = ['primary' => 'Core resources', 'extra' => 'Extra features'];
@endphp
@foreach($groups as $groupKey => $groupLabel)
                <h3 class="hp-group-title">{{ $groupLabel }}</h3>
                <div class="hp-row-head">
                    <span>Feature line &mdash; value + name (e.g. 20 GB NVMe storage)</span>
                    <span>Shown as</span>
                    <span></span>
                </div>
                <div class="hp-rows" data-group="{{ $groupKey }}">
@foreach($featureRows->where('group', $groupKey) as $feature)
@php
    $i = $hpIndex++;
@endphp
                    <div class="hp-row">
                        <div class="hp-line-cell">
                            <input type="text" name="features[{{ $i }}][line]" value="{{ $feature['line'] }}" placeholder="e.g. 20 GB NVMe storage">
                            <div class="hp-preview"></div>
                        </div>
                        <label class="hp-avail"><input type="checkbox" name="features[{{ $i }}][is_available]" value="1" {!! $feature['is_available'] ? 'checked' : '' !!}> <span class="hp-avail-text">Available</span></label>
                        <div class="hp-btn-group"><button type="button" class="hp-move" data-dir="-1" title="Move up">↑</button><button type="button" class="hp-move" data-dir="1" title="Move down">↓</button><button type="button" class="hp-del" title="Remove row">✕</button></div>
                        <input type="hidden" name="features[{{ $i }}][group]" value="{{ $groupKey }}">
                        <input type="hidden" name="features[{{ $i }}][feature_key]" value="{{ $feature['feature_key'] }}">
                    </div>
@endforeach
                </div>
                <div class="hp-addrow"><button type="button" class="hp-add-row" data-group="{{ $groupKey }}">+ Add row</button></div>
@endforeach
                <p class="hp-form-note">Core resources render under &ldquo;Core resources&rdquo;; extras appear under &ldquo;Account &amp; databases&rdquo;, and unticked (crossed) extras move under &ldquo;Advanced &amp; security&rdquo; on the public page. Up/down reorders within the group.</p>
            </section>

            <div class="hp-save">
                <button class="checkout-submit" type="submit">{{ $plan ? 'Save plan card' : 'Create plan card' }} <b>→</b></button>
                <a href="{{ route('admin.hosting.plans', ['type' => $type, 'term' => $returnTerm ?? 'monthly']) }}" style="font-size:12px;color:#8190a1;font-weight:700;text-decoration:none">Cancel</a>
            </div>
        </form>
    </div>
</main>
<script>
(function () {
    var firstWrap = document.querySelector('.hp-rows');
    if (!firstWrap) return;
    var idx = document.querySelectorAll('.hp-row').length;

    // Mirrors the server-side split in HostingPlanService::leadAndRest():
    // "20 GB NVMe storage" -> bold "20 GB", regular "NVMe storage".
    function updatePreview(input) {
        var preview = input.closest('.hp-line-cell').querySelector('.hp-preview');
        if (!preview) return;
        var line = input.value.trim();
        var match = line.match(/^(\d[\d.,]*(?:\s*(?:GB|MB|TB|Mbps|Core|Cores))?)(?:\s+(.*))?$/i);
        if (!line) { preview.innerHTML = ''; return; }
        if (match && match[2]) {
            preview.innerHTML = 'renders as: <b>' + match[1] + '</b> ' + match[2];
        } else {
            preview.innerHTML = 'renders as: <b>' + line + '</b>';
        }
    }

    function wireRow(row) {
        row.querySelectorAll('.hp-line-cell input[type=text]').forEach(function (input) {
            input.addEventListener('input', function () { updatePreview(input); });
            input.addEventListener('focus', function () { updatePreview(input); });
            input.addEventListener('blur', function () {
                input.closest('.hp-line-cell').querySelector('.hp-preview').innerHTML = '';
            });
        });
        var avail = row.querySelector('.hp-avail input');
        if (avail) {
            avail.addEventListener('change', function () {
                row.querySelector('.hp-avail-text').textContent = avail.checked ? 'Available' : 'Crossed';
            });
        }
    }

    document.querySelectorAll('.hp-row').forEach(wireRow);

    // Each group ("Core resources" / "Extra features") gets its own rows —
    // a new row is appended to the group whose + Add row was clicked.
    function newRow(group, wrap) {
        var div = document.createElement('div');
        div.className = 'hp-row';
        div.innerHTML =
            '<div class="hp-line-cell">' +
            '<input type="text" name="features[' + idx + '][line]" placeholder="e.g. 20 GB NVMe storage">' +
            '<div class="hp-preview"></div></div>' +
            '<label class="hp-avail"><input type="checkbox" name="features[' + idx + '][is_available]" value="1" checked> <span class="hp-avail-text">Available</span></label>' +
            '<div class="hp-btn-group"><button type="button" class="hp-move" data-dir="-1" title="Move up">\u2191</button><button type="button" class="hp-move" data-dir="1" title="Move down">\u2193</button><button type="button" class="hp-del" title="Remove row">\u2715</button></div>' +
            '<input type="hidden" name="features[' + idx + '][group]" value="' + group + '">' +
            '<input type="hidden" name="features[' + idx + '][feature_key]" value="">';
        wrap.appendChild(div);
        wireRow(div);
        idx++;
    }

    document.querySelectorAll('.hp-add-row').forEach(function (button) {
        button.addEventListener('click', function () {
            var group = button.dataset.group;
            var wrap = button.closest('.hp-form-panel').querySelector('.hp-rows[data-group="' + group + '"]');
            if (wrap) newRow(group, wrap);
        });
    });

    firstWrap.closest('.hp-form-panel').addEventListener('click', function (event) {
        var button = event.target.closest('button');
        if (!button) return;
        if (button.classList.contains('hp-add-row')) return; // handled above
        var row = button.closest('.hp-row');
        if (!row) return;
        if (button.classList.contains('hp-del')) {
            row.remove();
            return;
        }
        if (button.classList.contains('hp-move')) {
            // Up/down stays inside the row's own group container.
            if (button.dataset.dir === '-1' && row.previousElementSibling) {
                row.parentNode.insertBefore(row, row.previousElementSibling);
            } else if (button.dataset.dir === '1' && row.nextElementSibling) {
                row.parentNode.insertBefore(row.nextElementSibling, row);
            }
        }
    });
})();
</script>
</body>
</html>
