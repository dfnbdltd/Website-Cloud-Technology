/* Hosting checkout: the Billing dropdown.
   Page: resources/views/hosting/checkout.blade.php

   Every sellable term of the plan is embedded as JSON (#hostTerms). Choosing one
   swaps the price label, price, Total due, plan name, PLAN LABEL and highlights
   without a reload, and tells the server (POST data-period-url) so session
   'hosting_cart' stays in sync. This is display only: Place order re-quotes the
   submitted term on the server and never reads a price from the browser. */
(function () {
    'use strict';

    var select = document.getElementById('hostPeriod');
    var summary = document.getElementById('hostSummary');
    var dataNode = document.getElementById('hostTerms');
    if (!select || !summary || !dataNode) { return; }

    var terms = {};
    try { terms = JSON.parse(dataNode.textContent) || {}; } catch (e) { return; }

    var el = function (id) { return document.getElementById(id); };
    var token = function () {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    };

    var apply = function (period) {
        var term = terms[period];
        if (!term) { return; }

        el('hostPlanName').textContent = term.name;
        el('hostPlanLabel').textContent = term.plan_label;
        el('hostPriceLabel').textContent = 'Price per ' + term.unit;
        el('hostPrice').textContent = term.price_text;
        el('hostTotal').textContent = term.total_text;
        var pay = el('hostPayAmount');
        if (pay) { pay.textContent = term.total_text; }

        var list = el('hostHighlights');
        list.innerHTML = '';
        (term.highlights || []).forEach(function (line) {
            var li = document.createElement('li');
            li.textContent = line;
            list.appendChild(li);
        });

        // The "Change" link keeps the chosen term when the buyer goes back to the domain step.
        var change = el('hostChangeLink');
        if (change) {
            try {
                var url = new URL(change.getAttribute('href'), window.location.origin);
                url.searchParams.set('period', period);
                change.setAttribute('href', url.pathname + url.search);
            } catch (e) { /* leave the link as it is */ }
        }
    };

    var sync = function (period) {
        var url = summary.getAttribute('data-period-url');
        if (!url || !window.fetch) { return; }
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token()
            },
            credentials: 'same-origin',
            body: JSON.stringify({ period: period })
        }).catch(function () { /* Place order sends the term again; nothing to do */ });
    };

    select.addEventListener('change', function () {
        apply(select.value);
        sync(select.value);
    });

    apply(select.value);
})();
