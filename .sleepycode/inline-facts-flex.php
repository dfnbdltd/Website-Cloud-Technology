<?php
/* Replace the grid + breakpoint chain with a self-sizing flex strip.
   Cells take exactly the width their "LABEL on value" line needs and share the
   leftover space; they only wrap when the card genuinely cannot fit them, and
   the 1px row/column gap keeps the original hairline dividers in both states.
   This removes the 901-1150px band workaround entirely. */
$path = __DIR__ . '/../public/css/store.css';
$s = str_replace("\r\n", "\n", file_get_contents($path));

$start = strpos($s, "/* --- Inline fact strip:");
if ($start === false) {
    fwrite(STDERR, "inline strip block not found\n");
    exit(1);
}

$block = <<<'CSS'
/* --- Inline fact strip: one "LABEL → value" line per cell ---------------
   The domain and transfer cards hold a single value per cell, so the label and
   the value sit on one baseline ("REGISTERED on 06 Nov 2026") instead of the
   stacked label/value look used where a cell also carries a note.
   Layout is content-sized flex: each cell takes exactly the width its line
   needs and shares whatever is left over, so nothing ever wraps mid-cell on a
   narrow card — the strip reflows a whole cell at a time instead. The 1px
   gap over a tinted background keeps the hairline dividers of .store-facts. */
.store-facts--inline{
  display:flex;
  flex-wrap:wrap;
}
.store-facts--inline .store-fact{
  display:flex;
  flex:1 1 auto;
  flex-wrap:wrap;
  align-items:baseline;
  gap:0 8px;
  min-width:max-content;
  padding:15px 24px 16px;
}
.store-facts--inline .store-fact small{
  flex:none;
  color:#5f768c;
  font-size:10.5px;
  font-weight:800;
  letter-spacing:1.15px;
}
.store-facts--inline .store-fact-join{
  flex:none;
  margin-left:-2px;
  color:#63798f;
  font:500 13px 'DM Sans';
  letter-spacing:.1px;
}
.store-facts--inline .store-fact strong{
  margin:0;
  font:800 17px 'Manrope';
  letter-spacing:-.25px;
  white-space:nowrap;
}
.store-facts--inline .store-fact strong i{
  margin-left:1px;
  font-size:11.5px;
  font-weight:700;
}
@media(max-width:1000px){
  .store-facts--inline .store-fact{padding:14px 18px 15px;gap:0 7px}
  .store-facts--inline .store-fact small{font-size:10px;letter-spacing:1px}
  .store-facts--inline .store-fact strong{font-size:16px}
  .store-facts--inline .store-fact strong i{font-size:11px}
}
@media(max-width:420px){
  .store-facts--inline .store-fact{padding:13px 16px 14px}
}
CSS;

$s = substr($s, 0, $start) . $block . "\n";

file_put_contents($path, str_replace("\n", "\r\n", $s));
echo "inline strip rebuilt with flex sizing\n";
