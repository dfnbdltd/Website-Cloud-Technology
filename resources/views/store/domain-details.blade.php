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
                    <span class="store-detail-icon" aria-hidden="true"><span>⌁</span></span>
                    <div class="store-ns-heading">
                        <h2>NAME SERVERS &amp; DNS</h2>
                        <div class="store-ns-tabs">
                            <details class="store-ns-edit @if($errors->any() || old('nameservers')) is-editing @endif" @if($errors->any() || old('nameservers')) open @endif>
                                <summary>Name Servers</summary>
                                <div class="store-ns-modal">
                                    <div class="store-ns-modal-backdrop"></div>
                                    <div class="store-ns-modal-card" role="dialog" aria-modal="true" aria-label="Name Servers and DNS">
                                        <button type="button" class="store-ns-modal-close" aria-label="Close">×</button>
                                        <div class="store-ns-modal-main">
                                            <h3>NAME SERVERS &amp; DNS</h3>
                                            <p class="store-ns-modal-domain">For: <strong>{{ $item->domain }}</strong></p>
                                            <div class="store-ns-view">
                                                <p>You are currently using these Name Servers</p>
                                                @forelse($nameservers as $index => $nameserver)
                                                    <div class="store-ns-current-row">
                                                        <span>{{ $nameserver }}</span>
                                                        <button type="button" class="store-ns-pencil" aria-label="Edit nameserver {{ $index + 1 }}">✎</button>
                                                    </div>
                                                @empty
                                                    <p class="store-muted">No nameservers are configured yet.</p>
                                                    @for($index = 1; $index <= 2; $index++)
                                                        <div class="store-ns-current-row store-ns-empty-row">
                                                            <span>Nameserver {{ $index }} not configured</span>
                                                            <button type="button" class="store-ns-pencil" aria-label="Edit nameserver {{ $index }}">✎</button>
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
                                        <aside class="store-ns-modal-help">
                                            <strong>Use the DNS service to point this domain to a hosting or email package.</strong>
                                            <p>To do this,</p>
                                            <ul>
                                                <li>Specify at least 2 name servers provided with your hosting package here. Your domain will start resolving to your hosting in 24-72 hours.</li>
                                            </ul>
                                            <p>OR</p>
                                            <ul>
                                                <li>Use our default name servers and add the DNS records provided with your hosting package by clicking on 'Manage DNS Records'. Your domain will start resolving to your hosting in 4-6 hours.</li>
                                            </ul>
                                            <strong>Our default name servers are:</strong>
                                            <ul>
                                                @foreach($nameservers as $nameserver)<li>{{ $nameserver }}</li>@endforeach
                                            </ul>
                                        </aside>
                                    </div>
                                </div>
                            </details>
                            <button type="button" class="store-ns-dns-button" disabled>DNS Records</button>
                        </div>
                    </div>
                    <span class="store-ns-divider" aria-hidden="true"></span>
                    <div class="store-ns-values">
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

        <aside class="store-detail-panel store-activity-panel">
            <div class="store-detail-section-title">
                <div><span class="domain-kicker">LATEST ACTIVITY</span><h2>Latest Activity</h2></div>
            </div>
            @forelse($activities ?? [] as $activity)
                <div class="store-activity-row">
                    <span class="store-activity-dot"></span>
                    <div><small>{{ optional($activity['at'])->format('d M Y') }}</small><strong>{{ $activity['title'] }}</strong><p>{{ $activity['description'] }}</p></div>
                </div>
            @empty
                <p class="store-muted">No local activity recorded yet.</p>
            @endforelse
            <a class="store-activity-view-all" href="{{ route('mystore.domains.activity', $item->id) }}">View All Activity →</a>
        </aside>
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
</script>
@endpush
