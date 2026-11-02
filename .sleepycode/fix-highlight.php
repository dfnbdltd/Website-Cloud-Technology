<?php
// TEMPORARY helper (deleted after use): one group, one highlight.
//
// The navy pill must mean exactly one thing — "this group is open". The bug:
// the group that merely CONTAINED the current page also kept a fill while
// collapsed, so opening Web Hosting left two rows looking active. Even the
// soft tint that replaced it still read as "blue left behind", so nothing is
// painted on a closed group at all now; location is carried by aria-current
// (accessible, invisible) and by the highlighted child row inside the group.
function patch($file, array $pairs)
{
    $path = __DIR__ . '/../' . $file;
    $content = file_get_contents($path);
    $crlf = strpos($content, "\r\n") !== false;
    $content = str_replace("\r\n", "\n", $content);

    foreach ($pairs as $label => $pair) {
        list($old, $new) = $pair;
        $count = substr_count($content, $old);
        if ($count !== 1) {
            fwrite(STDERR, "FAIL ({$count}x) {$label}\n");
            exit(1);
        }
        $content = str_replace($old, $new, $content);
        echo "ok {$label}\n";
    }

    if ($crlf) {
        $content = str_replace("\n", "\r\n", $content);
    }

    file_put_contents($path, $content);
}

// ── 1. The JS: active === open, aria-current === "you are here", one helper
//       that always enforces the accordion (clicks AND deep links). ─────────
patch('resources/views/partials/sidebar.blade.php', [
    'setGroup + openOnly' => [
<<<'OLD'
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

        if (open && reveal && submenu.scrollIntoView) {
            window.requestAnimationFrame(function () {
                submenu.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
            });
        }
    }
OLD,
<<<'NEW'
    /**
     * Open or close one group.
     *
     * The navy pill means exactly one thing: THIS GROUP IS OPEN. So `active`
     * is bound to `open` and nothing else — a group that is merely the page
     * you are on gets no fill while collapsed, otherwise opening Web Hosting
     * left My Store looking active too (two rows claiming to be open at once).
     * Location is carried by aria-current, which is invisible, and by the
     * highlighted child row inside the group once it is open.
     */
    function setGroup(trigger, open, reveal) {
        var submenu = document.getElementById(trigger.getAttribute('aria-controls'));
        if (!submenu) { return; }

        submenu.classList.toggle('open', open);
        trigger.classList.toggle('open', open);
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        trigger.classList.toggle('active', open);
        markCurrent(trigger);

        if (open && reveal && submenu.scrollIntoView) {
            window.requestAnimationFrame(function () {
                submenu.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
            });
        }
    }

    /** "You are here" for assistive tech; it paints nothing. */
    function markCurrent(trigger) {
        if (trigger.getAttribute('data-current') === 'true') {
            trigger.setAttribute('aria-current', 'true');
        } else {
            trigger.removeAttribute('aria-current');
        }
    }

    /**
     * Open one group and close every other, in that order. The click handler
     * and the deep-link pass both go through here, so a programmatic open can
     * never leave two navy pills behind.
     */
    function openOnly(trigger, reveal) {
        openTriggers().forEach(function (other) {
            if (other !== trigger) { setGroup(other, false, false); }
        });
        setGroup(trigger, true, reveal);
    }
NEW,
    ],
    'initial normalisation' => [
<<<'OLD'
    // Start from the state the server rendered, with the same rules the click
    // handler uses: navy = open, soft tint = the page you are on.
    [].slice.call(sidebar.querySelectorAll('nav > a[aria-controls]')).forEach(function (trigger) {
        var open = trigger.getAttribute('aria-expanded') === 'true';
        trigger.classList.toggle('active', open);
        trigger.classList.toggle('is-current', trigger.getAttribute('data-current') === 'true');
    });
OLD,
<<<'NEW'
    // Start from the state the server rendered, with the same rules the click
    // handler uses: navy = open, nothing at all = closed.
    [].slice.call(sidebar.querySelectorAll('nav > a[aria-controls]')).forEach(function (trigger) {
        setGroup(trigger, trigger.getAttribute('aria-expanded') === 'true', false);
    });
NEW,
    ],
    'click handler' => [
<<<'OLD'
        var open = trigger.getAttribute('aria-expanded') !== 'true';

        // Accordion: only one group stays open. Opening Web Hosting closes
        // Domains first, so the list never grows under the pointer.
        if (open) {
            openTriggers().forEach(function (other) {
                if (other !== trigger) { setGroup(other, false, false); }
            });
        }

        setGroup(trigger, open, true);
OLD,
<<<'NEW'
        // Accordion: only one group stays open. Opening Web Hosting closes
        // Domains first, so the list never grows under the pointer — and the
        // closed group drops its navy pill in the same pass.
        if (trigger.getAttribute('aria-expanded') !== 'true') {
            openOnly(trigger, true);
            return;
        }

        setGroup(trigger, false, false);
NEW,
    ],
    'deep link open' => [
<<<'OLD'
        if (group && group.hasAttribute('aria-controls') && group.getAttribute('aria-expanded') !== 'true') {
            setGroup(group, true, false);
        }
OLD,
<<<'NEW'
        if (group && group.hasAttribute('aria-controls') && group.getAttribute('aria-expanded') !== 'true') {
            openOnly(group, false);
        }
NEW,
    ],
]);

// ── 2. The CSS: delete the soft tint entirely. ──────────────────────────────
patch('public/css/sidebar-nav.css', [
    'is-current rules' => [
<<<'OLD'
/* ── Collapsed group that still owns the current page ─────────────────────
   The navy pill means ONE thing now: this group is open. Before this, the
   group holding the current page also kept the pill while collapsed, so
   opening Web Hosting left BOTH Web Hosting and My Store navy — two rows
   claiming to be open at once. The closed-but-current group now keeps a soft
   tint instead (enough to say "you are here", clearly not an open group) and
   its +/− control stays dark, so the state of the list is never ambiguous.

   Scoping: id + .is-current + :not(.open) outranks domains.css's
   `.service-group-start:not(.open)` reset that used to strip the fill from
   the Domains row only. */
#clientSidebar:not(.collapsed) nav > a.is-current:not(.open) {
    background: #eaf2fb;
    border-color: #d6e6f6;
    color: #1b5aa0;
    box-shadow: none;
}

#clientSidebar:not(.collapsed) nav > a.is-current:not(.open):hover {
    background: #e1edfa;
    border-color: #c8ddf2;
    color: #164b87;
}

/* the bars stay dark on the soft tint (they are white only on the navy pill) */
#clientSidebar:not(.collapsed) nav > a.is-current:not(.open) .nav-toggle,
#clientSidebar:not(.collapsed) nav > a.is-current:not(.open):hover .nav-toggle {
    color: #3f5d7d;
}
OLD,
<<<'NEW'
/* ── A closed group is painted like any other row ─────────────────────────
   The navy pill means ONE thing: this group is open. The group that merely
   holds the current page used to keep the pill while collapsed, so opening
   Web Hosting left BOTH Web Hosting and My Store navy — two rows claiming to
   be open at once — and a paler replacement tint still read as "the blue
   stayed behind". So a collapsed group now carries no fill at all, not even
   for the page you are on: collapse is its own answer, and the +/− control
   already says whether the list is open.

   "You are here" is kept in the markup (aria-current="page" on the group row,
   an .active child row inside the list) so nothing is lost for assistive tech
   or for the deep-link pass. domains.css's `.service-group-start:not(.open)`
   reset now agrees with this instead of fighting it. */
NEW,
    ],
]);
