<?php
/* Domain card fact strip: label and value on ONE line per cell
   ("REGISTERED on 06 Nov 2026"), applied through a modifier so the service
   pages that use secondary notes stay stacked. */

/* ------------------------------------------------------------- blade ---- */
$bladePath = __DIR__ . '/../resources/views/store/domains.blade.php';
$blade = str_replace("\r\n", "\n", file_get_contents($bladePath));

$oldBlock = <<<'HTML'
    <div class="store-facts">
        <div class="store-fact">
            <small>REGISTERED</small>
            <strong>{{ $domain['registered_at'] ? $domain['registered_at']->format('d M Y') : '—' }}</strong>
        </div>
        <div class="store-fact">
            <small>EXPIRES</small>
            <strong>{{ $domain['expires_at'] ? $domain['expires_at']->format('d M Y') : '—' }}</strong>
        </div>
        <div class="store-fact">
            <small>RENEWAL </small>
            <strong>৳ {{ number_format($domain['renewal_price'], 2) }}<i>/year</i></strong>
        </div>
    </div>
HTML;

$newBlock = <<<'HTML'
    <div class="store-facts store-facts--inline">
        <div class="store-fact">
            <small>REGISTERED</small>
            @if($domain['registered_at'])
            <span class="store-fact-join">on</span>
            @endif
            <strong>{{ $domain['registered_at'] ? $domain['registered_at']->format('d M Y') : '—' }}</strong>
        </div>
        <div class="store-fact">
            <small>EXPIRES</small>
            @if($domain['expires_at'])
            <span class="store-fact-join">on</span>
            @endif
            <strong>{{ $domain['expires_at'] ? $domain['expires_at']->format('d M Y') : '—' }}</strong>
        </div>
        <div class="store-fact">
            <small>RENEWAL</small>
            <strong>৳ {{ number_format($domain['renewal_price'], 2) }}<i>/year</i></strong>
        </div>
    </div>
HTML;

if (strpos($blade, $oldBlock) === false) {
    fwrite(STDERR, "domain facts block not found\n");
    exit(1);
}
$blade = str_replace($oldBlock, $newBlock, $blade);

/* the transfer card on the same page uses the strip too */
$blade = str_replace(
    '<div class="store-facts store-facts--transfer">',
    '<div class="store-facts store-facts--transfer store-facts--inline">',
    $blade
);

file_put_contents($bladePath, str_replace("\n", "\r\n", $blade));
echo "blade updated\n";

/* --------------------------------------------------------------- css ---- */
$cssPath = __DIR__ . '/../public/css/store.css';
$css = str_replace("\r\n", "\n", file_get_contents($cssPath));

$css = rtrim($css) . "\n" . <<<'CSS'

/* --- Inline fact strip: one "LABEL → value" line per cell ---------------
   Dot-points under a value ("paid on", "2 year term") keep the stacked
   layout; the domain cards only ever hold one value per cell, so they put
   the label and the value on the same baseline instead. */
.store-facts--inline .store-fact{
  display:flex;
  flex-wrap:wrap;
  align-items:baseline;
  gap:0 8px;
  padding:15px 22px 16px;
}
.store-facts--inline .store-fact small{
  flex:none;
  color:#8ea2b9;
  font-size:10.5px;
  font-weight:800;
  letter-spacing:1.15px;
}
.store-facts--inline .store-fact-join{
  flex:none;
  margin-left:-2px;
  color:#a9bccf;
  font:600 12.5px 'DM Sans';
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
@media(max-width:760px){
  .store-facts--inline .store-fact{padding:14px 18px 15px;gap:0 7px}
  .store-facts--inline .store-fact strong{font-size:16px}
}
CSS;

file_put_contents($cssPath, str_replace("\n", "\r\n", $css));
echo "css appended\n";
