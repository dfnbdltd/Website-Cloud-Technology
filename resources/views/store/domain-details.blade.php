@extends('store.layout', ['pageTitle' => 'Manage ' . $item->domain, 'navActive' => 'my-domains', 'topbarTitle' => 'My Store', 'hideStoreAlerts' => true])

@section('store')
    @php
        $oldNameservers = old('nameservers');
        if (is_string($oldNameservers)) {
            $oldNameservers = preg_split('/\r\n|\r|\n/', $oldNameservers);
        }
        $oldNameservers = is_array($oldNameservers)
            ? array_values(array_filter(array_map('trim', $oldNameservers), function ($value) { return $value !== ''; }))
            : [];
        $editNameservers = $oldNameservers ?: ($nameservers ?: ['ns1.example.com', 'ns2.example.com']);
    @endphp
    <div class="store-domain-detail-head">
        <div>
            <a class="store-back" href="{{ route('mystore.domains') }}">← My Domains</a>
            <span class="domain-kicker">DOMAIN MANAGEMENT</span>
            <h1 class="workspace-title">{{ $item->domain }}</h1>
            <p class="workspace-subtitle">Manage nameservers and review the latest activity for this domain.</p>
        </div>
        <div class="store-domain-detail-status">
            <span class="table-status paid">Active</span>
            <a class="store-ghost-link" href="{{ route('mystore.domains.activity', $item->id) }}">View All Activity</a>
        </div>
    </div>

    <div class="store-domain-detail-grid">
        <main>
            <section class="store-detail-panel">
                <div class="store-detail-panel-head">
                    <div>
                        <span class="domain-kicker">DOMAIN REGISTRATION</span>
                        <h2>{{ $item->domain }}</h2>
                        <p>Order {{ $order->reference }} · {{ max(1, (int) $item->years) }} year registration</p>
                    </div>
                    @if($invoice)
                        <a class="store-ghost-link" href="{{ route('invoices.view', $invoice) }}">View Invoice</a>
                    @endif
                </div>
                <div class="store-detail-facts">
                    <div><small>REGISTERED</small><strong>{{ optional($order->created_at)->format('d M Y') ?: '—' }}</strong></div>
                    <div><small>EXPIRES</small><strong>{{ $order->created_at ? $order->created_at->copy()->addYears(max(1, (int) $item->years))->format('d M Y') : '—' }}</strong></div>
                    <div><small>RENEWAL PRICE</small><strong>৳ {{ number_format((float) $item->renewal_price, 2) }}<i>/year</i></strong></div>
                    <div><small>PAID</small><strong>৳ {{ number_format((float) $item->price * max(1, (int) $item->years), 2) }}</strong></div>
                </div>
            </section>

            <section class="store-detail-panel store-ns-panel">
                <div class="store-ns-row">
                    <span class="store-detail-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="6.5" rx="2"></rect><rect x="3" y="13.5" width="18" height="6.5" rx="2"></rect><path d="M7 7.25h.01"></path><path d="M7 16.75h.01"></path><path d="M11 7.25h4"></path><path d="M11 16.75h4"></path></svg>
                    </span>
                    <div class="store-ns-heading">
                        <h2>NAMESERVERS &amp; DNS</h2>
                        <p class="store-ns-subtitle">Choose where this domain points on the internet.</p>
                        <div class="store-ns-actions">
                            <details class="store-ns-edit @if($errors->any() || old('nameservers')) is-editing @endif" @if($errors->any() || old('nameservers') || session('success')) open @endif>
                                <summary class="store-ns-btn store-ns-btn-primary">
                                    <span>Edit Nameservers</span>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                        <path d="M12 20h9"></path>
                                        <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                    </svg>
                                </summary>
                                <div class="store-ns-modal">
                                    <div class="store-ns-modal-backdrop"></div>
                                    <div class="store-ns-modal-card" role="dialog" aria-modal="true" aria-label="Nameservers and DNS">
                                        <button type="button" class="store-ns-modal-close" aria-label="Close">×</button>
                                        <div class="store-ns-modal-main">
                                            <h3>NAMESERVERS &amp; DNS</h3>
                                            <p class="store-ns-modal-domain">For: <strong>{{ $item->domain }}</strong></p>
                                            @if($errors->any())
                                                <div class="store-ns-alert is-error" role="alert">
                                                    <span class="store-ns-alert-icon" aria-hidden="true">
                                                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M12 7.5v5.5"></path><path d="M12 16.4h.01"></path></svg>
                                                    </span>
                                                    <span class="store-ns-alert-text">{{ $errors->first() }}</span>
                                                </div>
                                            @elseif(session('success'))
                                                <div class="store-ns-alert is-success" role="status">
                                                    <span class="store-ns-alert-icon" aria-hidden="true">
                                                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="m8.2 12.3 2.6 2.6 5-5.2"></path></svg>
                                                    </span>
                                                    <span class="store-ns-alert-text">{{ session('success') }}</span>
                                                </div>
                                            @endif
                                            <div class="store-child-note store-ns-modal-note">
                                                <p>Not sure which nameserver option is right for this domain?</p>
                                                <button type="button" class="store-child-info-trigger store-ns-info-button" aria-haspopup="dialog" aria-controls="nameserver-details-{{ $item->id }}">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v5"></path><path d="M12 8h.01"></path></svg>
                                                    <span>View details</span>
                                                </button>
                                            </div>
                                            <div class="store-ns-view">
                                                @if(!empty($nameservers))
                                                    <p class="store-ns-view-title">You are currently using these Nameservers</p>
                                                    @foreach($nameservers as $index => $nameserver)
                                                        <div class="store-ns-current-row">
                                                            <span>{{ $nameserver }}</span>
                                                            <button type="button" class="store-ns-pencil" aria-label="Edit nameserver {{ $index + 1 }}">
                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                                                    <path d="M12 20h9"></path>
                                                                    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <p class="store-ns-view-empty">No nameservers are configured yet.</p>
                                                    @for($index = 1; $index <= 2; $index++)
                                                        <div class="store-ns-current-row store-ns-empty-row">
                                                            <span>Nameserver {{ $index }} not configured</span>
                                                            <button type="button" class="store-ns-pencil" aria-label="Edit nameserver {{ $index }}">
                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                                                    <path d="M12 20h9"></path>
                                                                    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    @endfor
                                                    <p class="store-ns-empty-hint">Use the edit icon to add your nameservers.</p>
                                                @endif
                                            </div>
                                            <form method="POST" action="{{ route('mystore.domains.nameservers', $item->id) }}" class="store-nameserver-form" novalidate>
                                                @csrf
                                                <textarea name="nameservers" class="store-ns-submit-value" hidden>{{ implode("\n", $editNameservers) }}</textarea>
                                                <p class="store-ns-recommendation">We recommend adding at least 2 nameservers</p>
                                                <div class="store-ns-input-list">
                                                    @foreach($editNameservers as $index => $nameserver)
                                                        <div class="store-ns-input-row">
                                                            <div class="store-ns-field">
                                                                <input id="nameserver-{{ $item->id }}-{{ $index + 1 }}" type="text" value="{{ $nameserver }}" placeholder="ns{{ $index + 1 }}.example.com" aria-label="Nameserver {{ $index + 1 }}" required>
                                                                <span class="store-ns-field-icon" aria-hidden="true">
                                                                    <svg class="store-ns-icon-ok" viewBox="0 0 24 24"><path d="m6.5 12.5 4 4 7-8"></path></svg>
                                                                    <svg class="store-ns-icon-bad" viewBox="0 0 24 24"><path d="M12 7.5v6.2"></path><path d="M12 17.2h.01"></path></svg>
                                                                </span>
                                                                <small class="store-ns-field-msg"></small>
                                                            </div>
                                                            @if($index < 2)
                                                                <span class="store-ns-required" aria-hidden="true">*</span>
                                                            @endif
                                                            @if($index >= 2)
                                                                <button type="button" class="store-ns-remove" aria-label="Remove nameserver">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"></path></svg>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <p class="store-ns-form-status" role="status" aria-live="polite"></p>
                                                <button type="button" class="store-ns-add">+ Add Nameservers</button>
                                                <div class="store-ns-form-actions">
                                                    <button class="store-primary store-ns-submit" type="submit" disabled>Update Nameservers</button>
                                                    <button type="button" class="store-ns-cancel">Cancel Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                </details>
                                <button type="button" class="store-ns-btn store-ns-btn-secondary store-child-ns-trigger" aria-haspopup="dialog" aria-controls="child-ns-modal-{{ $item->id }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                                    <span>Add Child Server</span>
                                </button>
                            </div>
                    </div>
                    <span class="store-ns-divider" aria-hidden="true"></span>
                    <div class="store-ns-values">
                        <small class="store-ns-values-label"><i aria-hidden="true"></i>Active nameservers</small>
                        @forelse($nameservers as $nameserver)
                            <span><i aria-hidden="true"></i>{{ $nameserver }}</span>
                        @empty
                            <strong>No nameservers configured</strong>
                            <span class="store-ns-empty-copy">Add nameservers to connect this domain to your hosting.</span>
                        @endforelse
                    </div>
                </div>
            </section>
        </main>

    </div>

    <div class="store-child-details store-ns-error-modal" id="nameserver-error-details-{{ $item->id }}" hidden>
        <div class="store-ns-modal-backdrop store-ns-error-close"></div>
        <div class="store-child-details-card" role="dialog" aria-modal="true" aria-labelledby="nameserver-error-title-{{ $item->id }}">
            <button type="button" class="store-ns-modal-close store-ns-error-close" aria-label="Close">×</button>
            <div class="store-child-details-head">
                <span class="store-child-head-icon store-ns-error-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7.5v5.5"></path><path d="M12 16.4h.01"></path></svg>
                </span>
                <div>
                    <span class="store-child-kicker">NAME SERVER UPDATE</span>
                    <h3 id="nameserver-error-title-{{ $item->id }}">Why did this happen?</h3>
                    <p class="store-child-domain">For <strong>{{ $item->domain }}</strong></p>
                </div>
            </div>
            <div class="store-child-details-body">
                <p>We could not connect this domain to its registrar order, so the nameserver change was not submitted.</p>
                <div class="store-ns-error-section">
                    <strong>Use this format</strong>
                    <code>ns1.example.com</code>
                    <p>Enter at least two complete nameserver hostnames, one per line. Do not enter an IP address, <code>https://</code>, spaces, or a trailing path.</p>
                </div>
                <div class="store-ns-error-section">
                    <strong>Why the lookup can fail</strong>
                    <ul>
                        <li>The domain registration is still syncing with the registrar.</li>
                        <li>The domain order is not active or cannot be found by the registrar.</li>
                        <li>The registrar service is temporarily unavailable.</li>
                    </ul>
                </div>
                <p class="store-ns-error-support-copy">If your nameservers follow the format above, please contact support and include the domain name so our team can check the registrar order.</p>
            </div>
        </div>
    </div>

    <div class="store-child-details store-ns-info-modal" id="nameserver-details-{{ $item->id }}" hidden>
        <div class="store-ns-modal-backdrop store-ns-info-close"></div>
        <div class="store-child-details-card" role="dialog" aria-modal="true" aria-labelledby="nameserver-details-title-{{ $item->id }}">
            <button type="button" class="store-ns-modal-close store-ns-info-close" aria-label="Close">×</button>
            <div class="store-child-details-head">
                <span class="store-child-head-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v5"></path><path d="M12 8h.01"></path></svg>
                </span>
                <div>
                    <span class="store-child-kicker">NAME SERVERS &amp; DNS</span>
                    <h3 id="nameserver-details-title-{{ $item->id }}">How nameservers work</h3>
                    <p class="store-child-domain">For <strong>{{ $item->domain }}</strong></p>
                </div>
            </div>
            <div class="store-child-details-body">
                <p>Use the DNS service to point this domain to a hosting or email package. Choose one of the two options below.</p>
                <div class="store-ns-detail-option">
                    <span class="store-ns-detail-badge" aria-hidden="true">1</span>
                    <p>Specify at least 2 nameservers provided with your hosting package here. Your domain will start resolving to your hosting in 24–72 hours.</p>
                </div>
                <div class="store-ns-detail-or" aria-hidden="true"><span>OR</span></div>
                <div class="store-ns-detail-option">
                    <span class="store-ns-detail-badge" aria-hidden="true">2</span>
                    <p>Use our default nameservers and add the DNS records provided with your hosting package by clicking on <strong>Manage DNS Records</strong>. Your domain will start resolving to your hosting in 4–6 hours.</p>
                </div>
                <div class="store-child-details-tip">
                    <strong>Our default nameservers</strong>
                    <ul class="store-ns-detail-ns">
                        @forelse($nameservers as $nameserver)
                            <li><code>{{ $nameserver }}</code></li>
                        @empty
                            <li>No default nameservers are assigned yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
            <div class="store-child-details-foot">
                <button type="button" class="store-child-save store-ns-info-close">Got it</button>
            </div>
        </div>
    </div>

    <div class="store-child-modal" id="child-ns-modal-{{ $item->id }}" hidden>
        <div class="store-ns-modal-backdrop store-child-close"></div>
        <div class="store-child-card" role="dialog" aria-modal="true" aria-labelledby="child-ns-title-{{ $item->id }}">
            <button type="button" class="store-ns-modal-close store-child-close" aria-label="Close">×</button>
            <div class="store-child-head">
                <span class="store-child-head-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="6.5" rx="2"></rect><rect x="3" y="13.5" width="18" height="6.5" rx="2"></rect><path d="M7 7.25h.01"></path><path d="M7 16.75h.01"></path><path d="M11 7.25h4"></path><path d="M11 16.75h4"></path></svg>
                </span>
                <div class="store-child-head-copy">
                    <span class="store-child-kicker">DOMAIN MANAGEMENT</span>
                    <h3 id="child-ns-title-{{ $item->id }}">Add Child Nameservers</h3>
                    <p class="store-child-domain">For <strong>{{ $item->domain }}</strong></p>
                </div>
            </div>
            <div class="store-child-body">
                <div class="store-child-note">
                    <p>Child nameservers let this domain run its own DNS and answer queries under <strong>{{ $item->domain }}</strong>.</p>
                    <button type="button" class="store-child-info-trigger" aria-haspopup="dialog" aria-controls="child-ns-details-{{ $item->id }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v5"></path><path d="M12 8h.01"></path></svg>
                        <span>View details</span>
                    </button>
                </div>
                <div class="store-child-rows">
                    <div class="store-child-row">
                        <label class="store-child-field">
                            <span>Host name</span>
                            <input type="text" placeholder="ns1" autocomplete="off">
                        </label>
                        <label class="store-child-field">
                            <span>IP address</span>
                            <input type="text" placeholder="203.0.113.10" inputmode="decimal" autocomplete="off">
                        </label>
                        <button type="button" class="store-child-remove" aria-label="Remove this child server">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path></svg>
                        </button>
                    </div>
                </div>
                <button type="button" class="store-child-add">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
                    <span>Add another server</span>
                </button>
            </div>
            <div class="store-child-foot">
                <button type="button" class="store-child-cancel store-child-close">Cancel</button>
                <button type="button" class="store-child-save">Save Child Server</button>
            </div>
        </div>
    </div>

    <div class="store-child-details" id="child-ns-details-{{ $item->id }}" hidden>
        <div class="store-ns-modal-backdrop store-child-details-close"></div>
        <div class="store-child-details-card" role="dialog" aria-modal="true" aria-labelledby="child-ns-details-title-{{ $item->id }}">
            <button type="button" class="store-ns-modal-close store-child-details-close" aria-label="Close">×</button>
            <div class="store-child-details-head">
                <span class="store-child-head-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v5"></path><path d="M12 8h.01"></path></svg>
                </span>
                <div>
                    <span class="store-child-kicker">CHILD NAME SERVERS</span>
                    <h3 id="child-ns-details-title-{{ $item->id }}">What are child nameservers?</h3>
                </div>
            </div>
            <div class="store-child-details-body">
                <p>Your Child Nameservers can be <code>ns1.{{ $item->domain }}</code> and <code>ns2.{{ $item->domain }}</code>.</p>
                <p>Create Child Nameservers when you want to use these as the Nameservers for your Domain Names.</p>
                <p>Once created, Child Nameservers get registered with the Registry under this Domain Name.</p>
                <p>When you associate these nameservers with your domains, these details will be visible in the WHOIS results.</p>
                <div class="store-child-details-tip">
                    <p>Learn More about Child Nameservers</p>
                </div>
            </div>
            <div class="store-child-details-foot">
                <button type="button" class="store-child-save store-child-details-close">Got it</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.store-ns-edit').forEach(function (panel) {
    var view = panel.querySelector('.store-ns-view');
    var form = panel.querySelector('.store-nameserver-form');
    var inputList = form.querySelector('.store-ns-input-list');
    var close = panel.querySelector('.store-ns-modal-close');
    var cancel = panel.querySelector('.store-ns-cancel');
    var pencils = panel.querySelectorAll('.store-ns-pencil');
    var add = panel.querySelector('.store-ns-add');
    var submit = panel.querySelector('.store-ns-submit');
    var initialValues = [];
    var savedValues = @json(array_values($nameservers));

    function createInputRow(value, index) {
        var row = document.createElement('div');
        row.className = 'store-ns-input-row';
        row.innerHTML = '<div class="store-ns-field">' +
            '<input type="text" value="" placeholder="ns' + (index + 1) + '.example.com" aria-label="Nameserver ' + (index + 1) + '" required>' +
            '<span class="store-ns-field-icon" aria-hidden="true">' +
            '<svg class="store-ns-icon-ok" viewBox="0 0 24 24"><path d="m6.5 12.5 4 4 7-8"></path></svg>' +
            '<svg class="store-ns-icon-bad" viewBox="0 0 24 24"><path d="M12 7.5v6.2"></path><path d="M12 17.2h.01"></path></svg>' +
            '</span><small class="store-ns-field-msg"></small></div>' +
            (index < 2 ? '<span class="store-ns-required" aria-hidden="true">*</span>' : '') +
            (index >= 2 ? '<button type="button" class="store-ns-remove" aria-label="Remove nameserver">' +
                '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"></path></svg>' +
                '</button>' : '');
        row.querySelector('input').value = value || '';
        return row;
    }

    function resetForm() {
        var values = savedValues.length ? savedValues : ['ns1.example.com', 'ns2.example.com'];
        inputList.innerHTML = '';
        values.forEach(function (value, index) {
            inputList.appendChild(createInputRow(value, index));
        });
        initialValues = values.slice();
        form.querySelector('.store-ns-submit-value').value = values.join('\n');
        form.dataset.submitting = '0';
        submit.classList.remove('is-loading');
        submit.removeAttribute('aria-busy');
        updateSubmitState();
    }

    function showEdit() {
        panel.classList.add('is-editing');
        initialValues = Array.prototype.map.call(form.querySelectorAll('.store-ns-input-row input'), function (input) {
            return input.value;
        });
        updateSubmitState();
    }
    function currentValues() {
        return Array.prototype.map.call(form.querySelectorAll('.store-ns-input-row input'), function (input) {
            return input.value;
        });
    }
    function updateSubmitState() {
        submit.disabled = JSON.stringify(currentValues()) === JSON.stringify(initialValues);
    }
    function validateNameserverInput(input, index) {
        var field = input.closest('.store-ns-field');
        var value = input.value.trim();
        var hostnamePattern = /^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i;
        var message = '';

        if (!value && index < 2) {
            message = 'Nameserver is required. Please enter a nameserver like ns1.example.com.';
        } else if (value && !hostnamePattern.test(value)) {
            message = '"' + value + '" is not a valid nameserver hostname. Use a format like ns1.example.com.';
        }

        field.classList.toggle('is-invalid', Boolean(message));
        field.classList.toggle('is-valid', Boolean(value) && !message);
        field.querySelector('.store-ns-field-msg').textContent = message;
        return !message;
    }
    function validateNameservers() {
        var valid = true;
        var inputs = form.querySelectorAll('.store-ns-input-row input');
        Array.prototype.forEach.call(inputs, function (input, index) {
            if (!validateNameserverInput(input, index)) {
                valid = false;
            }
        });
        return valid;
    }
    function clearFeedback() {
        panel.querySelectorAll('.store-ns-error-trigger').forEach(function (trigger) {
            var details = document.getElementById(trigger.getAttribute('aria-controls'));
            if (details) {
                details.hidden = true;
            }
        });
        panel.querySelectorAll('.store-ns-alert, .store-ns-error-actions').forEach(function (feedback) {
            feedback.remove();
        });
    }
    if (panel.classList.contains('is-editing')) {
        showEdit();
    }
    function closePanel() {
        clearFeedback();
        resetForm();
        panel.removeAttribute('open');
        panel.classList.remove('is-editing');
    }
    pencils.forEach(function (button) { button.addEventListener('click', showEdit); });
    form.addEventListener('input', updateSubmitState);
    inputList.addEventListener('input', function (event) {
        if (event.target.matches('input')) {
            var inputs = inputList.querySelectorAll('.store-ns-input-row input');
            validateNameserverInput(event.target, Array.prototype.indexOf.call(inputs, event.target));
        }
    });
    close.addEventListener('click', closePanel);
    cancel.addEventListener('click', function () {
        resetForm();
        panel.classList.remove('is-editing');
    });
    panel.querySelector('.store-ns-modal-backdrop').addEventListener('click', closePanel);
    add.addEventListener('click', function () {
        var row = createInputRow('', inputList.querySelectorAll('.store-ns-input-row').length);
        inputList.appendChild(row);
        row.querySelector('input').addEventListener('input', updateSubmitState);
        updateSubmitState();
    });
    inputList.addEventListener('click', function (event) {
        var remove = event.target.closest('.store-ns-remove');
        if (!remove) {
            return;
        }
        var row = remove.closest('.store-ns-input-row');
        if (row) {
            row.remove();
            updateSubmitState();
        }
    });
    form.addEventListener('submit', function (event) {
        if (form.dataset.submitting === '1') {
            event.preventDefault();
            return;
        }
        if (!validateNameservers()) {
            event.preventDefault();
            form.dataset.submitting = '0';
            var firstInvalid = form.querySelector('.store-ns-field.is-invalid input');
            if (firstInvalid) {
                firstInvalid.focus();
            }
            return;
        }
        form.dataset.submitting = '1';
        form.querySelector('.store-ns-submit-value').value = Array.prototype.map.call(form.querySelectorAll('.store-ns-input-row input'), function (input) {
            return input.value.trim();
        }).filter(Boolean).join('\n');
        submit.classList.add('is-loading');
        submit.setAttribute('aria-busy', 'true');
    });
});

