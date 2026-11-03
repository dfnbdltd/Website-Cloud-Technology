<?php
/**
 * The dashboard modal must never intercept a real page link. Public navbar /
 * footer links now point at /services/{slug}; if any of them still carries the
 * .coming-soon-trigger class for styling, the modal would swallow the click.
 * Bail out for anchors so navigation always wins.
 */
$path = __DIR__ . '/../resources/views/partials/coming-soon-modal.blade.php';
$raw = file_get_contents($path);
$eol = (strpos($raw, "\r\n") !== false) ? "\r\n" : "\n";

$old = "            var trigger=event.target.closest('.coming-soon-trigger');" . $eol .
       "            if(!trigger){return;}" . $eol;

$new = "            var trigger=event.target.closest('.coming-soon-trigger');" . $eol .
       "            if(!trigger){return;}" . $eol .
       "            // Public links point at real /services pages now, so an anchor" . $eol .
       "            // must navigate. Only in-dashboard buttons open the modal." . $eol .
       "            if(trigger.tagName==='A'){return;}" . $eol;

if (strpos($raw, $old) === false) {
    echo "OLD NOT FOUND\n";
    exit(1);
}

$raw = str_replace($old, $new, $raw);
file_put_contents($path, $raw);
echo "modal guarded\n";
