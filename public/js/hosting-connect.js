/* Hosting purchase, step 1: connect a domain name.
   Page: resources/views/hosting/connect.blade.php

   The three option cards drive a hidden <select id="hostMode"> (own | register | transfer);
   the server whitelists the same value. What the button beside the box does:

     register  Search: type "xyz" or "xyz.com" and the results appear under the box
               (POST data-search-url, the same search as the Domain Registration page).
               "Purchase now / Add to cart" on a free name submits the form in register
               mode; the server re-checks it and hands the customer to the domain checkout.
     transfer  Validate: sends the customer to the Domain Transfer page with the name
               filled in (the form is submitted in transfer mode; the server redirects).
     own       Check: asks ResellerClub whether the domain is already registered
               (POST data-check-url). A registered domain gets the green tick, the Check
               button is disabled and "Continue to Cart" unlocks.

   Everything here is feedback only: the server checks again on submit. */
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
    var resultsBox = document.getElementById('hostResults');
    var switchLink = document.getElementById('hostSwitchLink');
    var modeSelect = document.getElementById('hostMode');
    var continueBtn = document.getElementById('hostContinue');
    var checkUrl = form.getAttribute('data-check-url');
    var searchUrl = form.getAttribute('data-search-url');
    var isCustomer = form.getAttribute('data-customer') === '1';
    var modes = {};
    try { modes = JSON.parse(document.getElementById('hostModes').textContent) || {}; } catch (e) { modes = {}; }

    // Same hostname rule as App\Services\SslOrderService::DOMAIN_PATTERN.
    var pattern = /^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+(?:[a-z]{2,63}|xn--[a-z0-9-]{2,59})$/i;
    // Register search accepts a bare name ("xyz") as well as "xyz.com".
    var namePattern = /^[a-z0-9][a-z0-9.-]{1,62}$/i;

    var verifiedValue = null;   // own mode: the exact text that passed the check
    var busy = false;
    var requestId = 0;

    var currentMode = function () { return modeSelect ? modeSelect.value : 'own'; };

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

    var money = function (n) {
        n = Number(n) || 0;
        var whole = Math.abs(n - Math.round(n)) < 0.005;
        return n.toLocaleString('en-US', { minimumFractionDigits: whole ? 0 : 2, maximumFractionDigits: whole ? 0 : 2 });
    };

    var showError = function (text) {
        errorBox.textContent = text || '';
        errorBox.hidden = !text;
    };

    var currentToken = function () {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    };

    var clearResults = function () {
        resultsBox.innerHTML = '';
        resultsBox.hidden = true;
    };

    // own mode: ticked and unchanged
    var ownVerified = function () {
        return currentMode() === 'own' && verifiedValue !== null && verifiedValue === input.value.trim();
    };

    /* Paint the box. mode: idle | checking | ok | bad */
    var render = function (mode, message, showSwitch) {
        var own = currentMode() === 'own';
        field.classList.toggle('is-valid', mode === 'ok');
        field.classList.toggle('is-invalid', mode === 'bad');
        state.textContent = mode === 'ok' ? '✓' : (mode === 'bad' ? '✕' : '');
        hint.classList.toggle('is-ok', mode === 'ok');

        // The Check button is spent once the domain is confirmed; editing the box brings it back.
        checkBtn.disabled = mode === 'checking' || (own && mode === 'ok');

        var enabled = own && mode === 'ok';
        continueBtn.style.display = own ? '' : 'none';
        continueBtn.disabled = !enabled;
        continueBtn.setAttribute('aria-disabled', enabled ? 'false' : 'true');

        showError(mode === 'bad' ? message : '');
        if (mode === 'ok' && own) {
            hint.innerHTML = 'This plan will be connected to <strong>' + escapeHtml(clean(input.value)) + '</strong>';
        } else if (mode === 'checking') {
            hint.textContent = currentMode() === 'register' ? 'Searching…' : 'Checking the domain…';
        } else {
            hint.textContent = '';
        }

        switchLink.hidden = !showSwitch;
    };

    /* ------------------------------------------------------------ requests */

    var postJson = function (url, payload) {
        var send = function () {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': currentToken()
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            }).then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (body) {
                    return { status: response.status, body: body };
                });
            });
        };

        // 419 = the page's security token no longer matches the session. Get a fresh
        // token from the server and try once more instead of showing "CSRF token mismatch".
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
            }).catch(function () { /* the retry reports the failure */ });
        };

        return send().then(function (result) {
            if (result.status !== 419) { return result; }
            return refreshToken().then(send);
        });
    };

    var commonFailure = function (result) {
        if (result.status === 419) { return 'This page has expired. Please reload it (Ctrl+F5) and try again.'; }
        if (result.status === 429) { return 'Too many attempts. Please wait a minute and try again.'; }
        return null;
    };

    /* ------------------------------------------------------------ own: Check */

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
        render('checking');

        postJson(checkUrl, { domain: typed, mode: 'own' }).then(function (result) {
            if (mine !== requestId) { return; }   // the field changed while we waited
            var failure = commonFailure(result);
            if (failure) {
                verifiedValue = null;
                render('bad', failure);
            } else if (result.body && result.body.ok === true) {
                verifiedValue = typed;
                render('ok');
            } else if (result.body && result.body.message) {
                verifiedValue = null;
                // A free name while "Use My Domain" is chosen: offer the right option, not a detour.
                render('bad', result.body.message, result.body.state === 'available');
            } else {
                verifiedValue = null;
                render('bad', 'We could not verify this domain right now. Please try again in a moment.');
            }
        }).catch(function () {
            if (mine !== requestId) { return; }
            verifiedValue = null;
            render('bad', 'We could not verify this domain right now. Please try again in a moment.');
        }).then(function () {
            if (mine === requestId) { busy = false; }
        });
    };

    /* ------------------------------------------------------ register: Search */

    var submitRegister = function (domain, years) {
        var yearsInput = form.querySelector('input[name="years"]');
        if (!yearsInput) {
            yearsInput = document.createElement('input');
            yearsInput.type = 'hidden';
            yearsInput.name = 'years';
            form.appendChild(yearsInput);
        }
        yearsInput.value = String(years || 1);
        input.value = domain;
        if (modeSelect) { modeSelect.value = 'register'; }
        form.submit();   // native submit: the server re-checks the name, then opens the domain checkout
    };

    var el = function (tag, className, text) {
        var node = document.createElement(tag);
        if (className) { node.className = className; }
        if (text !== undefined) { node.textContent = text; }
        return node;
    };

    var renderRow = function (row) {
        var wrap = el('div', 'host-result' + (row.available === false ? ' is-registered' : ''));
        var badge = el('div', 'host-result-status ' + (row.available ? 'is-available' : 'is-taken'),
            row.available ? '✓' : (row.available === null ? '?' : '×'));
        var info = el('div', 'host-result-name');
        info.appendChild(el('strong', '', row.domain));

        if (row.available === true) {
            info.appendChild(el('small', '', 'Available to register'));
        } else if (row.available === false) {
            var note = el('small', '', row.domain + ' is already registered. If it is yours, choose “Use My Domain”.');
            info.appendChild(note);
        } else {
            info.appendChild(el('small', '', 'Could not check availability — please try again'));
        }
        wrap.appendChild(badge);
        wrap.appendChild(info);

        if (row.available === true) {
            var terms = row.year_prices || {};
            var years = Object.keys(terms).map(Number).sort(function (a, b) { return a - b; });
            if (!years.length) { years = [1]; terms = { 1: row.price }; }

            var price = el('div', 'host-result-price');
            var priceStrong = el('strong');
            var amount = el('span', 'host-result-amount');
            var period = el('span', 'host-result-period');
            priceStrong.appendChild(amount);
            priceStrong.appendChild(period);
            var renews = el('small');
            price.appendChild(priceStrong);
            price.appendChild(renews);

            var select = null;
            if (years.length > 1) {
                select = el('select', 'host-result-term');
                select.setAttribute('aria-label', 'Registration term for ' + row.domain);
                years.forEach(function (y) {
                    var opt = el('option', '', y + ' year' + (y > 1 ? 's' : '') + ' · ৳ ' + money(terms[y]));
                    opt.value = String(y);
                    select.appendChild(opt);
                });
            }

            var paint = function () {
                var y = select ? Number(select.value) : years[0];
                amount.textContent = '৳ ' + money(terms[y]);
                period.textContent = y === 1 ? ' /first year' : ' /' + y + ' years';
                renews.textContent = y === 1
                    ? 'Renews ৳ ' + money(row.renewal) + '/year'
                    : 'First year ৳ ' + money(row.price) + ' · renews ৳ ' + money(row.renewal) + '/year';
            };
            paint();
            if (select) { select.addEventListener('change', paint); }

            var buy = el('button', 'host-result-buy', (isCustomer ? 'Add to cart' : 'Purchase now') + ' +');
            buy.type = 'button';
            buy.addEventListener('click', function () {
                buy.disabled = true;
                submitRegister(row.domain, select ? Number(select.value) : years[0]);
            });

            wrap.appendChild(price);
            var actions = el('div', 'host-result-actions');
            if (select) { actions.appendChild(select); }
            actions.appendChild(buy);
            wrap.appendChild(actions);
        } else if (row.available === false) {
            var use = el('button', 'host-result-use', 'Use My Domain');
            use.type = 'button';
            use.addEventListener('click', function () { switchToOwn(row.domain); });
            var side = el('div', 'host-result-actions');
            side.appendChild(use);
            wrap.appendChild(side);
        }

        return wrap;
    };

    var runSearch = function () {
        if (busy) { return; }

        var name = clean(input.value).replace(/^www\./, '');
        if (name === '' || !namePattern.test(name)) {
            clearResults();
            render('bad', 'Enter a valid domain name, like mynewbrand or mynewbrand.com.');
            return;
        }

        busy = true;
        var mine = ++requestId;
        clearResults();
        render('checking');

        postJson(searchUrl, { domain: input.value.trim() }).then(function (result) {
            if (mine !== requestId) { return; }
            var failure = commonFailure(result);
            if (failure) {
                render('bad', failure);
            } else if (result.body && result.body.ok === true && Array.isArray(result.body.results)) {
                render('idle');
                if (!result.body.results.length) {
                    render('bad', 'No domains were found for this search. Try another name.');
                    return;
                }
                result.body.results.forEach(function (row) { resultsBox.appendChild(renderRow(row)); });
                resultsBox.hidden = false;
            } else {
                render('bad', (result.body && result.body.message) || 'We could not search right now. Please try again in a moment.');
            }
        }).catch(function () {
            if (mine !== requestId) { return; }
            render('bad', 'We could not search right now. Please try again in a moment.');
        }).then(function () {
            if (mine === requestId) { busy = false; }
        });
    };

    /* ---------------------------------------------------- transfer: Validate */

    var runTransfer = function () {
        var name = clean(input.value);
        if (name === '' || !pattern.test(name)) {
            render('bad', 'Enter a valid domain name, like example.com.');
            return;
        }
        checkBtn.disabled = true;
        input.value = name;
        form.submit();   // the server remembers the plan and opens the Domain Transfer page with this name
    };

    /* ------------------------------------------------------------- modes */

    var run = function () {
        var m = currentMode();
        if (m === 'register') { runSearch(); }
        else if (m === 'transfer') { runTransfer(); }
        else { runCheck(); }
    };

    var applyMode = function () {
        var m = currentMode();
        var meta = modes[m] || {};
        requestId++;
        busy = false;
        verifiedValue = null;
        input.setAttribute('placeholder', meta.placeholder || '');
        checkBtn.textContent = meta.button || 'Check';
        clearResults();
        paintCards();
        render('idle');
    };

    var switchToOwn = function (domain) {
        if (modeSelect) { modeSelect.value = 'own'; }
        applyMode();
        if (domain) { input.value = domain; runCheck(); }
    };

    var cards = Array.prototype.slice.call(document.querySelectorAll('[data-mode-card]'));
    var paintCards = function () {
        cards.forEach(function (card) {
            var on = card.getAttribute('data-mode-card') === currentMode();
            card.classList.toggle('is-active', on);
            card.setAttribute('aria-checked', on ? 'true' : 'false');
        });
    };
    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            if (!modeSelect || modeSelect.value === card.getAttribute('data-mode-card')) { input.focus(); return; }
            modeSelect.value = card.getAttribute('data-mode-card');
            applyMode();
            input.focus();
        });
    });
    if (modeSelect) { modeSelect.addEventListener('change', applyMode); }

    switchLink.querySelector('a').addEventListener('click', function (event) {
        event.preventDefault();
        if (modeSelect) { modeSelect.value = 'register'; }
        applyMode();
        if (input.value.trim() !== '') { runSearch(); }
    });

    // Any edit takes the tick away (own) and re-enables the button: the new text has not been checked.
    input.addEventListener('input', function () {
        requestId++;           // ignore an answer that is still on its way
        busy = false;
        verifiedValue = null;
        if (currentMode() === 'register') { clearResults(); }
        render('idle');
    });

    checkBtn.addEventListener('click', run);

    // Enter runs the button; it never submits an unchecked form.
    input.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter') { return; }
        event.preventDefault();
        if (currentMode() === 'own' && ownVerified()) { form.submit(); return; }
        if (!checkBtn.disabled) { run(); }
    });

    // Only Continue to Cart submits the form by itself, and only for a checked own domain.
    form.addEventListener('submit', function (event) {
        if (!ownVerified()) {
            event.preventDefault();
            input.focus();
        }
    });

    // Reopened with a domain that already passed for this plan: keep the tick.
    var serverError = errorBox.textContent.replace(/\s+/g, ' ').trim();
    if (form.getAttribute('data-verified') === '1' && input.value.trim() !== '' && currentMode() === 'own') {
        verifiedValue = input.value.trim();
        render('ok');
    } else if (serverError !== '') {
        // The server refused the submitted domain (it re-checks): show its reason.
        render('bad', serverError);
    } else {
        render('idle');
    }
})();
