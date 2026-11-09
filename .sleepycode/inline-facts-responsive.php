<?php
/* The generic .store-facts rule collapses to 2 columns at <=1100px, which
   leaves the renewal cell alone on a second row. The inline variant is much
   shorter now, so let it size itself: as many "LABEL on value" cells per row as
   actually fit, never below one. */
$path = __DIR__ . '/../public/css/store.css';
$s = str_replace("\r\n", "\n", file_get_contents($path));

$old = <<<'CSS'
@media(max-width:760px){
  .store-facts--inline .store-fact{padding:14px 18px 15px;gap:0 7px}
  .store-facts--inline .store-fact strong{font-size:16px}
}
CSS;

$new = <<<'CSS'
@media(max-width:1100px){
  .store-facts--inline{grid-template-columns:repeat(auto-fit,minmax(250px,1fr))}
}
@media(max-width:760px){
  .store-facts--inline{grid-template-columns:1fr}
  .store-facts--inline .store-fact{padding:14px 18px 15px;gap:0 7px}
  .store-facts--inline .store-fact strong{font-size:16px}
}
CSS;

if (strpos($s, $old) === false) {
    fwrite(STDERR, "inline media block not found\n");
    exit(1);
}

file_put_contents($path, str_replace("\n", "\r\n", str_replace($old, $new, $s)));
echo "responsive rule added\n";
