(function () {
    var panel = document.querySelector('.store-ns-panel');
    if (!panel) {
        return;
    }

    var edit = panel.querySelector('.store-ns-edit');
    var form = panel.querySelector('.store-nameserver-form');
    var submit = panel.querySelector('.store-ns-submit');
    var cancel = panel.querySelector('.store-ns-cancel');
    var closeButton = panel.querySelector('.store-ns-modal-close');
    var backdrop = panel.querySelector('.store-ns-modal-backdrop');
    var feedback = panel.querySelector('.store-ns-feedback');
    var alertBox = panel.querySelector('.store-ns-alert');
    var records = panel.querySelector('.store-ns-records');
    var stateChip = panel.querySelector('.store-ns-state');
    var view = panel.querySelector('.store-ns-view');
    var inputList = panel.querySelector('.store-ns-input-list');
    var addRowButton = panel.querySelector('.store-ns-add');
    var help = panel.querySelector('#nameserver-help-modal');
    var helpTrigger = panel.querySelector('.store-ns-details-trigger');
    var childModal = panel.querySelector('#child-ns-modal');
    var childDetailsModal = panel.querySelector('#child-ns-details-modal');

    if (!edit || !form || !submit || !inputList) {
        return;
    }

    var domain = panel.getAttribute('data-domain') || '';
    var submitLabel = submit.textContent.trim();
    var submitting = false;
    var initialValues = [];

    var pencilIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16.6 3.9a2.1 2.1 0 0 1 3 3L8 18.5l-4.2 1.1 1.1-4.2z"/><path d="M5.5 21.4h10"/></svg>';
    var okIcon = '<svg class="store-ns-record-ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke-width="1.5" opacity=".4"/><path d="m8.3 12.4 2.4 2.4 4.9-5.2"/></svg>';

    function inputRows() {
        return Array.prototype.slice.call(inputList.querySelectorAll('input'));
    }

    function values() {
        return inputRows().map(function (input) {
            return input.value.trim();
        });
    }

    function refreshSubmit() {
        var current = values();
        var dirty = current.join('\n') !== initialValues.join('\n');
        var incomplete = current.some(function (value) {
            return value === '';
        });

        submit.disabled = submitting || !dirty || incomplete;
    }

    function buildInputRow(host, index) {
        var row = document.createElement('div');
        row.className = 'store-ns-input-row';

        var tag = document.createElement('small');
        tag.textContent = 'NS' + (index + 1);

        var input = document.createElement('input');
        input.type = 'text';
        input.value = host || '';
        input.placeholder = 'ns' + (index + 1) + '.example.com';
        input.setAttribute('aria-label', 'Name server ' + (index + 1));
        input.required = true;

        row.appendChild(tag);
        row.appendChild(input);

        return row;
    }

    function renderInputs(list) {
        inputList.innerHTML = '';
        list.forEach(function (host, index) {
            inputList.appendChild(buildInputRow(host, index));
        });
    }

    function renderRecords(list) {
        if (!records) {
            return;
        }

        records.innerHTML = '';

        list.forEach(function (host, index) {
            var row = document.createElement('div');
            row.className = 'store-ns-record';

            var tag = document.createElement('small');
            tag.textContent = 'NS' + (index + 1);

            var name = document.createElement('span');
            name.textContent = host;

            row.appendChild(tag);
            row.appendChild(name);
            row.insertAdjacentHTML('beforeend', okIcon);
            records.appendChild(row);
        });
    }

    function renderView(list) {
        if (!view) {
            return;
        }

        view.innerHTML = '';

        if (!list.length) {
            var empty = document.createElement('p');
            empty.className = 'store-ns-view-empty';
            empty.textContent = 'No name servers are configured yet. Add them below to connect this domain to your hosting.';
            view.appendChild(empty);

            return;
        }

        var label = document.createElement('p');
        label.className = 'store-ns-view-label';
        label.textContent = 'Currently active';
        view.appendChild(label);

        list.forEach(function (host, index) {
            var row = document.createElement('div');
            row.className = 'store-ns-current-row';

            var tag = document.createElement('small');
            tag.textContent = 'NS' + (index + 1);

            var name = document.createElement('span');
            name.textContent = host;

            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'store-ns-pencil';
            button.setAttribute('aria-label', 'Edit nameserver ' + (index + 1));
            button.innerHTML = pencilIcon;
            button.addEventListener('click', openEditor);

            row.appendChild(tag);
            row.appendChild(name);
            row.appendChild(button);
            view.appendChild(row);
        });
    }

    function renderHelpDefaults(list) {
        if (!help) {
            return;
        }

        var listElement = help.querySelector('.store-ns-help-defaults ul');
        if (!listElement) {
            return;
        }

        listElement.innerHTML = '';

        if (!list.length) {
            var blank = document.createElement('li');
            blank.textContent = 'Not configured';
            listElement.appendChild(blank);

            return;
        }

        list.forEach(function (host) {
            var item = document.createElement('li');
            item.textContent = host;
            listElement.appendChild(item);
        });
    }

    function renderState(list) {
        if (!stateChip) {
            return;
        }

        stateChip.className = 'store-ns-state ' + (list.length ? 'is-live' : 'is-empty');
        stateChip.innerHTML = '<i aria-hidden="true"></i>' + (list.length ? list.length + ' active' : 'Not configured');
    }

    function showAlert(type, message) {
        if (!alertBox) {
            return;
        }

        alertBox.className = 'store-ns-alert ' + type;
        alertBox.textContent = message;
        alertBox.hidden = false;
    }

    function showFeedback(type, message) {
        if (!feedback) {
            return;
        }

        feedback.className = 'store-ns-feedback ' + type;
        feedback.textContent = message;
        feedback.hidden = false;
    }

    function clearFeedback() {
        if (!feedback) {
            return;
        }

        feedback.className = 'store-ns-feedback';
        feedback.textContent = '';
        feedback.hidden = true;
    }

    function setLoading(loading) {
        submitting = loading;
        panel.classList.toggle('is-submitting', loading);

        Array.prototype.forEach.call(form.querySelectorAll('input, textarea, button'), function (control) {
            if (control === submit) {
                return;
            }

            control.disabled = loading;
        });

        submit.disabled = loading;
        submit.setAttribute('aria-busy', loading ? 'true' : 'false');
        submit.innerHTML = loading
            ? '<span class="store-ns-spinner" aria-hidden="true"></span><span>Updating name servers...</span>'
            : submitLabel;
    }

    function openEditor() {
        edit.setAttribute('open', '');
        edit.classList.add('is-editing');
        clearFeedback();
        renderInputs(initialValues);
        refreshSubmit();

        window.setTimeout(function () {
            var first = inputList.querySelector('input');
            if (first) {
                first.focus();
                first.select();
            }
        }, 0);
    }

    function closeEditor() {
        edit.removeAttribute('open');
        edit.classList.remove('is-editing');
        clearFeedback();
        renderInputs(initialValues);
        refreshSubmit();
    }

    function applyNameservers(list) {
        initialValues = list;
        renderRecords(list);
        renderState(list);
        renderView(list);
        renderHelpDefaults(list);
        renderInputs(list);
        refreshSubmit();
    }

    initialValues = values();
    if (edit.classList.contains('is-editing')) {
        edit.setAttribute('open', '');
    }
    refreshSubmit();

    Array.prototype.forEach.call(panel.querySelectorAll('.store-ns-pencil'), function (button) {
        button.addEventListener('click', openEditor);
    });

    form.addEventListener('input', refreshSubmit);

    if (cancel) {
        cancel.addEventListener('click', closeEditor);
    }

    if (closeButton) {
        closeButton.addEventListener('click', closeEditor);
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeEditor);
    }

    if (addRowButton) {
        addRowButton.addEventListener('click', function () {
            var row = buildInputRow('', inputRows().length);
            inputList.appendChild(row);
            row.querySelector('input').focus();
            refreshSubmit();
        });
    }

    function alertFromResponse(responseText) {
        var responseDocument = new DOMParser().parseFromString(responseText, 'text/html');
        var alert = responseDocument.querySelector('.domain-alert.success, .domain-alert.error');

        return alert ? {
            type: alert.classList.contains('success') ? 'success' : 'error',
            message: alert.textContent.trim()
        } : null;
    }

    form.addEventListener('submit', function (event) {
        var saved = values().filter(Boolean);

        form.querySelector('.store-ns-submit-value').value = saved.join('\n');

        if (!window.fetch || !window.DOMParser) {
            return;
        }

        event.preventDefault();

        var body = new FormData(form);
        var pending = values();

        clearFeedback();
        setLoading(true);

        fetch(form.action, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                return response.text();
            })
            .then(function (responseText) {
                var result = alertFromResponse(responseText);

                if (!result) {
                    throw new Error('The server returned an unexpected response.');
                }

                setLoading(false);

                if (result.type === 'success') {
                    applyNameservers(pending);
                    edit.removeAttribute('open');
                    edit.classList.remove('is-editing');
                    showAlert('success', result.message);

                    return;
                }

                showFeedback('error', result.message);
                refreshSubmit();
            })
            .catch(function () {
                setLoading(false);
                showFeedback('error', 'We could not update your name servers right now. Please try again or contact support at +880 9613 003 001.');
                refreshSubmit();
            });
    });

    function openModal(modal) {
        if (!modal) {
            return;
        }

        modal.hidden = false;

        var firstInput = modal.querySelector('input');
        var focusTarget = firstInput || modal.querySelector('button');

        if (focusTarget) {
            window.setTimeout(function () {
                focusTarget.focus();
            }, 0);
        }
    }

    function closeModal(modal) {
        if (modal) {
            modal.hidden = true;
        }
    }

    var childTrigger = panel.querySelector('.store-child-ns-trigger');
    if (childTrigger) {
        childTrigger.addEventListener('click', function () {
            openModal(childModal);
        });
    }

    Array.prototype.forEach.call(panel.querySelectorAll('.store-child-ns-details-trigger'), function (trigger) {
        trigger.addEventListener('click', function () {
            openModal(childDetailsModal);
        });
    });

    if (helpTrigger) {
        helpTrigger.addEventListener('click', function () {
            openModal(help);
        });
    }

    [childModal, childDetailsModal, help].forEach(function (modal) {
        if (!modal) {
            return;
        }

        Array.prototype.forEach.call(modal.querySelectorAll('[data-child-ns-close], [data-ns-help-close], .store-child-ns-close, .store-child-ns-cancel, .store-ns-help-close'), function (button) {
            button.addEventListener('click', function () {
                closeModal(modal);
            });
        });
    });

    if (childModal) {
        var childRows = childModal.querySelector('.store-child-ns-rows');
        var addChildRow = childModal.querySelector('.store-child-ns-add');

        function updateChildRemoveButtons() {
            var rowButtons = childRows.querySelectorAll('.store-child-ns-remove');

            Array.prototype.forEach.call(rowButtons, function (button) {
                button.hidden = rowButtons.length < 2;
            });
        }

        if (addChildRow) {
            addChildRow.addEventListener('click', function () {
                var row = document.createElement('div');
                row.className = 'store-child-ns-row';
                row.innerHTML = '<input type="text" placeholder="Ex. ns' + (childRows.children.length + 1) + '.' + domain + '" aria-label="Child nameserver host name"><input type="text" placeholder="IP address" aria-label="Child nameserver IP address"><button type="button" class="store-child-ns-remove" aria-label="Remove child nameserver">×</button>';
                childRows.appendChild(row);
                updateChildRemoveButtons();
                row.querySelector('input').focus();
            });
        }

        childRows.addEventListener('click', function (event) {
            if (event.target.classList.contains('store-child-ns-remove')) {
                event.target.closest('.store-child-ns-row').remove();
                updateChildRemoveButtons();
            }
        });

        updateChildRemoveButtons();
    }

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        closeModal(childModal);
        closeModal(childDetailsModal);
        closeModal(help);

        if (edit.hasAttribute('open')) {
            closeEditor();
        }
    });
})();
