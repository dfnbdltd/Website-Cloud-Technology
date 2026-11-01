<!doctype html><html lang="en"><head>@include('partials.head', ['title' => 'bKash payment'])<link rel="stylesheet" href="{{ asset('css/domains.css') }}?v={{ filemtime(public_path('css/domains.css')) }}"><script src="https://scripts.pay.bka.sh/versions/1.2.0-beta/checkout/bKash-checkout.js"></script><style>
.bkash-shell{max-width:760px;margin:0 auto}
.bkash-summary{display:flex;flex-wrap:wrap;gap:0;border:1px solid #e6e8ef;border-radius:14px;overflow:hidden;background:#fff;margin:22px 0 26px}
.bkash-summary>div{flex:1 1 180px;padding:16px 22px;border-right:1px solid #eef0f5}
.bkash-summary>div:last-child{border-right:none}
.bkash-summary small{display:block;font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#8a90a3;margin-bottom:4px}
.bkash-summary strong{font-size:18px;color:#1c2333;font-weight:700}
.bkash-summary .amt strong{color:#D51469;font-size:24px}
.bkash-window{background:#fff;border:1px solid #e6e8ef;border-radius:16px;overflow:hidden;box-shadow:0 18px 45px rgba(28,35,51,.08)}
.bkash-window-head{display:flex;align-items:center;justify-content:space-between;padding:16px 22px;background:#fff;border-bottom:1px solid #eef0f5}
.bkash-brand{display:flex;align-items:center;gap:10px}
.bkash-brand .dot{width:38px;height:38px;border-radius:10px;background:#D51469;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px}
.bkash-brand span.title{font-weight:700;color:#1c2333;font-size:15px}
.bkash-brand small{display:block;color:#8a90a3;font-size:12px}
.bkash-status{display:inline-flex;align-items:center;gap:8px;font-size:13px;color:#5b6478}
.bkash-status .pulse{width:9px;height:9px;border-radius:50%;background:#f0a52f;animation:bkpulse 1.4s ease-in-out infinite}
.bkash-status.ok .pulse{background:#27ae60;animation:none}
.bkash-status.err .pulse{background:#e05252;animation:none}
@keyframes bkpulse{0%,100%{opacity:.35}50%{opacity:1}}
.bkash-body{padding:34px 22px;text-align:center}
.bkash-body p.lead{color:#5b6478;font-size:15px;margin:0 0 20px}
.bkash-button{border:0;background:#D51469;color:#fff;font-size:16px;font-weight:700;padding:15px 34px;border-radius:8px;cursor:pointer;box-shadow:0 10px 25px rgba(213,20,105,.25)}
.bkash-button:disabled{background:#e6a9c1;cursor:not-allowed;box-shadow:none}
.bkash-error{display:none;margin:0 auto 18px;max-width:520px;padding:12px 15px;border-radius:8px;background:#fff0f0;color:#c95c52;font-size:13px;text-align:left}
.bkash-foot{padding:14px 22px;border-top:1px solid #eef0f5;background:#fff}
.bkash-foot small{color:#8a90a3;font-size:12px}
.bkash-foot a{font-size:13px;color:#3b62e0;text-decoration:none}
</style></head><body class="domain-app">@php($backUrl = $backUrl ?? route('domains.confirmation', ['reference' => $order['reference']]))<main class="checkout-page">
<a class="domain-brand checkout-brand" href="{{ route('domains.index') }}">@include('partials.logo', ['gid' => 'b'])</a>
<div class="checkout-head"><div><span class="domain-kicker">SECURE PAYMENT</span><h1>{{ $heading ?? 'Pay with bKash.' }}</h1><p>Complete the payment in the official bKash window — wallet number, then the OTP and PIN.</p></div></div>

<div class="bkash-shell">
    <div class="bkash-summary">
        <div><small>{{ $summaryLabel ?? 'Order' }}</small><strong>{{ $order['reference'] }}</strong></div>
        <div class="amt"><small>Amount</small><strong>৳ {{ number_format($order['total']) }}</strong></div>
        <div><small>Method</small><strong>bKash</strong></div>
    </div>

    @if(!empty($order['payment']['transaction_id']))
        <div class="bkash-window">
            <div class="bkash-window-head">
                <div class="bkash-brand"><span class="dot">bK</span><span class="title">Payment completed<span class="bkash-status ok" style="margin-left:10px"><span class="pulse"></span>Success</span></span></div>
            </div>
            <div class="bkash-body">
                <p style="color:#27ae60;font-weight:700;margin:0 0 6px">Payment already completed</p>
                <p style="color:#5b6478;margin:0 0 22px">Transaction: <strong>{{ $order['payment']['transaction_id'] }}</strong></p>
                <a class="checkout-submit" style="display:inline-block" href="{{ $backUrl }}">Continue <b>→</b></a>
            </div>
        </div>
    @elseif(empty($gatewayReady))
        <div class="bkash-window">
            <div class="bkash-window-head">
                <div class="bkash-brand"><span class="dot">bK</span><span><span class="title">bKash payment unavailable</span></span></div>
            </div>
            <div class="bkash-body">
                <div class="bkash-error" style="display:block">bKash is not configured on this server yet. Your order is saved — our billing team will contact you with payment instructions for reference {{ $order['reference'] }}.</div>
                <a class="checkout-submit bkash-cancel-link" style="display:inline-block" href="{{ route('domains.index') }}">Back <b>→</b></a>
            </div>
        </div>
    @else
        <div class="bkash-window">
            <div class="bkash-window-head">
                <div class="bkash-brand">
                    <span class="dot">bK</span>
                    <span><span class="title">bKash Payment</span><small>Official bKash checkout window</small></span>
                </div>
                <span class="bkash-status" id="bkashStatusPill"><span class="pulse"></span><span id="bkashStatusText">Ready when you are</span></span>
            </div>
            <div class="bkash-body">
                <div class="bkash-error" id="bkashError"></div>
                <p class="lead">Click the button below and complete the payment in the bKash window that opens.</p>
                <button id="bKash_button" class="bkash-button" type="button" disabled>Pay ৳ {{ number_format($order['total']) }} with bKash</button>
            </div>
            <div class="bkash-foot">
                <small>🔒 The payment window is provided by bKash. Never share your PIN outside this window.</small>
                <a class="bkash-cancel-link" href="{{ route('domains.index') }}">{{ $backLabel ?? 'Cancel and go back →' }}</a>
            </div>
        </div>

        <script>
        (function () {
            var statusPill = document.getElementById('bkashStatusPill');
            var statusText = document.getElementById('bkashStatusText');
            var errorBox = document.getElementById('bkashError');
            var button = document.getElementById('bKash_button');

            function setStatus(text, cls) {
                statusText.textContent = text;
                statusPill.classList.remove('ok', 'err');
                if (cls) { statusPill.classList.add(cls); }
            }

            function showError(message) {
                errorBox.textContent = message;
                errorBox.style.display = 'block';
            }

            function post(path, body) {
                return fetch(path, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(body || {})
                }).then(function (response) {
                    return response.json().then(function (data) { return { ok: response.ok, status: response.status, data: data }; });
                });
            }

            document.querySelectorAll('.bkash-cancel-link').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    link.style.pointerEvents = 'none';
                    setStatus('Cancelling checkout…', '');
                    post('{{ route('bkash.cancel') }}').then(function (result) {
                        window.location.href = result.data.redirect || link.href;
                    }).catch(function () {
                        window.location.href = link.href;
                    });
                });
            });

            function finish(data) {
                setStatus('Payment successful — redirecting…', 'ok');
                button.disabled = true;
                window.location.href = data.redirect;
            }

            function cancelCheckout() {
                post('{{ route('bkash.cancel') }}').then(function (result) {
                    window.location.href = result.data.redirect || '{{ route('domains.index') }}';
                }).catch(function () {
                    window.location.href = '{{ route('domains.index') }}';
                });
            }

            function handleClosedPayment() {
                post('{{ $endpoints['query'] ?? route('bkash.query') }}').then(function (result) {
                    if (result.ok && result.data.status === 'success') {
                        finish(result.data);
                        return;
                    }
                    cancelCheckout();
                }).catch(function () {
                    cancelCheckout();
                });
            }

            bKash.init({
                paymentMode: 'checkout',
                paymentRequest: {
                    amount: '{{ number_format($order['total'], 2, '.', '') }}',
                    intent: 'sale'
                },
                createRequest: function (request) {
                    setStatus('Creating payment…', '');
                    post('{{ $endpoints['create'] ?? route('bkash.create') }}').then(function (result) {
                        if (result.ok && result.data.paymentID) {
                            setStatus('Waiting for bKash…', '');
                            bKash.create().onSuccess(result.data);
                        } else {
                            setStatus('Could not start the payment', 'err');
                            showError((result.data && result.data.errorMessage) || 'bKash could not start the payment.');
                            bKash.create().onError();
                        }
                    }).catch(function () {
                        setStatus('Could not start the payment', 'err');
                        showError('Network error while starting the payment.');
                        bKash.create().onError();
                    });
                },
                executeRequestOnAuthorization: function () {
                    setStatus('Verifying payment…', '');
                    (function tryExecute() {
                        post('{{ $endpoints['execute'] ?? route('bkash.execute') }}').then(function (result) {
                            if (result.ok && result.data.status === 'success') { finish(result.data); return; }
                            if (result.data && result.data.status === 'pending') { setStatus('Still confirming with bKash…', ''); setTimeout(tryExecute, 3000); return; }
                            setStatus('Payment was not completed', 'err');
                            showError((result.data && result.data.errorMessage) || 'Payment was not completed — you can retry.');
                            bKash.execute().onError();
                        }).catch(function () {
                            setStatus('Verifying payment…', '');
                            setTimeout(tryExecute, 3000);
                        });
                    })();
                },
                onClose: function () {
                    setStatus('Cancelling checkout…', '');
                    handleClosedPayment();
                }
            });

            button.removeAttribute('disabled');
        })();
        </script>
    @endif
</div>
</main>
@include('partials.chat')
</body></html>
