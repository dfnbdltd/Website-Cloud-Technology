/* Hosting purchase, step 1: connect a domain name.
   Page: resources/views/hosting/connect.blade.php

   The customer types a domain and presses Check (or Enter). The server asks
   ResellerClub whether the domain is already registered (POST data-check-url).
   Only a registered domain gets the green tick and unlocks "Continue to Cart".
   Editing the field again takes the tick away. The server checks again when the
   form is submitted, so this script is feedback only, never the proof. */
(function () {
    'use strict';

    var form = document.getElementById('hostConnectForm');
    if (!form) { return; }

    var input = document.getElementById('hostDomain');
    var field = input.closest('.sslx-field');
    var state = field.querySelector('.sslx-field-state');
    var checkBtn = document.getElementById('hostCheck');
    var hint = document.getElementById('hostDomainHint');
    var errorBox = document.getElementById('hostDomainError');
    var registerLink = document.getElementById('hostRegisterLink');
    var continueBtn = document.getElementById('hostContinue');
    var token = document.querySelector('meta[name="csrf-token"]');
    var checkUrl = form.getAttribute('data-check-url');
    var defaultHint = hint.getAttribute('data-default') || '';

    // Same hostname rule as App\Services\SslOrderService::DOMAIN_PATTERN.
    var pattern = /^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+(?:[a-z]{2,63}|xn--[a-z0-9-]{2,59})$/i;

    var verifiedValue = null;   // the exact text that passed the check
    var busy = false;
    var requestId = 0;

    var clean = function (value) {
        return String(value).trim().toLowerCase()
            .replace(/^[a-z][a-z0-9+.-]*:\/\//, '')
            .replace(/[\/?#].*$/, '')
            .replace(/\.+$/, '');
    };

    var escapeHtml = function (s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    };

    var showError = function (text) {
        errorBox.textContent = text || '';
        errorBox.hidden = !text;
    };

    var render = function (mode, message, registerUrl) {
        // mode: idle | checking | ok | bad
        field.classList.toggle('is-valid', mode === 'ok');
        field.classList.toggle('is-invalid', mode === 'bad');
        state.textContent = mode === 'ok' ? '✓' : (mode === 'bad' ? '✕' : '');
        hint.classList.toggle('is-ok', mode === 'ok');

        var ok = mode === 'ok';
        continueBtn.disabled = !ok;
        continueBtn.setAttribute('aria-disabled', ok ? 'false' : 'true');

        showError(mode === 'bad' ? message : '');
        if (mode === 'ok') {
            hint.innerHTML = 'This plan will be connected to <strong>' + escapeHtml(clean(input.value)) + '</strong>';
        } else if (mode === 'checking') {
            hint.textContent = 'Checking the domain…';
        } else {
            hint.textContent = defaultHint;
        }

        if (registerUrl) {
            registerLink.querySelector('a').setAttribute('href', registerUrl);
            registerLink.hidden = false;
        } else {
            registerLink.hidden = true;
        }
    };

    var runCheck = function () {
        if (busy) { return; }

        var typed = input.value.trim();
        var name = clean(typed);
        if (name === '' || !pattern.test(name)) {
            verifiedValue = null;
            render('bad', 'Enter a valid domain name, like example.com.');
            return;
        }

        busy = true;
        var mine = ++requestId;
        checkBtn.disabled = true;
        render('checking');

        var currentToken = function () {
            var meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        };

        var send = function () {
            return fetch(checkUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': currentToken()
                },
                credentials: 'same-origin',
                body: JSON.stringify({ domain: typed })
            }).then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (body) {
                    return { status: response.status, body: body };
                });
            });
        };

        // 419 = the page's security token no longer matches the session (logged in in
        // another tab, session expired, page served from a cache). Get a fresh token
        // from the server and try once more, instead of showing "CSRF token mismatch".
        var refreshToken = function () {
            return fetch(window.location.href, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) {
                return response.text();
            }).then(function (html) {
                var match = html.match(/name="csrf-token" content="([^"]+)"/);
                if (!match) { return; }
                var meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) { meta.setAttribute('content', match[1]); }
                var hidden = form.querySelector('input[name="_token"]');
                if (hidden) { hidden.value = match[1]; }
            }).catch(function () { /* the retry below will report the failure */ });
        };

        send().then(function (result) {
            if (result.status !== 419) { return result; }
            return refreshToken().then(send);
        }).then(function (result) {
            if (mine !== requestId) { return; }   // the field changed while we waited
            if (result.status === 419) {
                verifiedValue = null;
                render('bad', 'This page has expired. Please reload it (Ctrl+F5) and try again.');
            } else if (result.status === 429) {
                verifiedValue = null;
                render('bad', 'Too many checks. Please wait a minute and try again.');
            } else if (result.body && result.body.ok === true) {
                verifiedValue = typed;
                render('ok');
            } else if (result.body && result.body.message) {
                verifiedValue = null;
                render('bad', result.body.message, result.body.state === 'available' ? result.body.register_url : null);
            } else {
                verifiedValue = null;
                render('bad', 'We could not verify this domain right now. Please try again in a moment.');
            }
        }).catch(function () {
            if (mine !== requestId) { return; }
            verifiedValue = null;
            render('bad', 'We could not verify this domain right now. Please try again in a moment.');
        }).then(function () {
            busy = false;
            checkBtn.disabled = false;
        });
    };

    // Any edit takes the tick away: the new text has not been checked.
    input.addEventListener('input', function () {
        requestId++;           // ignore an answer that is still on its way
        busy = false;
        checkBtn.disabled = false;
        verifiedValue = null;
        render('idle');
    });

    checkBtn.addEventListener('click', runCheck);

    // Enter checks the domain; it never submits an unchecked form.
    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            if (verifiedValue === null || verifiedValue !== input.value.trim()) { runCheck(); }
            else { form.submit(); }
        }
    });

    form.addEventListener('submit', function (event) {
        if (verifiedValue === null || verifiedValue !== input.value.trim()) {
            event.preventDefault();
            input.focus();
        }
    });

    // Reopened with a domain that already passed for this plan: keep the tick.
    var serverError = errorBox.textContent.trim();
    if (form.getAttribute('data-verified') === '1' && input.value.trim() !== '') {
        verifiedValue = input.value.trim();
        render('ok');
    } else if (serverError !== '') {
        // The server refused the submitted domain (it re-checks): show its reason.
        render('bad', serverError);
    } else {
        render('idle');
    }
})();
