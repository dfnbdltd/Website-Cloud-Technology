/* ==========================================================================
 * CloudTech — domain transfer (front-end simulation)
 *
 * Everything here is local: the domain is validated in the browser and the
 * Authorization Code dialog mimics the registrar hand-off. No request is sent,
 * so the exact same file can be demoed locally and on the live site.
 *
 * Markup: resources/views/partials/domain-transfer.blade.php
 * Styles: public/css/domain-transfer.css
 * ========================================================================== */
(function (window, document) {
    'use strict';

    var page = document.querySelector('.dt-page');
    if (!page) {
        return;
    }

    var form = page.querySelector('[data-dt-form]');
    var domainInput = page.querySelector('[data-dt-domain]');
    var errorBox = page.querySelector('[data-dt-error]');
    var errorText = page.querySelector('[data-dt-error-text]');
    var notFoundBox = page.querySelector('[data-dt-not-found]');
    var notFoundDomain = page.querySelector('[data-dt-not-found-domain]');
    var retryButton = page.querySelector('[data-dt-retry]');
    var modal = page.querySelector('[data-dt-modal]');
    var formPane = page.querySelector('[data-dt-form-state]');
    var successPane = page.querySelector('[data-dt-success]');
    var modalDomain = page.querySelector('[data-dt-modal-domain]');
    var successDomain = page.querySelector('[data-dt-success-domain]');
    var authInput = page.querySelector('[data-dt-auth]');
    var authError = page.querySelector('[data-dt-auth-error]');
    var confirmBtn = page.querySelector('[data-dt-confirm]');
    var doneBtn = page.querySelector('[data-dt-done]');

    if (!form || !domainInput || !modal) {
        return;
    }

    var lastFocused = null;
    var shakeAnimation = null;
    var INVALID_MESSAGE = 'The domain you entered is not valid';
    var DEMO_DOMAIN = 'test.com';

    /* ---------------------------------------------------------------------
     * Domain validation
     * ------------------------------------------------------------------- */

    /** Trim, lowercase and drop scheme / path / trailing dots from pasted URLs. */
    function normalize(raw) {
        var value = String(raw || '').trim().toLowerCase();
        value = value.replace(/^[a-z][a-z0-9+.-]*:\/\//, '');
        value = value.replace(/^\/\//, '');
        value = value.split(/[/?#]/)[0];
        value = value.replace(/\.+$/, '');
        return value;
    }

    /** A real domain: at least two labels, a sane TLD, no leading/trailing hyphen. */
    function isValidDomain(value) {
        if (!value || value.length > 253) {
            return false;
        }

        var labels = value.split('.');
        if (labels.length < 2) {
            return false;
        }

        var tld = labels[labels.length - 1];
        if (!/^[a-z]{2,63}$/.test(tld) && !/^xn--[a-z0-9-]{2,59}$/.test(tld)) {
            return false;
        }

        for (var i = 0; i < labels.length; i++) {
            var label = labels[i];
            if (!label || label.length > 63) {
                return false;
            }
            if (!/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/.test(label)) {
                return false;
            }
        }

        return true;
    }

    function showDomainError(message) {
        errorText.textContent = message || INVALID_MESSAGE;
        errorBox.hidden = false;

        form.classList.add('is-invalid');
        domainInput.setAttribute('aria-invalid', 'true');
        shakeSearch();
    }

    function clearDomainError() {
        errorBox.hidden = true;
        form.classList.remove('is-invalid');
        domainInput.removeAttribute('aria-invalid');

        if (shakeAnimation) {
            shakeAnimation.cancel();
            shakeAnimation = null;
        }

    }

    function hideNotFound() {
        if (notFoundBox) {
            notFoundBox.hidden = true;
        }
    }

    function showNotFound(domain) {
        if (!notFoundBox) {
            return;
        }

        notFoundDomain.textContent = domain;
        notFoundBox.hidden = false;
        notFoundBox.classList.remove('is-visible');
        window.requestAnimationFrame(function () {
            notFoundBox.classList.add('is-visible');
        });
    }

    /* Shake the bar with the Web Animations API rather than a CSS class:
       toggling `animation` on the form would restart its entrance animation
       and briefly fade it out. */
    function shakeSearch() {
        if (typeof form.animate !== 'function') {
            return;
        }
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }
        if (shakeAnimation) {
            shakeAnimation.cancel();
        }
        shakeAnimation = form.animate([
            { transform: 'translateX(0)' },
            { transform: 'translateX(-5px)' },
            { transform: 'translateX(4px)' },
            { transform: 'translateX(-3px)' },
            { transform: 'translateX(2px)' },
            { transform: 'translateX(0)' }
        ], {
            duration: 380,
            easing: 'ease'
        });
    }

    domainInput.addEventListener('input', clearDomainError);

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        var domain = normalize(domainInput.value);
        domainInput.value = domain;

        if (!isValidDomain(domain)) {
            showDomainError(INVALID_MESSAGE);
            domainInput.focus();
            return;
        }

        clearDomainError();
        hideNotFound();

        if (domain === DEMO_DOMAIN) {
            openModal(domain);
            return;
        }

        showNotFound(domain);
    });

    /* ---------------------------------------------------------------------
     * Authorization Code dialog
     * ------------------------------------------------------------------- */

    function openModal(domain) {
        lastFocused = document.activeElement;

        modalDomain.textContent = domain;
        authInput.value = '';
        authError.hidden = true;
        authInput.classList.remove('is-invalid');

        formPane.hidden = false;
        successPane.hidden = true;
        modal.hidden = false;
        document.body.classList.add('dt-modal-open');

        // Wait for the open animation before stealing focus, so the entrance
        // is not interrupted by the mobile keyboard jumping in.
        window.setTimeout(function () {
            authInput.focus();
        }, 80);
    }

    function closeModal() {
        if (modal.hidden) {
            return;
        }

        modal.hidden = true;
        document.body.classList.remove('dt-modal-open');

        if (lastFocused && typeof lastFocused.focus === 'function') {
            lastFocused.focus({ preventScroll: true });
        }
    }

    function confirmTransfer() {
        var code = String(authInput.value || '').trim();

        if (code.length < 4) {
            authInput.classList.add('is-invalid');
            authError.hidden = false;
            authInput.focus();
            return;
        }

        successDomain.textContent = modalDomain.textContent;
        formPane.hidden = true;
        successPane.hidden = false;

        window.setTimeout(function () {
            doneBtn.focus();
        }, 60);
    }

    authInput.addEventListener('input', function () {
        authError.hidden = true;
        authInput.classList.remove('is-invalid');
    });

    authInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            confirmTransfer();
        }
    });

    confirmBtn.addEventListener('click', confirmTransfer);
    doneBtn.addEventListener('click', function () {
        closeModal();
        domainInput.focus();
    });

    if (retryButton) {
        retryButton.addEventListener('click', function () {
            hideNotFound();
            domainInput.value = '';
            domainInput.focus();
        });
    }

    page.querySelectorAll('[data-dt-close]').forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    // Click the dimmed backdrop (but not the dialog itself) to dismiss.
    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (modal.hidden) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal();
            return;
        }

        // Keep keyboard focus inside the dialog while it is open.
        if (event.key !== 'Tab') {
            return;
        }

        var focusable = modal.querySelectorAll('button, input');
        if (!focusable.length) {
            return;
        }

        var first = focusable[0];
        var last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
}(window, document));
