<?php
// TEMPORARY helper (deleted after use): align the CSS comment with what the
// JS actually sets (aria-current="true").
$path = __DIR__ . '/../public/css/sidebar-nav.css';
$css = str_replace("\r\n", "\n", file_get_contents($path));

$old = '"You are here" is kept in the markup (aria-current="page" on the group row,';
$new = '"You are here" is kept in the markup (aria-current="true" on the group row,';

if (substr_count($css, $old) !== 1) {
    fwrite(STDERR, "FAIL comment\n");
    exit(1);
}

$css = str_replace($old, $new, $css);
file_put_contents($path, str_replace("\n", "\r\n", $css));
echo "patched\n";
