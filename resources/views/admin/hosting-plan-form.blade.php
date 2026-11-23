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
        .hp-term-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:14px; }
        .hp-term-box { padding:14px; border:1px solid #e5ebf3; border-radius:10px; background:#f8fafc; }
        .hp-term-box h3 { margin:0 0 8px; font-size:14px; color:#1f2937; }
        .hp-term-box label { display:block; margin-top:8px; font-size:11px; font-weight:700; color:#77879a; text-transform:uppercase; letter-spacing:.05em; }
        .hp-term-box input, .hp-term-box select { display:block; box-sizing:border-box; width:100%; margin-top:4px; padding:9px 11px; border:1px solid #dfe6ee; border-radius:6px; font:14px 'DM Sans',sans-serif; background:#fff; }
        .hp-ftabs { display:flex; gap:6px; flex-wrap:wrap; margin:6px 0 14px; }
        .hp-ftab { padding:8px 16px; border:1px solid #dfe6ee; border-radius:999px; background:#fff; color:#475569; font:700 13px 'DM Sans',sans-serif; cursor:pointer; }
        .hp-ftab.is-active { background:#1d4ed8; border-color:#1d4ed8; color:#fff; }
        .hp-pane[hidden] { display:none; }
        .hp-same { display:flex; align-items:center; gap:8px; margin:0 0 12px; padding:10px 12px; border-radius:8px; background:#f0f6ff; color:#1e3a8a; font-size:13px; font-weight:700; }
        .hp-pane.is-same .hp-pane-body { opacity:.4; pointer-events:none; }
        details.hp-names { margin-top:14px; }
        details.hp-names summary { cursor:pointer; color:#1d4ed8; font-weight:700; font-size:13px; }
    </style>
</head>
<body class="domain-app admin-app">
    @include('partials.admin-sidebar', ['active' => 'hosting-plans'])
<main class="domain-main">
    @php
        $sectionLabel = $region === 'wordpress' ? 'WordPress' : (\App\Models\HostingPlan::REGION_SHORT[$region] . ' ' . \App\Models\HostingPlan::TYPE_LABELS[$type]);
        $listUrl = route('admin.hosting.plans', ['region' => $region, 'type' => $type]);
    @endphp
    <header class="domain-topbar"><div><span class="crumb">CMS /</span><strong>Hosting plans</strong></div><a class="cart-badge" href="{{ $listUrl }}">&uarr; Back to list</a></header>
    <div class="domain-content standalone-content">
        <span class="domain-kicker">{{ $plan ? 'EDIT CARD' : 'ADD CARD' }} &middot; {{ strtoupper($sectionLabel) }}</span>
        <h1 class="workspace-title">{{ $plan ? 'Edit ' . $plan->name : 'Add a ' . $sectionLabel . ' card' }}.</h1>
        <p class="workspace-subtitle">What you save here appears on the public hosting page and in the client area. Unavailable features show a red cross.</p>
        @if($errors->any())<div class="domain-alert error">{{ $errors->first() }}</div>@endif

        <form method="POST" action="{{ $plan ? route('admin.hosting.plans.update', $plan) : route('admin.hosting.plans.store') }}">@csrf
            <input type="hidden" name="region" value="{{ $region }}">
            <input type="hidden" name="type" value="{{ $type }}">

            <section class="store-panel hp-form-panel">
                <h2>1. Card details</h2>
                <div class="hp-grid">
                    <label>Section
                        <input type="text" value="{{ $sectionLabel }}" disabled>
                    </label>
                    <label>Plan name
                        <input type="text" name="name" value="{{ old('name', $plan->name ?? '') }}" placeholder="Starter" required>
                    </label>
                    <label>Circle style
                        <select name="style_class">
                            @foreach(\App\Models\HostingPlan::STYLE_CLASSES as $classValue => $classLabel)
                            <option value="{{ $classValue }}" {!! old('style_class', $plan->style_class ?? 'plan-circle-basic') === $classValue ? 'selected' : '' !!}>{{ $classLabel }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Badge <span style="text-transform:none">(e.g. Cheapest, Most popular)</span>
                        <input type="text" name="badge" value="{{ old('badge', $plan->badge ?? '') }}" placeholder="leave empty for no badge">
                    </label>
                    <label>Sort order
                        <input type="number" name="sort" value="{{ old('sort', $plan->sort ?? 0) }}" min="0" max="999" required>
                    </label>
                    <label>Short description
                        <input type="text" name="description" value="{{ old('description', $plan->description ?? '') }}" placeholder="For small websites.">
                    </label>
                </div>
                <div class="hp-checks">
                    <label><input type="checkbox" name="is_visible" value="1" {!! old('is_visible', $plan ? $plan->is_visible : true) ? 'checked' : '' !!}> Show this card on the website</label>
                </div>
                <details class="hp-names">
                    <summary>Different name for a billing period (optional)</summary>
                    <div class="hp-grid" style="margin-top:10px">
                        @foreach($terms as $termKey => $termMeta)
                        @if($termMeta['name'])
                        <label>{{ $termMeta['label'] }} name
                            <input type="text" name="names[{{ $termKey }}]" value="{{ old('names.' . $termKey, $plan->{$termMeta['name']} ?? '') }}" placeholder="same as plan name">
                        </label>
                        @endif
                        @endforeach
                    </div>
                </details>
            </section>

            <section class="store-panel hp-form-panel">
                <h2>2. Prices &amp; status</h2>
                <div class="hp-term-grid">
                    @foreach($terms as $termKey => $termMeta)
                    <div class="hp-term-box">
                        <h3>{{ $termMeta['label'] }}</h3>
                        <label>Price (&#2547;)
                            <input type="number" step="0.01" min="0" name="price[{{ $termKey }}]" value="{{ old('price.' . $termKey, $plan && $plan->{$termMeta['column']} !== null ? rtrim(rtrim(number_format((float) $plan->{$termMeta['column']}, 2, '.', ''), '0'), '.') : '') }}" placeholder="&mdash;">
                        </label>
                        <label>Status
                            <select name="status[{{ $termKey }}]">
                                @foreach(['on' => 'On sale', 'soon' => 'Coming soon', 'hidden' => 'Hidden'] as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}" {!! old('status.' . $termKey, $statuses[$termKey]) === $statusValue ? 'selected' : '' !!}>{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    @endforeach
                </div>
                <p class="hp-form-note">On sale = customers can order. Coming soon = the tab shows &ldquo;Coming soon&rdquo;. Hidden = this billing period is not shown for this card. A card with no price cannot be On sale.</p>
            </section>

            <section class="store-panel hp-form-panel">
                <h2>3. Features</h2>
                <p class="hp-form-note" style="margin:0 0 10px">Each billing period can have its own list. Tick &ldquo;Same as Monthly&rdquo; to reuse the Monthly list.</p>
                <div class="hp-ftabs">
                    @foreach($terms as $termKey => $termMeta)
                    <button type="button" class="hp-ftab{{ $loop->first ? ' is-active' : '' }}" data-pane="{{ $termKey }}">{{ $termMeta['label'] }}</button>
                    @endforeach
                </div>

                @foreach($terms as $termKey => $termMeta)
                @php $oldSame = old('same.' . $termKey, $same[$termKey]); @endphp
                <div class="hp-pane{{ $oldSame ? ' is-same' : '' }}" data-pane="{{ $termKey }}" {!! $loop->first ? '' : 'hidden' !!}>
                    @if($termKey !== 'monthly')
                    <label class="hp-same"><input type="checkbox" class="hp-same-box" name="same[{{ $termKey }}]" value="1" {{ $oldSame ? 'checked' : '' }}> Same as Monthly ({{ $termMeta['label'] }} uses the Monthly feature list)</label>
                    @endif
                    <div class="hp-pane-body">
                    @php $hpIndex = 0; @endphp
                    @foreach(['primary' => 'Core resources', 'extra' => 'Extra features'] as $groupKey => $groupLabel)
                        <h3 class="hp-group-title">{{ $groupLabel }}</h3>
                        <div class="hp-row-head"><span>Feature line (e.g. 20 GB NVMe storage)</span><span>Shown as</span><span></span></div>
                        <div class="hp-rows" data-group="{{ $groupKey }}">
                        @foreach(collect($featureRows[$termKey])->where('group', $groupKey) as $feature)
                            @php $i = $hpIndex++; @endphp
                            <div class="hp-row">
                                <div class="hp-line-cell">
                                    <input type="text" name="features[{{ $termKey }}][{{ $i }}][line]" value="{{ $feature['line'] }}" placeholder="e.g. 20 GB NVMe storage">
                                    <div class="hp-preview"></div>
                                </div>
                                <label class="hp-avail"><input type="checkbox" name="features[{{ $termKey }}][{{ $i }}][is_available]" value="1" {!! $feature['is_available'] ? 'checked' : '' !!}> <span class="hp-avail-text">{{ $feature['is_available'] ? 'Available' : 'Crossed' }}</span></label>
                                <div class="hp-btn-group"><button type="button" class="hp-move" data-dir="-1" title="Move up">&uarr;</button><button type="button" class="hp-move" data-dir="1" title="Move down">&darr;</button><button type="button" class="hp-del" title="Remove">&times;</button></div>
                                <input type="hidden" name="features[{{ $termKey }}][{{ $i }}][group]" value="{{ $groupKey }}">
                                <input type="hidden" name="features[{{ $termKey }}][{{ $i }}][feature_key]" value="{{ $feature['feature_key'] }}">
                            </div>
                        @endforeach
                        </div>
                        <div class="hp-addrow"><button type="button" class="hp-add-row" data-group="{{ $groupKey }}" data-next="{{ $hpIndex }}">+ Add row</button></div>
                    @endforeach
                    </div>
                </div>
                @endforeach
            </section>

            <div class="hp-save">
                <button class="checkout-submit" type="submit">{{ $plan ? 'Save card' : 'Create card' }} <b>&rarr;</b></button>
                <a href="{{ $listUrl }}" style="font-size:12px;color:#8190a1;font-weight:700;text-decoration:none">Cancel</a>
            </div>
        </form>
    </div>
</main>
<script>
(function () {
    var rowIndex = {};

    function updatePreview(input) {
        var preview = input.closest('.hp-line-cell').querySelector('.hp-preview');
        var line = input.value.trim();
        if (!line) { preview.textContent = ''; return; }
        var match = line.match(/^(\d[\d.,]*(?:\s*(?:GB|MB|TB|Mbps|Core|Cores))?)(?:\s+(.*))?$/i);
        preview.textContent = '';
        preview.appendChild(document.createTextNode('renders as: '));
        var bold = document.createElement('b');
        if (match && match[2]) {
            bold.textContent = match[1];
            preview.appendChild(bold);
            preview.appendChild(document.createTextNode(' ' + match[2]));
        } else {
            bold.textContent = line;
            preview.appendChild(bold);
        }
    }

    function wireRow(row) {
        var input = row.querySelector('.hp-line-cell input[type=text]');
        if (input) {
            input.addEventListener('input', function () { updatePreview(input); });
            input.addEventListener('focus', function () { updatePreview(input); });
            input.addEventListener('blur', function () { row.querySelector('.hp-preview').textContent = ''; });
        }
        var avail = row.querySelector('.hp-avail input');
        if (avail) {
            avail.addEventListener('change', function () {
                row.querySelector('.hp-avail-text').textContent = avail.checked ? 'Available' : 'Crossed';
            });
        }
    }

    function el(tag, attrs) {
        var node = document.createElement(tag);
        Object.keys(attrs || {}).forEach(function (k) { node.setAttribute(k, attrs[k]); });
        return node;
    }

    function newRow(pane, group, wrap) {
        var term = pane.dataset.pane;
        var key = term;
        rowIndex[key] = (rowIndex[key] || 0);
        var n = 1000 + rowIndex[key]++;
        var base = 'features[' + term + '][' + n + ']';
        var row = el('div', {'class': 'hp-row'});
        var cell = el('div', {'class': 'hp-line-cell'});
        cell.appendChild(el('input', {type: 'text', name: base + '[line]', placeholder: 'e.g. 20 GB NVMe storage'}));
        cell.appendChild(el('div', {'class': 'hp-preview'}));
        var label = el('label', {'class': 'hp-avail'});
        var box = el('input', {type: 'checkbox', name: base + '[is_available]', value: '1'});
        box.checked = true;
        var txt = el('span', {'class': 'hp-avail-text'});
        txt.textContent = 'Available';
        label.appendChild(box);
        label.appendChild(document.createTextNode(' '));
        label.appendChild(txt);
        var btns = el('div', {'class': 'hp-btn-group'});
        [['hp-move', '-1', '\u2191'], ['hp-move', '1', '\u2193'], ['hp-del', '', '\u00d7']].forEach(function (b) {
            var button = el('button', {type: 'button', 'class': b[0]});
            if (b[1]) { button.dataset.dir = b[1]; }
            button.textContent = b[2];
            btns.appendChild(button);
        });
        row.appendChild(cell);
        row.appendChild(label);
        row.appendChild(btns);
        row.appendChild(el('input', {type: 'hidden', name: base + '[group]', value: group}));
        row.appendChild(el('input', {type: 'hidden', name: base + '[feature_key]', value: ''}));
        wrap.appendChild(row);
        wireRow(row);
    }

    document.querySelectorAll('.hp-row').forEach(wireRow);

    document.querySelectorAll('.hp-ftab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.hp-ftab').forEach(function (t) { t.classList.toggle('is-active', t === tab); });
            document.querySelectorAll('.hp-pane').forEach(function (p) { p.hidden = p.dataset.pane !== tab.dataset.pane; });
        });
    });

    document.querySelectorAll('.hp-same-box').forEach(function (box) {
        box.addEventListener('change', function () { box.closest('.hp-pane').classList.toggle('is-same', box.checked); });
    });

    document.querySelectorAll('.hp-pane').forEach(function (pane) {
        pane.addEventListener('click', function (event) {
            var button = event.target.closest('button');
            if (!button) return;
            if (button.classList.contains('hp-add-row')) {
                var wrap = pane.querySelector('.hp-rows[data-group="' + button.dataset.group + '"]');
                if (wrap) newRow(pane, button.dataset.group, wrap);
                return;
            }
            var row = button.closest('.hp-row');
            if (!row) return;
            if (button.classList.contains('hp-del')) { row.remove(); return; }
            if (button.classList.contains('hp-move')) {
                if (button.dataset.dir === '-1' && row.previousElementSibling) {
                    row.parentNode.insertBefore(row, row.previousElementSibling);
                } else if (button.dataset.dir === '1' && row.nextElementSibling) {
                    row.parentNode.insertBefore(row.nextElementSibling, row);
                }
            }
        });
    });
})();
</script>
</body>
</html>
