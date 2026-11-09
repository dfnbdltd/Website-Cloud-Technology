
document.querySelectorAll('.store-ns-edit').forEach(function(panel) {
    var view = panel.querySelector('.store-ns-view');
    var form = panel.querySelector('.store-nameserver-form');
    var inputList = form.querySelector('.store-ns-input-list');
    var close = panel.querySelector('.store-ns-modal-close');
    var cancel = panel.querySelector('.store-ns-cancel');
    var pencils = panel.querySelectorAll('.store-ns-pencil');
    var add = panel.querySelector('.store-ns-add');
    var submit = panel.querySelector('.store-ns-submit');
    var initialValues = [];
    var savedValues = [];

    function createInputRow(value, index) {
        var row = document.createElement('div');
        row.className = 'store-ns-input-row';
        row.innerHTML = '<div class="store-ns-field">' +
            '<input type="text" value="" placeholder="ns' + (index + 1) +
            '.example.com" aria-label="Nameserver ' + (index + 1) + '" required>' +
            '<span class="store-ns-field-icon" aria-hidden="true">' +
            '<svg class="store-ns-icon-ok" viewBox="0 0 24 24"><path d="m6.5 12.5 4 4 7-8"></path></svg>' +
            '<svg class="store-ns-icon-bad" viewBox="0 0 24 24"><path d="M12 7.5v6.2"></path><path d="M12 17.2h.01"></path></svg>' +
            '</span><small class="store-ns-field-msg"></small></div>' +
            (index < 2 ? '<span class="store-ns-required" aria-hidden="true">*</span>' : '') +
            (index >= 2 ? '<button type="button" class="store-ns-remove" aria-label="Remove nameserver">' +
                '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"></path></svg>' +
                '</button>' : '');
        row.querySelector('input').value = value || '';
        return row;
    }

    function resetForm() {
        var values = savedValues.length ? savedValues : ['ns1.example.com', 'ns2.example.com'];
        inputList.innerHTML = '';
        values.forEach(function(value, index) {
            inputList.appendChild(createInputRow(value, index));
        });
        initialValues = values.slice();
        form.querySelector('.store-ns-submit-value').value = values.join('\n');
        form.dataset.submitting = '0';
        submit.classList.remove('is-loading');
        submit.removeAttribute('aria-busy');
        updateSubmitState();
    }

    function showEdit() {
        panel.classList.add('is-editing');
        initialValues = Array.prototype.map.call(form.querySelectorAll('.store-ns-input-row input'), function(
            input) {
            return input.value;
        });
        updateSubmitState();
    }

    function currentValues() {
        return Array.prototype.map.call(form.querySelectorAll('.store-ns-input-row input'), function(input) {
            return input.value.trim();
        });
    }

    /* Shared by the field validation and the button state, so a value can never
       be "red" on screen yet still look savable. */
    var hostnamePattern = /^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i;

    /* Lower-case + trimmed + sorted, so re-ordering rows or only changing the
       case of a hostname is not mistaken for a real change. */
    function normalise(list) {
        return list.map(function(value) {
            return String(value || '').trim().toLowerCase();
        }).filter(Boolean).sort();
    }

    function emptyInputs() {
        return Array.prototype.filter.call(
            form.querySelectorAll('.store-ns-input-row input'),
            function(input) {
                return !input.value.trim();
            }
        );
    }

    function hasDuplicates(values) {
        var filled = values.filter(Boolean);
        return new Set(normalise(filled)).size !== filled.length;
    }

    /* Why the button may not be pressed. Empty string means "go ahead". */
    function submitBlockReason() {
        var values = currentValues();

        if (!values.length) {
            return 'Add at least two nameservers.';
        }

        var empties = emptyInputs();
        if (empties.length) {
            // Only rows three and up carry a remove button, so only promise
            // removal when every empty box really can be removed.
            var allRemovable = empties.every(function(input) {
                var row = input.closest('.store-ns-input-row');
                return row && row.querySelector('.store-ns-remove');
            });

            return allRemovable
                ? 'Fill in or remove the empty nameserver box.'
                : 'Fill in the empty nameserver box.';
        }

        var filled = values.filter(Boolean);
        if (filled.length < 2) {
            return 'Enter at least 2 nameservers so the domain stays reachable.';
        }

        if (values.some(function(value) {
                return !hostnamePattern.test(value);
            })) {
            return 'Use a valid hostname such as ns1.example.com.';
        }

        if (hasDuplicates(values)) {
            return 'Each nameserver must be different.';
        }

        if (normalise(values).join('\n') === normalise(initialValues).join('\n')) {
            return 'Change a nameserver to enable the update.';
        }

        return '';
    }

    function renderFormStatus(message) {
        var status = form.querySelector('.store-ns-form-status');
        if (!status) {
            return;
        }
        status.textContent = message || '';
        status.classList.toggle('is-bad', Boolean(message));
        status.classList.toggle('is-ok', !message);
    }

    function updateSubmitState() {
        var reason = submitBlockReason();
        submit.disabled = Boolean(reason);
        renderFormStatus(reason);
    }

    function validateNameserverInput(input, index) {
        var field = input.closest('.store-ns-field');
        var value = input.value.trim();
        var message = '';

        if (!value) {
            // Any empty box blocks saving, so flag every row — including the one
            // "+ Add Nameservers" just created, not only the first two.
            message = 'Nameserver is required. Please enter a nameserver like ns1.example.com';
        } else if (!hostnamePattern.test(value)) {
            message = '"' + value + '" is not a valid nameserver hostname. Use a format like ns1.example.com';
        } else if (currentValues().filter(function(other) {
                return other.toLowerCase() === value.toLowerCase();
            }).length > 1) {
            message = 'This nameserver is already listed above.';
        }

        field.classList.toggle('is-invalid', Boolean(message));
        field.classList.toggle('is-valid', Boolean(value) && !message);
        field.querySelector('.store-ns-field-msg').textContent = message;
        return !message;
    }

    function validateNameservers() {
        var valid = true;
        var inputs = form.querySelectorAll('.store-ns-input-row input');
        Array.prototype.forEach.call(inputs, function(input, index) {
            if (!validateNameserverInput(input, index)) {
                valid = false;
            }
        });
        return valid;
    }

    function clearFeedback() {
        panel.querySelectorAll('.store-ns-error-trigger').forEach(function(trigger) {
            var details = document.getElementById(trigger.getAttribute('aria-controls'));
            if (details) {
                details.hidden = true;
            }
        });
        panel.querySelectorAll('.store-ns-alert, .store-ns-error-actions').forEach(function(feedback) {
            feedback.remove();
        });
    }
    if (panel.classList.contains('is-editing')) {
        showEdit();
    }

    function closePanel() {
        clearFeedback();
        resetForm();
        panel.removeAttribute('open');
        panel.classList.remove('is-editing');
    }
    pencils.forEach(function(button) {
        button.addEventListener('click', showEdit);
    });
    /* Re-run row validation for every row so the red marks always match the
       disabled button (adding an empty row must paint that row red too). */
    function refreshAllValidation() {
        var inputs = inputList.querySelectorAll('.store-ns-input-row input');
        Array.prototype.forEach.call(inputs, function(input, index) {
            validateNameserverInput(input, index);
        });
    }

    form.addEventListener('input', function() {
        updateSubmitState();
        refreshAllValidation();
    });
    inputList.addEventListener('input', function(event) {
        if (event.target.matches('input')) {
            var inputs = inputList.querySelectorAll('.store-ns-input-row input');
            validateNameserverInput(event.target, Array.prototype.indexOf.call(inputs, event.target));
        }
    });
    close.addEventListener('click', closePanel);
    cancel.addEventListener('click', function() {
        resetForm();
        panel.classList.remove('is-editing');
    });
    panel.querySelector('.store-ns-modal-backdrop').addEventListener('click', closePanel);
    add.addEventListener('click', function() {
        var row = createInputRow('', inputList.querySelectorAll('.store-ns-input-row').length);
        inputList.appendChild(row);
        row.querySelector('input').addEventListener('input', updateSubmitState);
        updateSubmitState();
        refreshAllValidation();
        row.querySelector('input').focus();
    });
    inputList.addEventListener('click', function(event) {
        var remove = event.target.closest('.store-ns-remove');
        if (!remove) {
            return;
        }
        var row = remove.closest('.store-ns-input-row');
        if (row) {
            row.remove();
            updateSubmitState();
            refreshAllValidation();
        }
    });
    form.addEventListener('submit', function(event) {
        if (form.dataset.submitting === '1') {
            event.preventDefault();
            return;
        }
        if (!validateNameservers()) {
            event.preventDefault();
            form.dataset.submitting = '0';
            var firstInvalid = form.querySelector('.store-ns-field.is-invalid input');
            if (firstInvalid) {
                firstInvalid.focus();
            }
            return;
        }
        form.dataset.submitting = '1';
        form.querySelector('.store-ns-submit-value').value = Array.prototype.map.call(form
            .querySelectorAll('.store-ns-input-row input'),
            function(input) {
                return input.value.trim();
            }).filter(Boolean).join('\n');
        submit.classList.add('is-loading');
        submit.setAttribute('aria-busy', 'true');
    });
});

