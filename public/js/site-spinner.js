/* ==========================================================================
 * CloudTech — shared loading spinner (site-spinner)
 * Fullscreen dark overlay containing a single dot loader. There is no card,
 * no percentage and no progress bar on screen; the counter still runs
 * internally so progress()/finish() keep their exact behaviour.
 * See public/css/site-spinner.css.
 *
 * API:
 *   SiteSpinner.show({ title, theme, inline, host }) -> void
 *   SiteSpinner.progress(0-100 | null)  // null = auto-simulate
 *   SiteSpinner.finish(ms)              // animate to 100%, then hide
 *   SiteSpinner.hide()                  // hide immediately
 *   SiteSpinner.isVisible()             // bool
 * ========================================================================== */
(function (window, document) {
    'use strict';

    var OVERLAY_ID = 'sspin-overlay';
    var SELECTOR = '.sspin-overlay';

    var overlay = null;
    var els = null;
    var rafId = null;
    var finishTimer = null;
    var hideTimer = null;
    // Identifies the run of the finish animation so a newer show() can cancel
    // the older ramp instead of letting it paint over the fresh counter.
    var finishToken = 0;
    var value = 0;
    var auto = true;
    var lastTick = 0;

    function ensureOverlay() {
        if (overlay && document.body.contains(overlay)) {
            return overlay;
        }
        overlay = document.getElementById(OVERLAY_ID) || document.querySelector(SELECTOR);
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = OVERLAY_ID;
            overlay.className = 'sspin-overlay';
            overlay.setAttribute('role', 'status');
            overlay.setAttribute('aria-live', 'assertive');
            overlay.setAttribute('aria-busy', 'true');
            overlay.hidden = true;
            // Only the loader is visible. The status text is a screen-reader
            // only live region, so nothing is drawn next to the loader.
            overlay.innerHTML =
                '<div class="loader" aria-hidden="true"></div>' +
                '<span class="sspin-status"></span>' +
                '<span class="sspin-sr" aria-live="polite">0 percent</span>';
            (document.body || document.documentElement).appendChild(overlay);
        } else if (!overlay.querySelector('.loader')) {
            // An overlay found in the DOM may predate the loader markup (stale
            // page, cached partial). Rebuild its contents so the animation
            // always exists instead of silently showing an empty box.
            overlay.innerHTML =
                '<div class="loader" aria-hidden="true"></div>' +
                '<span class="sspin-status"></span>' +
                '<span class="sspin-sr" aria-live="polite">0 percent</span>';
        }
        els = {
            status: overlay.querySelector('.sspin-status'),
            sr: overlay.querySelector('.sspin-sr')
        };
        return overlay;
    }

    function paint(pct) {
        if (!els) {
            return;
        }
        var shown = Math.max(0, Math.min(100, Math.round(pct)));
        // No visual counter any more — only the polite live region is updated,
        // so assistive tech still hears the progress of the handoff.
        if (els.sr) {
            els.sr.textContent = shown + ' percent';
        }
    }

    function tick(now) {
        rafId = null;
        // Keep the loop alive while the overlay is open even if the entrance
        // transition has not applied .is-active yet (it is added two frames
        // after show()). Bailing out here used to kill the loop permanently,
        // so every show() after the first one stayed frozen at 0%.
        if (!overlay || overlay.hidden) {
            return;
        }
        if (auto) {
            if (!lastTick) {
                lastTick = now;
            }
            var dt = Math.min(now - lastTick, 120);
            lastTick = now;
            // Slowing pace: fast start, gentle crawl so it never hits 100 early.
            var speed = 14 * Math.exp(-value / 45) + 1.6;
            value = Math.min(94, value + (speed * dt) / 1000);
            paint(value);
        }
        rafId = window.requestAnimationFrame(tick);
    }

    function startLoop() {
        // Always (re)arm from a clean slate: a stale handle from a previous
        // open used to make this call a no-op and freeze the counter.
        if (rafId !== null) {
            window.cancelAnimationFrame(rafId);
        }
        rafId = null;
        lastTick = 0;
        rafId = window.requestAnimationFrame(tick);
    }

    function stopLoop() {
        if (rafId !== null) {
            window.cancelAnimationFrame(rafId);
            rafId = null;
        }
    }

    function show(opts) {
        opts = opts || {};
        ensureOverlay();
        window.clearTimeout(finishTimer);
        // Cancel a pending hide so a quick re-open is never pulled back down,
        // and invalidate any in-flight finish ramp.
        window.clearTimeout(hideTimer);
        finishToken++;
        value = typeof opts.startAt === 'number' ? opts.startAt : 0;
        auto = opts.progress !== false;
        paint(value);

        if (els && els.status) {
            if (opts.title) {
                els.status.textContent = opts.title;
            } else if (typeof opts.title !== 'string') {
                els.status.textContent = 'Loading…';
            }
        }
        overlay.classList.toggle('sspin--light', opts.theme === 'light');
        overlay.classList.toggle('sspin--inline', !!opts.inline);
        if (opts.inline && opts.host) {
            opts.host.appendChild(overlay);
            if (!opts.host.classList.contains('sspin-host')) {
                opts.host.classList.add('sspin-host');
            }
        } else if (overlay.parentNode !== document.body) {
            document.body.appendChild(overlay);
        }

        overlay.hidden = false;
        // Reset the simulated counter on every open, then start the loop once
        // the entrance transition has actually applied .is-active.
        if (auto) {
            value = typeof opts.startAt === 'number' ? opts.startAt : value;
        }
        // Double rAF so the transition from [hidden] -> visible animates.
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () {
                overlay.classList.add('is-active');
                startLoop();
            });
        });
        document.body.classList.add('sspin-lock');
        overlay.setAttribute('aria-busy', 'true');
    }

    function progress(pct) {
        ensureOverlay();
        if (pct === null || typeof pct === 'undefined') {
            auto = true;
            return;
        }
        auto = false;
        value = Math.max(0, Math.min(100, Number(pct) || 0));
        paint(value);
    }

    function finish(holdMs) {
        if (!overlay) {
            return;
        }
        stopLoop();
        auto = false;
        var from = value;
        var t0 = performance.now();
        var dur = 450;
        var thisToken = ++finishToken;
        function stepFin(now) {
            // A new show() during the finish animation must win: stop painting
            // this ramp so the fresh 0% counter is not yanked back to 100%.
            if (finishToken !== thisToken) {
                return;
            }
            var t = Math.min(1, (now - t0) / dur);
            var eased = 1 - Math.pow(1 - t, 3);
            paint(from + (100 - from) * eased);
            if (t < 1) {
                window.requestAnimationFrame(stepFin);
            }
        }
        window.requestAnimationFrame(stepFin);
        window.clearTimeout(finishTimer);
        finishTimer = window.setTimeout(hide, (holdMs || 350) + dur);
    }

    function hide() {
        window.clearTimeout(finishTimer);
        stopLoop();
        if (!overlay) {
            return;
        }
        overlay.classList.remove('is-active');
        // Drop the no-transition flag added by the early-paint script, so a
        // later show() fades in normally again.
        overlay.classList.remove('sspin-early');
        document.body.classList.remove('sspin-lock');
        overlay.setAttribute('aria-busy', 'false');
        // Track this timer: an untracked one used to fire *after* a new show()
        // and hid the freshly opened overlay, which read as a second popup.
        window.clearTimeout(hideTimer);
        var node = overlay;
        hideTimer = window.setTimeout(function () {
            node.hidden = true;
        }, 240);
    }

    function isVisible() {
        return !!(overlay && !overlay.hidden);
    }

    /* --- Handoff across full-page navigations (search POST -> redirect) ---
     * A form marked data-spinner="search" shows the overlay immediately and
     * stores a token; the results page picks it up, shows a short "finishing"
     * pass to 100%, and fades out. */
    var HANDOFF_KEY = 'sspin-handoff';

    function armHandoff(label) {
        try {
            window.sessionStorage.setItem(HANDOFF_KEY, JSON.stringify({
                label: label || 'Loading…',
                t: Date.now()
            }));
        } catch (e) { /* storage unavailable — overlay still shows on submit */ }
    }

    function consumeHandoff() {
        try {
            var raw = window.sessionStorage.getItem(HANDOFF_KEY);
            if (!raw) {
                return null;
            }
            window.sessionStorage.removeItem(HANDOFF_KEY);
            var data = JSON.parse(raw);
            if (!data || !data.t || Date.now() - data.t > 8000) {
                return null; // stale (e.g. back/refresh) — ignore
            }
            return data;
        } catch (e) {
            return null;
        }
    }

    var booted = false;

    function boot() {
        // boot() can be reachable from both DOMContentLoaded and the
        // readyState !== 'loading' branch on cached/slow loads. Wiring the
        // forms twice made one submit show the overlay twice.
        if (booted) {
            return;
        }
        booted = true;

        // 1) Resume after a navigation handoff.
        //    The overlay is painted by partials/spinner-overlay as soon as the
        //    new document's <body> opens, so there is no white gap between the
        //    old page and this one.
        var handoff = consumeHandoff();
        if (handoff) {
            // Only touch the overlay when this page actually pre-rendered it.
            // Pages without the include would otherwise flash an overlay that
            // was created here and dismissed straight away.
            var preRendered = document.getElementById(OVERLAY_ID);
            if (preRendered && !preRendered.hidden) {
                ensureOverlay();
                if (els && els.status) {
                    els.status.textContent = handoff.label || 'Loading…';
                }
                // There is no visible percentage to animate any more, so the
                // old 450ms count-to-100 ramp only kept a blocking overlay over
                // a finished page. Release immediately instead.
                value = 100;
                paint(value);
                hide();
            }
            // Fall through: still wire this page's own forms so a second
            // search straight from the results page gets the spinner too.
        }

        // 2) Wire every form marked data-spinner to show the overlay on submit.
        var forms = document.querySelectorAll('form[data-spinner]');
        for (var i = 0; i < forms.length; i++) {
            (function (form) {
                form.addEventListener('submit', function () {
                    var label = form.dataset.spinner === 'search' ? 'Searching…' : 'Loading…';
                    armHandoff(label);
                    show({ title: label });
                });
            })(forms[i]);
        }

        // 3) bfcache: never show a stale overlay when navigating back.
        window.addEventListener('pageshow', function (event) {
            if (event.persisted && isVisible()) {
                value = 0;
                auto = true;
                hide();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    window.SiteSpinner = {
        show: show,
        progress: progress,
        finish: finish,
        hide: hide,
        isVisible: isVisible
    };
})(window, document);
