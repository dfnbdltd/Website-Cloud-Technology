(function () {
    var checks = [
        { label: '8+ characters', test: function (value) { return value.length >= 8; } },
        { label: 'Lowercase letter', test: function (value) { return /[a-z]/.test(value); } },
        { label: 'Uppercase letter', test: function (value) { return /[A-Z]/.test(value); } },
        { label: 'Number', test: function (value) { return /\d/.test(value); } },
        { label: 'Special character', test: function (value) { return /[^A-Za-z0-9]/.test(value); } }
    ];

    function addPasswordChecks(form) {
        if (form.dataset.passwordStrengthInitialized === 'true') {
            return;
        }

        var password = form.querySelector('input[name="password"]');
        var confirmation = form.querySelector('input[name="password_confirmation"]');
        if (!password || !confirmation) {
            return;
        }
        form.dataset.passwordStrengthInitialized = 'true';

        var panel = document.createElement('section');
        panel.className = 'password-strength';
        panel.setAttribute('aria-live', 'polite');

        var header = document.createElement('div');
        header.className = 'password-strength-header';
        var title = document.createElement('strong');
        title.textContent = 'Password strength';
        var status = document.createElement('span');
        status.className = 'password-strength-status';
        status.textContent = 'Enter a password';
        header.appendChild(title);
        header.appendChild(status);
        panel.appendChild(header);

        var meter = document.createElement('div');
        meter.className = 'password-strength-meter';
        meter.setAttribute('aria-hidden', 'true');
        var meterFill = document.createElement('span');
        meter.appendChild(meterFill);
        panel.appendChild(meter);

        var list = document.createElement('ul');
        list.className = 'password-strength-checks';
        var items = checks.map(function (check) {
            var item = document.createElement('li');
            item.textContent = check.label;
            list.appendChild(item);
            return item;
        });
        panel.appendChild(list);
        password.parentNode.appendChild(panel);

        var matchStatus = document.createElement('small');
        matchStatus.className = 'password-match-status';
        matchStatus.setAttribute('aria-live', 'polite');
        confirmation.parentNode.appendChild(matchStatus);

        function update() {
            var value = password.value;
            var metCount = 0;

            checks.forEach(function (check, index) {
                var met = check.test(value);
                items[index].classList.toggle('is-met', met);
                if (met) {
                    metCount++;
                }
            });

            meterFill.style.width = (metCount / checks.length * 100) + '%';
            meterFill.dataset.strength = metCount === checks.length
                ? 'strong'
                : (metCount >= 3 ? 'medium' : 'weak');
            status.textContent = value.length === 0
                ? 'Enter a password'
                : (metCount === checks.length ? 'Strong' : 'Add missing requirements');

            var matches = confirmation.value.length > 0 && confirmation.value === value;
            matchStatus.textContent = confirmation.value.length === 0
                ? ''
                : (matches ? 'Passwords match' : 'Passwords do not match');
            matchStatus.classList.toggle('is-matched', matches);
            matchStatus.classList.toggle('is-mismatched', confirmation.value.length > 0 && !matches);
        }

        password.addEventListener('input', update);
        confirmation.addEventListener('input', update);
        update();
    }

    function initialize() {
        document.querySelectorAll('form[data-password-strength]').forEach(addPasswordChecks);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
}());
