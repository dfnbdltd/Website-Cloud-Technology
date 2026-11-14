/* ==========================================================================
 * Adds link support to the shared site-spinner.
 *
 * site-spinner.js only ever wired <form data-spinner>. The admin sidebar has
 * no forms to submit — it is plain links — so a link that starts a slow
 * server round trip (the SSL plans list refetches ResellerClub pricing on
 * every load) had no way to show the same loader the domain search uses.
 *
 * Rather than fork the spinner, this file extends it: an element marked
 * `data-spinner-link` arms the identical sessionStorage handoff the search
 * form uses, so the destination page pre-paints the overlay from
 * partials.spinner-overlay and the loader survives the navigation exactly as
 * it does for a domain search.
 *
 * Contract (kept deliberately small):
 *   <a href="..." data-spinner-link data-spinner-title="Loading SSL plans…">
 *
 * Only same-tab, plain left-clicks are intercepted — ctrl/cmd/shift clicks and
 * middle clicks keep the browser's own behaviour, because the overlay may not
 * be on the destination (a new tab, a download) and a spinner that appears and
 * never leaves is worse than a moment of nothing.
 * ========================================================================== */
(function (window, document) {
    'use strict';

    var HANDOFF_KEY = 'sspin-handoff';

    function armHandoff(label) {
        try {
            window.sessionStorage.setItem(HANDOFF_KEY, JSON.stringify({
                label: label || 'Loading…',
                t: Date.now()
            }));
        } catch (e) { /* storage unavailable — the overlay still shows on navigation */ }
    }

    /** Is this a navigation we are allowed to dress up with the overlay? */
    function isPlainNavigation(event, link) {
        if (event.defaultPrevented) { return false; }
        if (event.button !== 0) { return false; }          // middle/right click
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) { return false; }
        if (link.target && link.target !== '_self') { return false; }
        if (link.hasAttribute('download')) { return false; }
        // A link that is already going somewhere else (JS handler, confirm
        // dialog that cancelled) must not have a spinner forced onto it.
        if (link.getAttribute('href') === '#') { return false; }
        return true;
    }

    function wire(link) {
        // Guard against double-wiring: site-spinner.js may boot more than once
        // on a cached page, and two handlers used to open the overlay twice.
        if (link.getAttribute('data-spinner-link-wired') === 'true') { return; }
        link.setAttribute('data-spinner-link-wired', 'true');

        link.addEventListener('click', function (event) {
            if (!isPlainNavigation(event, link)) { return; }

            var label = link.getAttribute('data-spinner-title') || 'Loading…';

            // Arm BEFORE navigating so the next document can paint at once.
            armHandoff(label);

            // The overlay may not be in this page's DOM (only pages that
            // include partials.spinner-overlay pre-render it). SiteSpinner
            // creates it on demand, so showing it is always safe.
            if (window.SiteSpinner && typeof window.SiteSpinner.show === 'function') {
                window.SiteSpinner.show({ title: label });
            }
        });
    }

    function boot() {
        var links = document.querySelectorAll('[data-spinner-link]');
        for (var i = 0; i < links.length; i++) {
            wire(links[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})(window, document);
