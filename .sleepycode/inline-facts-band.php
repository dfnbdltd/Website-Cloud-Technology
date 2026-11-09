<?php
/* The 900-1150px band is the only place the three-up row gets too tight: the
   sidebar is still docked, so each cell would fall under ~230px and the value
   would drop onto its own line (exactly the look we are removing). There the
   strip becomes two-up with the renewal cell spanning both columns, which keeps
   every "LABEL on value" pair on a single line.
   - <=900px the sidebar is an off-canvas drawer, so the card is full width and
     three-up fits again.
   - <=760px it stacks. */
$path = __DIR__ . '/../public/css/store.css';
$s = str_replace("\r\n", "\n", file_get_contents($path));

$old = "@media(max-width:1100px){\n  .store-facts--inline{grid-template-columns:repeat(3,minmax(0,1fr))}\n}\n";

$new = "@media(min-width:901px) and (max-width:1150px){\n  .store-facts--inline{grid-template-columns:repeat(2,minmax(0,1fr))}\n  .store-facts--inline:not(.store-facts--transfer) .store-fact:nth-child(3){grid-column:1 / -1}\n}\n";

if (strpos($s, $old) === false) {
    fwrite(STDERR, "1100 three-up rule not found\n");
    exit(1);
}

file_put_contents($path, str_replace("\n", "\r\n", str_replace($old, $new, $s)));
echo "two-up band added\n";
