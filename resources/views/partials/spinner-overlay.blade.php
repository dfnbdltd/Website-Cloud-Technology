{{--
    Early-paint copy of the spinner overlay.

    The overlay used to be created by site-spinner.js, which is loaded with
    `defer` — so during a full page navigation the new document painted first
    and the loader appeared only afterwards: spinner, then a white screen, then
    the spinner again. Emitting the overlay in the markup means it already
    exists at first paint.

    This partial must be included immediately after the opening <body> tag of
    any page that can be the target of a data-spinner form, so document.body
    exists when the inline script below runs.

    The inline script only ever activates the overlay when a fresh handoff
    token is present; on a normal visit the overlay stays hidden.
--}}
<div class="sspin-overlay" id="sspin-overlay" role="status" aria-live="assertive" aria-busy="false" hidden>
    <div class="loader" aria-hidden="true"></div>
    <span class="sspin-status"></span>
    <span class="sspin-sr" aria-live="polite">0 percent</span>
</div>
<script>
    (function () {
        var HANDOFF_KEY = 'sspin-handoff';
        // Same freshness window as site-spinner.js: an older token is a
        // back/refresh artefact and must not paint an overlay.
        var FRESH_MS = 8000;

        try {
            var raw = window.sessionStorage.getItem(HANDOFF_KEY);
            if (!raw) {
                return;
            }
            var data = JSON.parse(raw);
            if (!data || !data.t || Date.now() - data.t > FRESH_MS) {
                return;
            }

            var overlay = document.getElementById('sspin-overlay');
            if (!overlay) {
                return;
            }

            // Paint on the very first frame of this document. `sspin-early`
            // switches the opacity transition off, so the overlay is solid
            // immediately instead of fading in from a white page.
            overlay.hidden = false;
            overlay.classList.add('is-active', 'sspin-early');
            overlay.setAttribute('aria-busy', 'true');

            var status = overlay.querySelector('.sspin-status');
            if (status) {
                status.textContent = data.label || 'Loading…';
            }
            if (document.body) {
                document.body.classList.add('sspin-lock');
            }

            // Safety net: if site-spinner.js never runs (blocked/failed), do
            // not leave a blocking overlay on top of a perfectly good page.
            window.setTimeout(function () {
                if (window.SiteSpinner) {
                    return; // the real script took over and already dismissed it
                }
                var node = document.getElementById('sspin-overlay');
                if (node && !node.hidden) {
                    node.hidden = true;
                    node.classList.remove('is-active', 'sspin-early');
                    node.setAttribute('aria-busy', 'false');
                }
                if (document.body) {
                    document.body.classList.remove('sspin-lock');
                }
            }, 6000);
        } catch (e) {
            /* sessionStorage unavailable — behave as a normal visit */
        }
    })();
</script>
