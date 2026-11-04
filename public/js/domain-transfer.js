/* ==========================================================================
 * CloudTech — domain transfer form.
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
    var modal = page.querySelector('[data-dt-modal]');
    var modalDomain = page.querySelector('[data-dt-modal-domain]');
    var transferForm = page.querySelector('[data-dt-transfer-form]');
    var transferDomain = page.querySelector('[data-dt-transfer-domain]');
    var authInput = page.querySelector('[data-dt-auth]');
    var authError = page.querySelector('[data-dt-auth-error]');

    if (!form || !domainInput || !modal || !transferForm || !transferDomain) {
        return;
    }

    var lastFocused = null;
    var shakeAnimation = null;
    var INVALID_MESSAGE = 'The domain you entered is not valid';

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
        openModal(domain);
    });

    /* ---------------------------------------------------------------------
     * Authorization Code dialog
     * ------------------------------------------------------------------- */

    function openModal(domain) {
        lastFocused = document.activeElement;

        modalDomain.textContent = domain;
        transferDomain.value = domain;
        authInput.value = '';
        authError.hidden = true;
        authInput.classList.remove('is-invalid');

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

        if (!code) {
            authInput.classList.add('is-invalid');
            authError.hidden = false;
            authInput.focus();
            return;
        }

        authInput.value = code;
        transferForm.submit();
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

    transferForm.addEventListener('submit', function (event) {
        event.preventDefault();
        confirmTransfer();
    });

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
