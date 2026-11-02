/* ==========================================================================
 * CloudTech — shared loading spinner (site-spinner)
 * Reusable fullscreen / inline progress overlay with a 0–100% counter and
 * "Searching…" / "Loading…" status states, styled to the site brand
 * (navy #17365e, accent #2168c1). See public/css/site-spinner.css.
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
            overlay.innerHTML =
                '<div class="sspin-card">' +
                '  <div class="sspin-orbit">' +
                '    <span class="sspin-ring" aria-hidden="true"></span>' +
                '    <span class="sspin-core"><span class="sspin-pct"><span class="sspin-num">0</span><i>%</i></span></span>' +
                '  </div>' +
                '  <p class="sspin-status">Loading…</p>' +
                '  <span class="sspin-bar" aria-hidden="true"><span class="sspin-bar-fill"></span></span>' +
                '  <span class="sspin-sr" aria-live="polite">0 percent</span>' +
                '</div>';
            (document.body || document.documentElement).appendChild(overlay);
        }
        els = {
            status: overlay.querySelector('.sspin-status'),

            num: overlay.querySelector('.sspin-num'),
            fill: overlay.querySelector('.sspin-bar-fill'),
            sr: overlay.querySelector('.sspin-sr'),
            card: overlay.querySelector('.sspin-card')
        };
        return overlay;
    }

    function paint(pct) {
        if (!els || !els.num) {
            return;
        }
        var shown = Math.max(0, Math.min(100, Math.round(pct)));
        els.num.textContent = String(shown);
        if (els.fill) {
            els.fill.style.width = shown + '%';
        }
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
        value = typeof opts.startAt === 'number' ? opts.startAt : 0;
        auto = opts.progress !== false;
        paint(value);

        if (opts.title) {
            els.status.textContent = opts.title;
        } else if (typeof opts.title !== 'string') {
            els.status.textContent = 'Loading…';
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
        function stepFin(now) {
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
        document.body.classList.remove('sspin-lock');
        overlay.setAttribute('aria-busy', 'false');
        var node = overlay;
        window.setTimeout(function () {
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

    function boot() {
        // 1) Resume after a navigation handoff.
        var handoff = consumeHandoff();
        if (handoff) {
            value = 86;
            show({
                title: handoff.label || 'Loading…',
                progress: false,
                startAt: 86
            });
            finish(350);
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
