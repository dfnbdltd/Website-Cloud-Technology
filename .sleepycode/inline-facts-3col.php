<?php
/* Keep the three inline facts in ONE row as long as the card allows it.
   The generic .store-facts rule drops to 2 columns at <=1100px, which strands
   the renewal cell on its own row; the inline strip is a single line per cell,
   so it keeps three equal columns until the card is too narrow for them. */
$path = __DIR__ . '/../public/css/store.css';
$s = str_replace("\r\n", "\n", file_get_contents($path));

$old = "@media(max-width:1100px){\n  .store-facts--inline{grid-template-columns:repeat(auto-fit,minmax(250px,1fr))}\n}";
$new = "@media(max-width:1100px){\n  .store-facts--inline{grid-template-columns:repeat(3,minmax(0,1fr))}\n}";

if (strpos($s, $old) === false) {
    fwrite(STDERR, "auto-fit rule not found\n");
    exit(1);
}

file_put_contents($path, str_replace("\n", "\r\n", str_replace($old, $new, $s)));
echo "three-column rule applied\n";
