<?php
/* Keep the three-up row down to the phone stack break (760px): the measured
   single-line need at the compact rhythm (~196px) still fits a ~230px cell,
   and below that the flex box wraps the value onto its own line rather than
   overflowing. */
$path = __DIR__ . '/../public/css/store.css';
$s = str_replace("\r\n", "\n", file_get_contents($path));

$old = "@media(max-width:860px){\n  .store-facts--inline{grid-template-columns:1fr}\n}";
$new = "@media(max-width:760px){\n  .store-facts--inline{grid-template-columns:1fr}\n}";

if (strpos($s, $old) === false) {
    fwrite(STDERR, "860 rule not found\n");
    exit(1);
}

file_put_contents($path, str_replace("\n", "\r\n", str_replace($old, $new, $s)));
echo "stack breakpoint back to 760px\n";
