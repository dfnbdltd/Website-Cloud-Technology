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
                setHint('Certificate will be issued for <strong>' + escapeHtml(shown) + '</strong>');
            } else if (!wildcardPlan && parsed.wild) {
                setHint('A wildcard name (*.) needs a Wildcard SSL plan.');
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
        var lookupUrl = checkout.getAttribute('data-lookup-url');
        var token = document.querySelector('meta[name="csrf-token"]');
        var confirmed = null; // the identifier that was verified

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

        var refresh = function () {
            if (!submit) { return; }
            submit.disabled = buyingForCustomer() && confirmed !== identifier.value.trim();
        };

        for (var i = 0; i < radios.length; i++) {
            radios[i].addEventListener('change', refresh);
        }

        identifier.addEventListener('input', function () {
            confirmed = null;
            setStatus('', null);
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
                } else if (result.status === 404) {
                    confirmed = null;
                    setStatus(result.body.message || 'Customer does not exist.', 'bad');
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
                trigger.setAttribute('data-service', 'SSL Certificate ordering');
                trigger.hidden = true;
                (submit || checkout).parentNode.appendChild(trigger);
                trigger.click();
                trigger.parentNode.removeChild(trigger);
            });
        }

        refresh();
    }
})();
