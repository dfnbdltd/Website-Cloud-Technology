<?php
/* Below 900px the client sidebar is an off-canvas drawer, so the card spans the
   full viewport again and three one-line cells fit comfortably. */
$path = __DIR__ . '/../public/css/store.css';
$s = str_replace("\r\n", "\n", file_get_contents($path));

$anchor = "@media(max-width:1000px){\n  .store-facts--inline .store-fact{padding:14px 18px 15px;gap:0 7px}";

if (strpos($s, "@media(max-width:900px){\n  .store-facts--inline") !== false) {
    echo "already present\n";
    exit(0);
}
if (strpos($s, $anchor) === false) {
    fwrite(STDERR, "1000px anchor not found\n");
    exit(1);
}

$insert = "@media(max-width:900px){\n  .store-facts--inline{grid-template-columns:repeat(3,minmax(0,1fr))}\n}\n" . $anchor;

file_put_contents($path, str_replace("\n", "\r\n", str_replace($anchor, $insert, $s)));
echo "900px three-up rule added\n";
