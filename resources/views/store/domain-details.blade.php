@extends('store.layout', ['pageTitle' => 'Manage ' . $item->domain, 'navActive' => 'my-domains', 'topbarTitle' => 'My Store'])

@section('store')
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

            <section class="store-detail-panel">
                <div class="store-ns-row">
                    <span class="store-detail-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="6.5" rx="2"></rect><rect x="3" y="13.5" width="18" height="6.5" rx="2"></rect><path d="M7 7.25h.01"></path><path d="M7 16.75h.01"></path><path d="M11 7.25h4"></path><path d="M11 16.75h4"></path></svg>
                    </span>
                    <div class="store-ns-heading">
                        <h2>NAME SERVERS &amp; DNS</h2>
                        <p class="store-ns-subtitle">Choose where this domain points on the internet.</p>
                        <div class="store-ns-actions">
                            <details class="store-ns-edit @if($errors->any() || old('nameservers')) is-editing @endif" @if($errors->any() || old('nameservers')) open @endif>
                                <summary class="store-ns-btn store-ns-btn-primary">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25Z"></path>
                                        <path d="m14.06 6.19 1.77-1.77a2.12 2.12 0 0 1 3 0l.75.75a2.12 2.12 0 0 1 0 3l-1.77 1.77"></path>
                                    </svg>
                                    <span>Name Servers</span>
                                </summary>
                                <div class="store-ns-modal">
                                    <div class="store-ns-modal-backdrop"></div>
                                    <div class="store-ns-modal-card" role="dialog" aria-modal="true" aria-label="Name Servers and DNS">
                                        <button type="button" class="store-ns-modal-close" aria-label="Close">×</button>
                                        <div class="store-ns-modal-main">
                                            <h3>NAME SERVERS &amp; DNS</h3>
                                            <p class="store-ns-modal-domain">For: <strong>{{ $item->domain }}</strong></p>
                                            <div class="store-child-note store-ns-modal-note">
                                                <p>Not sure which name server option is right for this domain?</p>
                                                <button type="button" class="store-child-info-trigger store-ns-info-button" aria-haspopup="dialog" aria-controls="nameserver-details-{{ $item->id }}">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 11v5"></path><path d="M12 8h.01"></path></svg>
                                                    <span>View details</span>
                                                </button>
                                            </div>
                                            <div class="store-ns-view">
                                                <p>You are currently using these Name Servers</p>
                                                @forelse($nameservers as $index => $nameserver)
                                                    <div class="store-ns-current-row">
                                                        <span>{{ $nameserver }}</span>
                                                        <button type="button" class="store-ns-pencil" aria-label="Edit nameserver {{ $index + 1 }}">
                                                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                                <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25Z"></path>
                                                                <path d="m14.06 6.19 1.77-1.77a2.12 2.12 0 0 1 3 0l.75.75a2.12 2.12 0 0 1 0 3l-1.77 1.77"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                @empty
                                                    <p class="store-muted">No nameservers are configured yet.</p>
                                                    @for($index = 1; $index <= 2; $index++)
                                                        <div class="store-ns-current-row store-ns-empty-row">
                                                            <span>Nameserver {{ $index }} not configured</span>
                                                            <button type="button" class="store-ns-pencil" aria-label="Edit nameserver {{ $index }}">
                                                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                                    <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25Z"></path>
                                                                    <path d="m14.06 6.19 1.77-1.77a2.12 2.12 0 0 1 3 0l.75.75a2.12 2.12 0 0 1 0 3l-1.77 1.77"></path>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    @endfor
                                                @endforelse
                                            </div>
                                            <form method="POST" action="{{ route('mystore.domains.nameservers', $item->id) }}" class="store-nameserver-form">
                                                @csrf
                                                <textarea name="nameservers" class="store-ns-submit-value" hidden>{{ old('nameservers', implode("\n", $nameservers ?: ['ns1.example.com', 'ns2.example.com'])) }}</textarea>
                                                <p class="store-ns-recommendation">We recommend adding at least 2 name servers</p>
                                                <div class="store-ns-input-list">
                                                    @foreach($nameservers ?: ['ns1.example.com', 'ns2.example.com'] as $index => $nameserver)
                                                        <div class="store-ns-input-row">
                                                            <input type="text" value="{{ old('nameservers.' . $index, $nameserver) }}" placeholder="ns{{ $index + 1 }}.example.com" required>
                                                            <b>*</b>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <button type="button" class="store-ns-add">+ Add Name Servers</button>
                                                <div class="store-ns-form-actions">
                                                    <button class="store-primary store-ns-submit" type="submit" disabled>Update Name Servers</button>
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
                    <h3 id="nameserver-details-title-{{ $item->id }}">How name servers work</h3>
                    <p class="store-child-domain">For <strong>{{ $item->domain }}</strong></p>
                </div>
            </div>
            <div class="store-child-details-body">
                <p>Use the DNS service to point this domain to a hosting or email package. Choose one of the two options below.</p>
                <div class="store-ns-detail-option">
                    <span class="store-ns-detail-badge" aria-hidden="true">1</span>
                    <p>Specify at least 2 name servers provided with your hosting package here. Your domain will start resolving to your hosting in 24–72 hours.</p>
                </div>
                <div class="store-ns-detail-or" aria-hidden="true"><span>OR</span></div>
                <div class="store-ns-detail-option">
                    <span class="store-ns-detail-badge" aria-hidden="true">2</span>
                    <p>Use our default name servers and add the DNS records provided with your hosting package by clicking on <strong>Manage DNS Records</strong>. Your domain will start resolving to your hosting in 4–6 hours.</p>
                </div>
                <div class="store-child-details-tip">
                    <strong>Our default name servers</strong>
                    <ul class="store-ns-detail-ns">
                        @forelse($nameservers as $nameserver)
                            <li><code>{{ $nameserver }}</code></li>
                        @empty
                            <li>No default name servers are assigned yet.</li>
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
                    <h3 id="child-ns-title-{{ $item->id }}">Add Child Server</h3>
                    <p class="store-child-domain">For <strong>{{ $item->domain }}</strong></p>
                </div>
            </div>
            <div class="store-child-body">
                <div class="store-child-note">
                    <p>Child name servers let this domain run its own DNS and answer queries under <strong>{{ $item->domain }}</strong>.</p>
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
                    <div class="store-child-row">
                        <label class="store-child-field">
                            <span>Host name</span>
                            <input type="text" placeholder="ns2" autocomplete="off">
                        </label>
                        <label class="store-child-field">
                            <span>IP address</span>
                            <input type="text" placeholder="203.0.113.11" inputmode="decimal" autocomplete="off">
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
                    <h3 id="child-ns-details-title-{{ $item->id }}">What are child name servers?</h3>
                </div>
            </div>
            <div class="store-child-details-body">
                <p>Child name servers, also called vanity name servers, are branded name servers that use your own domain — for example <code>ns1.{{ $item->domain }}</code> instead of your hosting provider's domain.</p>
                <p>To set them up:</p>
                <ol>
                    <li>Create two A records, <code>ns1.{{ $item->domain }}</code> and <code>ns2.{{ $item->domain }}</code>, pointing to the IP addresses provided by your hosting company.</li>
                    <li>Add each child server below with its matching host name and IP address.</li>
                    <li>Update this domain's name servers to the child servers you created.</li>
                    <li>Allow up to 24–72 hours for DNS propagation worldwide.</li>
                </ol>
                <div class="store-child-details-tip">
                    <strong>Good to know</strong>
                    <p>Use at least two child servers for reliability, and keep both host names inside the domain shown above.</p>
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
    var close = panel.querySelector('.store-ns-modal-close');
    var cancel = panel.querySelector('.store-ns-cancel');
    var pencils = panel.querySelectorAll('.store-ns-pencil');
    var add = panel.querySelector('.store-ns-add');
    var submit = panel.querySelector('.store-ns-submit');
    var initialValues = [];

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
    if (panel.classList.contains('is-editing')) {
        showEdit();
    }
    function closePanel() {
        panel.removeAttribute('open');
        panel.classList.remove('is-editing');
    }
    pencils.forEach(function (button) { button.addEventListener('click', showEdit); });
    form.addEventListener('input', updateSubmitState);
    close.addEventListener('click', closePanel);
    cancel.addEventListener('click', function () { panel.classList.remove('is-editing'); });
    panel.querySelector('.store-ns-modal-backdrop').addEventListener('click', closePanel);
    add.addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'store-ns-input-row';
        row.innerHTML = '<input type="text" placeholder="ns.example.com" required><b>*</b>';
        form.querySelector('.store-ns-input-list').appendChild(row);
        row.querySelector('input').addEventListener('input', updateSubmitState);
        updateSubmitState();
    });
    form.addEventListener('submit', function () {
        form.querySelector('.store-ns-submit-value').value = Array.prototype.map.call(form.querySelectorAll('.store-ns-input-row input'), function (input) {
            return input.value.trim();
        }).filter(Boolean).join('\n');
    });
});

document.querySelectorAll('.store-ns-info-button').forEach(function (button) {
    var modal = document.getElementById(button.getAttribute('aria-controls'));
    var closers = modal.querySelectorAll('.store-ns-info-close');

    button.addEventListener('click', function () {
        modal.hidden = false;
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

    function closeChild() {
        modal.hidden = true;
    }

    button.addEventListener('click', function () {
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
