/* SSL purchase flow behaviour. Pages: resources/views/ssl/connect.blade.php and checkout.blade.php.
   Server-side validation is the real check; this only gives instant feedback. */
(function () {
    'use strict';

    /* ---------- step 1: connect a domain ---------- */
    var form = document.getElementById('sslConnectForm');
    if (form) {
        var input = document.getElementById('sslDomain');
        var field = input.closest('.sslx-field');
        var hint = document.getElementById('sslDomainHint');
        var button = document.getElementById('sslContinue');
        var state = field.querySelector('.sslx-field-state');
        var wildcardPlan = input.getAttribute('data-wildcard') === '1';
        var defaultHint = hint.getAttribute('data-default') || '';
        // Other flows (hosting) reuse this script and set their own wording.
        var validPrefix = hint.getAttribute('data-valid-prefix') || 'Certificate will be issued for';
        var wildMessage = hint.getAttribute('data-wild-message') || 'A wildcard name (*.) needs a Wildcard SSL plan.';
        // Same rule as App\Services\SslOrderService::DOMAIN_PATTERN.
        var pattern = /^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+(?:[a-z]{2,63}|xn--[a-z0-9-]{2,59})$/i;

        var clean = function (value) {
            var v = String(value).trim().toLowerCase()
                .replace(/^[a-z][a-z0-9+.-]*:\/\//, '')
                .replace(/[\/?#].*$/, '')
                .replace(/\.+$/, '');
            var wild = v.indexOf('*.') === 0;
            if (wild) { v = v.slice(2); }
            return { name: v, wild: wild };
        };

        var setHint = function (html) { hint.innerHTML = html; };
        var escapeHtml = function (s) {
            return s.replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        };

        var check = function () {
            var raw = input.value;
            var parsed = clean(raw);
            var valid = pattern.test(parsed.name) && (wildcardPlan || !parsed.wild);

            field.classList.toggle('is-valid', valid);
            field.classList.toggle('is-invalid', !valid && raw.trim() !== '' && parsed.name.indexOf('.') > -1 && /\.[a-z0-9-]{2,}$/i.test(parsed.name));
            state.textContent = valid ? '✓' : '✕';
            button.disabled = !valid;
            button.setAttribute('aria-disabled', valid ? 'false' : 'true');

            if (valid) {
                var shown = wildcardPlan ? '*.' + parsed.name : parsed.name;
                setHint(escapeHtml(validPrefix) + ' <strong>' + escapeHtml(shown) + '</strong>');
            } else if (!wildcardPlan && parsed.wild) {
                setHint(escapeHtml(wildMessage));
            } else {
                setHint(defaultHint);
            }
        };

        input.addEventListener('input', check);
        form.addEventListener('submit', function (event) {
            if (button.disabled) { event.preventDefault(); input.focus(); }
        });
        check();
    }

    /* ---------- step 2: checkout (signed-in) ---------- */
    var checkout = document.getElementById('sslCheckoutForm');
    if (checkout) {
        var radios = checkout.querySelectorAll('input[name="purchase_for"]');
        var identifier = document.getElementById('sslCustomerId');
        var confirmBtn = document.getElementById('sslCustomerConfirm');
        var status = document.getElementById('sslCustomerStatus');
        var submit = document.getElementById('sslPlaceOrder');
        var details = document.getElementById('sslCustomerDetails');
        var detailName = document.getElementById('sslCustomerName');
        var detailEmail = document.getElementById('sslCustomerEmail');
        var detailPhone = document.getElementById('sslCustomerPhone');
        var lookupUrl = checkout.getAttribute('data-lookup-url');
        var token = document.querySelector('meta[name="csrf-token"]');
        var confirmed = checkout.getAttribute('data-confirmed') || null; // the identifier that was verified

        var buyingForCustomer = function () {
            var checked = checkout.querySelector('input[name="purchase_for"]:checked');
            return checked && checked.value === 'customer';
        };

        var setStatus = function (text, kind) {
            status.textContent = text || '';
            status.className = 'sslx-customer-status' + (kind ? ' is-' + kind : '');
            identifier.classList.toggle('is-valid', kind === 'ok');
            identifier.classList.toggle('is-invalid', kind === 'bad');
        };

        // Fill the "Customer details" card once the server confirms the account.
        var setDetails = function (customer) {
            if (!details) { return; }
            if (!customer) {
                details.hidden = true;
                detailName.textContent = '';
                detailEmail.textContent = '';
                detailPhone.textContent = '';
                return;
            }
            detailName.textContent = customer.name || '—';
            detailEmail.textContent = customer.email || '—';
            detailPhone.textContent = customer.phone || 'Not provided';
            details.hidden = false;
        };

        var refresh = function () {
            var customer = buyingForCustomer();
            var verified = confirmed !== null && confirmed === identifier.value.trim();
            if (details) {
                details.hidden = !(customer && verified);
            }
            // data-locked: the button stays disabled (e.g. hosting: payment not connected yet).
            if (submit && submit.getAttribute('data-locked') !== '1') {
                submit.disabled = customer && !verified;
            }
        };

        for (var i = 0; i < radios.length; i++) {
            radios[i].addEventListener('change', refresh);
        }

        identifier.addEventListener('input', function () {
            confirmed = null;
            setStatus('', null);
            setDetails(null);
            refresh();
        });

        confirmBtn.addEventListener('click', function () {
            var value = identifier.value.trim();
            if (value === '') {
                setStatus('Enter the customer email or ID first.', 'bad');
                return;
            }

            confirmBtn.disabled = true;
            setStatus('Checking…', null);

            fetch(lookupUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
                },
                credentials: 'same-origin',
                body: JSON.stringify({ identifier: value })
            }).then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (body) {
                    return { ok: response.ok, status: response.status, body: body };
                });
            }).then(function (result) {
                if (result.ok && result.body.verified) {
                    confirmed = value;
                    setStatus('✓ Customer found. You can continue.', 'ok');
                    setDetails(result.body.customer || null);
                } else if (result.status === 404) {
                    confirmed = null;
                    setStatus(result.body.message || 'Customer does not exist.', 'bad');
                    setDetails(null);
                } else if (result.status === 429) {
                    setStatus('Too many attempts. Please wait a minute and try again.', 'bad');
                } else {
                    setStatus('Could not check the customer right now. Please try again.', 'bad');
                }
            }).catch(function () {
                setStatus('Could not check the customer right now. Please try again.', 'bad');
            }).then(function () {
                confirmBtn.disabled = false;
                refresh();
            });
        });

        // Payment is not live yet: Place order only opens the "coming soon" popup.
        // (The server refuses the order too while SSL_CHECKOUT_ENABLED is off.)
        if (checkout.getAttribute('data-live') !== '1') {
            checkout.addEventListener('submit', function (event) {
                event.preventDefault();
                var trigger = document.createElement('button');
                trigger.type = 'button';
                trigger.className = 'coming-soon-trigger';
                trigger.setAttribute('data-service', checkout.getAttribute('data-service') || 'SSL Certificate ordering');
                trigger.hidden = true;
                (submit || checkout).parentNode.appendChild(trigger);
                trigger.click();
                trigger.parentNode.removeChild(trigger);
            });
        }

        refresh();
    }
})();