document.querySelectorAll('.store-ns-info-button').forEach(function(button) {
    var modal = document.getElementById(button.getAttribute('aria-controls'));
    var closers = modal.querySelectorAll('.store-ns-info-close');

    button.addEventListener('click', function() {
        modal.hidden = false;
    });

    document.querySelectorAll('.store-ns-error-trigger').forEach(function(button) {
        var modal = document.getElementById(button.getAttribute('aria-controls'));
        if (!modal) {
            return;
        }

        button.addEventListener('click', function() {
            modal.hidden = false;
        });
        modal.querySelectorAll('.store-ns-error-close').forEach(function(closer) {
            closer.addEventListener('click', function() {
                modal.hidden = true;
            });
        });
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && !modal.hidden) {
                modal.hidden = true;
            }
        });
    });
    closers.forEach(function(closer) {
        closer.addEventListener('click', function() {
            modal.hidden = true;
        });
    });
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && !modal.hidden) {
            modal.hidden = true;
        }
    });
});

document.querySelectorAll('.store-child-ns-trigger').forEach(function(button) {
    var modal = document.getElementById(button.getAttribute('aria-controls'));
    if (!modal) {
        return;
    }
    var rows = modal.querySelector('.store-child-rows');
    var template = rows ? rows.querySelector('.store-child-row') : null;

    function resetChildForm() {
        if (!rows || !template) {
            return;
        }

        var firstRow = template.cloneNode(true);
        firstRow.querySelectorAll('input').forEach(function(input) {
            input.value = '';
        });
        rows.innerHTML = '';
        rows.appendChild(firstRow);
    }

    function closeChild() {
        resetChildForm();
        modal.hidden = true;
    }

    button.addEventListener('click', function() {
        resetChildForm();
        modal.hidden = false;
    });

    modal.querySelectorAll('.store-child-close').forEach(function(closer) {
        closer.addEventListener('click', closeChild);
    });

    var addRow = modal.querySelector('.store-child-add');
    if (addRow && rows && template) {
        addRow.addEventListener('click', function() {
            var row = template.cloneNode(true);
            row.querySelectorAll('input').forEach(function(input) {
                input.value = '';
            });
            rows.appendChild(row);
            var first = row.querySelector('input');
            if (first) {
                first.focus();
            }
        });
    }

    modal.addEventListener('click', function(event) {
        var remove = event.target.closest('.store-child-remove');
        if (!remove) {
            return;
        }
        var row = remove.closest('.store-child-row');
        if (!row || !rows) {
            return;
        }
        if (rows.querySelectorAll('.store-child-row').length > 1) {
            row.remove();
        } else {
            row.querySelectorAll('input').forEach(function(input) {
                input.value = '';
            });
        }
    });

    var save = modal.querySelector('.store-child-save');
    if (save) {
        save.addEventListener('click', closeChild);
    }

    var detailsTrigger = modal.querySelector('.store-child-info-trigger');
    var detailsModal = detailsTrigger ? document.getElementById(detailsTrigger.getAttribute('aria-controls')) :
        null;

    document.addEventListener('keydown', function(event) {
        if (event.key !== 'Escape' || modal.hidden) {
            return;
        }
        if (detailsModal && !detailsModal.hidden) {
            return;
        }
        closeChild();
    });
});

document.querySelectorAll('.store-child-info-trigger[aria-controls^="child-ns-details-"]').forEach(function(button) {
    var modal = document.getElementById(button.getAttribute('aria-controls'));
    if (!modal) {
        return;
    }

    button.addEventListener('click', function() {
        modal.hidden = false;
    });

    modal.querySelectorAll('.store-child-details-close').forEach(function(closer) {
        closer.addEventListener('click', function() {
            modal.hidden = true;
        });
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && !modal.hidden) {
            modal.hidden = true;
        }
    });
});
