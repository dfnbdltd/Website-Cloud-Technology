<?php
$path = __DIR__ . '/../resources/views/partials/coming-soon-modal.blade.php';
$raw = file_get_contents($path);
$eol = (strpos($raw, "\r\n") !== false) ? "\r\n" : "\n";

$old = implode($eol, [
    "            var trigger=event.target.closest('.coming-soon-trigger');",
    "            if(!trigger){return;}",
    "            // Public links point at real /services pages now, so an anchor",
    "            // must navigate. Only in-dashboard buttons open the modal.",
    "            if(trigger.tagName==='A'){return;}",
    "            event.preventDefault();",
]);

$new = implode($eol, [
    "            var trigger=event.target.closest('.coming-soon-trigger');",
    "            if(!trigger){return;}",
    "            // .coming-soon-trigger is reserved for the client dashboard",
    "            // (sidebar rows and empty store sections). The public navbar",
    "            // uses .coming-soon-link for its real /services pages, so it",
    "            // never matches this selector and keeps navigating normally.",
    "            event.preventDefault();",
]);

if (strpos($raw, $old) === false) {
    echo "OLD NOT FOUND\n";
    exit(1);
}

$raw = str_replace($old, $new, $raw);
file_put_contents($path, $raw);
echo "guard removed\n";
