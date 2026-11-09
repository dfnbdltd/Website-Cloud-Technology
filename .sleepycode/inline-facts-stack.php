<?php
/* Move the inline strip to its single-column stack a little earlier, so a
   narrow tablet (761-820px) never squeezes three "LABEL on value" cells into
   ~150px each. */
$path = __DIR__ . '/../public/css/store.css';
$s = str_replace("\r\n", "\n", file_get_contents($path));

$old = "@media(max-width:1000px){\n  .store-facts--inline .store-fact{padding:14px 18px 15px;gap:0 7px}\n  .store-facts--inline .store-fact small{font-size:10px;letter-spacing:1px}\n  .store-facts--inline .store-fact strong{font-size:16px}\n  .store-facts--inline .store-fact strong i{font-size:11px}\n}\n@media(max-width:760px){\n  .store-facts--inline{grid-template-columns:1fr}\n}";

$new = "@media(max-width:1000px){\n  .store-facts--inline .store-fact{padding:14px 18px 15px;gap:0 7px}\n  .store-facts--inline .store-fact small{font-size:10px;letter-spacing:1px}\n  .store-facts--inline .store-fact strong{font-size:16px}\n  .store-facts--inline .store-fact strong i{font-size:11px}\n}\n@media(max-width:860px){\n  .store-facts--inline{grid-template-columns:1fr}\n}";

if (strpos($s, $old) === false) {
    fwrite(STDERR, "stack rule not found\n");
    exit(1);
}

file_put_contents($path, str_replace("\n", "\r\n", str_replace($old, $new, $s)));
echo "stack breakpoint set to 860px\n";
