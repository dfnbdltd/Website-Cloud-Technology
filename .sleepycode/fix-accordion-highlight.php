<?php
// TEMPORARY helper (deleted after use): fix the "closed group keeps the navy
// pill" bug in the sidebar accordion.
function rep($file, $old, $new, $label)
{
    $p = __DIR__ . '/../' . $file;
    $c = file_get_contents($p);

    foreach (["\r\n", "\n"] as $eol) {
        $a = str_replace("\n", $eol, $old);
        if (substr_count($c, $a) === 1) {
            $c = str_replace($a, str_replace("\n", $eol, $new), $c);
            echo "ok {$label} (eol=" . ($eol === "\n" ? 'LF' : 'CRLF') . ")\n";
            file_put_contents($p, $c);

            return;
        }
    }

    fwrite(STDERR, "FAIL {$label}\n");
    exit(1);
}

rep('resources/views/partials/sidebar.blade.php', <<<'PHP'
    /**
     * Open or close one group.
     *
     * The parent of the page you are on keeps its highlight while its list is
     * collapsed — the highlight marks location, not expansion.
     */
    function setGroup(trigger, open, reveal) {
        var submenu = document.getElementById(trigger.getAttribute('aria-controls'));
        if (!submenu) { return; }

        submenu.classList.toggle('open', open);
        trigger.classList.toggle('open', open);
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        trigger.classList.toggle('active', open || trigger.getAttribute('data-current') === 'true');
PHP, <<<'PHP'
    /**
     * Open or close one group.
     *
     * Only the OPEN group wears the navy pill: that fill is the state of the
     * list, so two groups must never wear it at once. The group that merely
     * contains the page you are on keeps a soft tint (`is-current`) while it
     * is collapsed — enough to say "you are here", not enough to be mistaken
     * for an open group (My Store used to stay navy after Web Hosting took
     * over, which read as two open groups).
     */
    function setGroup(trigger, open, reveal) {
        var submenu = document.getElementById(trigger.getAttribute('aria-controls'));
        if (!submenu) { return; }

        submenu.classList.toggle('open', open);
        trigger.classList.toggle('open', open);
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        trigger.classList.toggle('active', open);
        trigger.classList.toggle('is-current', trigger.getAttribute('data-current') === 'true');
PHP, 'setGroup');

rep('resources/views/partials/sidebar.blade.php', <<<'PHP'
    function openTriggers() {
        return sidebar.querySelectorAll('nav > a[aria-controls][aria-expanded="true"]');
    }
PHP, <<<'PHP'
    function openTriggers() {
        return sidebar.querySelectorAll('nav > a[aria-controls][aria-expanded="true"]');
    }

    // Start from the state the server rendered, with the same rules the click
    // handler uses: navy = open, soft tint = the page you are on.
    [].slice.call(sidebar.querySelectorAll('nav > a[aria-controls]')).forEach(function (trigger) {
        var open = trigger.getAttribute('aria-expanded') === 'true';
        trigger.classList.toggle('active', open);
        trigger.classList.toggle('is-current', trigger.getAttribute('data-current') === 'true');
    });
PHP, 'init sync');