document.querySelectorAll('.store-ns-info-button').forEach(function (button) {
    var modal = document.getElementById(button.getAttribute('aria-controls'));
    var closers = modal.querySelectorAll('.store-ns-info-close');

    button.addEventListener('click', function () {
        modal.hidden = false;
    });

    document.querySelectorAll('.store-ns-error-trigger').forEach(function (button) {
        var modal = document.getElementById(button.getAttribute('aria-controls'));
        if (!modal) {
            return;
        }

        button.addEventListener('click', function () {
            modal.hidden = false;
        });
        modal.querySelectorAll('.store-ns-error-close').forEach(function (closer) {
            closer.addEventListener('click', function () {
                modal.hidden = true;
            });
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.hidden) {
                modal.hidden = true;
            }
        });
    });
    closers.forEach(function (closer) {
        closer.addEventListener('click', function () {
            modal.hidden = true;
        });
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            modal.hidden = true;
        }
    });
});

document.querySelectorAll('.store-child-ns-trigger').forEach(function (button) {
    var modal = document.getElementById(button.getAttribute('aria-controls'));
    if (!modal) {
        return;
    }
    var rows = modal.querySelector('.store-child-rows');
    var template = rows ? rows.querySelector('.store-child-row') : null;

    function resetChildForm() {
        if (!rows || !template) {
            return;
        }

        var firstRow = template.cloneNode(true);
        firstRow.querySelectorAll('input').forEach(function (input) {
            input.value = '';
        });
        rows.innerHTML = '';
        rows.appendChild(firstRow);
    }

    function closeChild() {
        resetChildForm();
        modal.hidden = true;
    }

    button.addEventListener('click', function () {
        resetChildForm();
        modal.hidden = false;
    });

    modal.querySelectorAll('.store-child-close').forEach(function (closer) {
        closer.addEventListener('click', closeChild);
    });

    var addRow = modal.querySelector('.store-child-add');
    if (addRow && rows && template) {
        addRow.addEventListener('click', function () {
            var row = template.cloneNode(true);
            row.querySelectorAll('input').forEach(function (input) {
                input.value = '';
            });
            rows.appendChild(row);
            var first = row.querySelector('input');
            if (first) {
                first.focus();
            }
        });
    }

    modal.addEventListener('click', function (event) {
        var remove = event.target.closest('.store-child-remove');
        if (!remove) {
            return;
        }
        var row = remove.closest('.store-child-row');
        if (!row || !rows) {
            return;
        }
        if (rows.querySelectorAll('.store-child-row').length > 1) {
            row.remove();
        } else {
            row.querySelectorAll('input').forEach(function (input) {
                input.value = '';
            });
        }
    });

    var save = modal.querySelector('.store-child-save');
    if (save) {
        save.addEventListener('click', closeChild);
    }

    var detailsTrigger = modal.querySelector('.store-child-info-trigger');
    var detailsModal = detailsTrigger ? document.getElementById(detailsTrigger.getAttribute('aria-controls')) : null;

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape' || modal.hidden) {
            return;
        }
        if (detailsModal && !detailsModal.hidden) {
            return;
        }
        closeChild();
    });
});

document.querySelectorAll('.store-child-info-trigger[aria-controls^="child-ns-details-"]').forEach(function (button) {
    var modal = document.getElementById(button.getAttribute('aria-controls'));
    if (!modal) {
        return;
    }

    button.addEventListener('click', function () {
        modal.hidden = false;
    });

    modal.querySelectorAll('.store-child-details-close').forEach(function (closer) {
        closer.addEventListener('click', function () {
            modal.hidden = true;
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            modal.hidden = true;
        }
    });
});
</script>
@endpush
