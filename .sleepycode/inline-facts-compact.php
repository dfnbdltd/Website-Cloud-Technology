<?php
/* Three facts, one row, as long as the card allows it. Below ~1000px the cells
   get tight, so the strip switches to a compact rhythm instead of dropping to
   two columns (which used to strand the renewal cell on its own row). */
$path = __DIR__ . '/../public/css/store.css';
$s = str_replace("\r\n", "\n", file_get_contents($path));

$old = <<<'CSS'
@media(max-width:1100px){
  .store-facts--inline{grid-template-columns:repeat(3,minmax(0,1fr))}
}
@media(max-width:760px){
  .store-facts--inline{grid-template-columns:1fr}
  .store-facts--inline .store-fact{padding:14px 18px 15px;gap:0 7px}
  .store-facts--inline .store-fact strong{font-size:16px}
}
CSS;

$new = <<<'CSS'
@media(max-width:1100px){
  .store-facts--inline{grid-template-columns:repeat(3,minmax(0,1fr))}
}
@media(max-width:1000px){
  .store-facts--inline .store-fact{padding:14px 18px 15px;gap:0 7px}
  .store-facts--inline .store-fact small{font-size:10px;letter-spacing:1px}
  .store-facts--inline .store-fact strong{font-size:16px}
  .store-facts--inline .store-fact strong i{font-size:11px}
}
@media(max-width:760px){
  .store-facts--inline{grid-template-columns:1fr}
}
CSS;

if (strpos($s, $old) === false) {
    fwrite(STDERR, "compact media block not found\n");
    exit(1);
}

file_put_contents($path, str_replace("\n", "\r\n", str_replace($old, $new, $s)));
echo "compact rhythm applied\n";
