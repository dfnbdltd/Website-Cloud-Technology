<?php
/* Nameserver form: "Update Nameservers" must stay disabled whenever the form is
   not actually savable.

   Rolled-back bug: updateSubmitState() compared JSON of current vs initial
   values, so "+ Add Nameservers" (which appends an EMPTY row) made the arrays
   differ and switched the button ON with nothing typed.

   New rule — the button is enabled only when ALL of these hold:
     * every row has text (no empty box, mandatory or added)
     * at least 2 nameservers
     * every value is a valid hostname            -> otherwise it goes red
     * no duplicates (case-insensitive)           -> otherwise it goes red
     * the resulting list differs from the saved one
   Every blocked reason is printed in red under the button, and the offending
   fields get the existing .is-invalid red styling — so "red on screen" and
   "button disabled" are driven by the same function and cannot disagree. */

function readLf(string $path): string
{
    $s = file_get_contents($path);
    if ($s === false) {
        fwrite(STDERR, "cannot read {$path}\n");
        exit(1);
    }
    return str_replace("\r\n", "\n", $s);
}

function writeCrlf(string $path, string $s): void
{
    file_put_contents($path, str_replace("\n", "\r\n", $s));
}

/* ------------------------------------------------------------- 1. markup ---- */
$bladePath = __DIR__ . '/../resources/views/store/domain-details.blade.php';
$blade = readLf($bladePath);

$oldMarkup = <<<'HTML'
                                        <p class="store-ns-form-status" role="status" aria-live="polite"></p>
                                        <button type="button" class="store-ns-add">+ Add Nameservers</button>
                                        <div class="store-ns-form-actions">
                                            <button class="store-primary store-ns-submit" type="submit" disabled>Update
                                                Nameservers</button>
                                            <button type="button" class="store-ns-cancel">Cancel Changes</button>
                                        </div>
HTML;

$newMarkup = <<<'HTML'
                                        <button type="button" class="store-ns-add">+ Add Nameservers</button>
                                        <div class="store-ns-form-actions">
                                            <button class="store-primary store-ns-submit" type="submit" disabled>Update
                                                Nameservers</button>
                                            <button type="button" class="store-ns-cancel">Cancel Changes</button>
                                        </div>
                                        <p class="store-ns-form-status" role="status" aria-live="polite"></p>
HTML;

if (strpos($blade, $oldMarkup) === false) {
    fwrite(STDERR, "markup block not found\n");
    exit(1);
}
$blade = str_replace($oldMarkup, $newMarkup, $blade);

/* ----------------------------------------------------------- 2. JS logic ---- */
$oldJs = <<<'JS'
    function currentValues() {
        return Array.prototype.map.call(form.querySelectorAll('.store-ns-input-row input'), function(input) {
            return input.value;
        });
    }

    function updateSubmitState() {
        submit.disabled = JSON.stringify(currentValues()) === JSON.stringify(initialValues);
    }

    function validateNameserverInput(input, index) {
        var field = input.closest('.store-ns-field');
        var value = input.value.trim();
        var hostnamePattern = /^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i;
        var message = '';

        if (!value && index < 2) {
            message = 'Nameserver is required. Please enter a nameserver like ns1.example.com';
        } else if (value && !hostnamePattern.test(value)) {
            message = '"' + value + '" is not a valid nameserver hostname. Use a format like ns1.example.com';
        }
JS;

$newJs = <<<'JS'
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
JS;

if (strpos($blade, $oldJs) === false) {
    fwrite(STDERR, "JS block not found\n");
    exit(1);
}
$blade = str_replace($oldJs, $newJs, $blade);

/* Keep red styling in sync with the button: whenever the form is blocked by an
   empty/invalid/duplicate row, mark those fields red too. Runs after every
   input, add and remove. */
$oldHook = <<<'JS'
    form.addEventListener('input', updateSubmitState);
    inputList.addEventListener('input', function(event) {
        if (event.target.matches('input')) {
            var inputs = inputList.querySelectorAll('.store-ns-input-row input');
            validateNameserverInput(event.target, Array.prototype.indexOf.call(inputs, event.target));
        }
    });
JS;

$newHook = <<<'JS'
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
JS;

if (strpos($blade, $oldHook) === false) {
    fwrite(STDERR, "input hook not found\n");
    exit(1);
}
$blade = str_replace($oldHook, $newHook, $blade);

/* Adding a row must validate (paint) it immediately, and removing one must
   re-validate the rest. */
$oldAdd = <<<'JS'
    add.addEventListener('click', function() {
        var row = createInputRow('', inputList.querySelectorAll('.store-ns-input-row').length);
        inputList.appendChild(row);
        row.querySelector('input').addEventListener('input', updateSubmitState);
        updateSubmitState();
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
        }
    });
JS;

$newAdd = <<<'JS'
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
JS;

if (strpos($blade, $oldAdd) === false) {
    fwrite(STDERR, "add/remove block not found\n");
    exit(1);
}
$blade = str_replace($oldAdd, $newAdd, $blade);

writeCrlf($bladePath, $blade);
echo "blade updated\n";

/* ---------------------------------------------------------------- 3. CSS ---- */
$cssPath = __DIR__ . '/../public/css/store.css';
$css = readLf($cssPath);

$oldCss = <<<'CSS'
.store-ns-form-status{display:none;margin:16px auto 0;max-width:440px;font:700 12.5px/1.5 'DM Sans';text-align:center}
.store-ns-form-status.is-ok{display:block;color:#12855a}
.store-ns-form-status.is-bad{display:block;color:#c23b3b}
CSS;

$newCss = <<<'CSS'
/* Why the Update button is disabled. Sits directly under the button it belongs
   to, in red, so "red on screen" and "button inactive" always agree.
   The .store-nameserver-form scope beats the generic p rule at 11px. */
.store-ns-form-status{display:none;margin:0;max-width:100%;font:700 12.5px/1.45 'DM Sans';text-align:center}
.store-nameserver-form .store-ns-form-status{font-size:12.5px;margin:12px auto 0}
.store-ns-form-status.is-bad{display:block;color:#c23b3b}
.store-ns-form-status.is-ok{display:none}
CSS;

if (strpos($css, $oldCss) === false) {
    fwrite(STDERR, "status css not found\n");
    exit(1);
}
$css = str_replace($oldCss, $newCss, $css);

writeCrlf($cssPath, $css);
echo "css updated\n";
