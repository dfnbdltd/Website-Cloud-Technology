<?php
/* Same inline treatment on the Manage page: "REGISTERED on 06 Nov 2026"
   instead of a label stacked over the value. Four facts there, so the compact
   rhythm is slightly tighter than the list cards. */
$bladePath = __DIR__ . '/../resources/views/store/domain-details.blade.php';
$blade = str_replace("\r\n", "\n", file_get_contents($bladePath));

$old = <<<'HTML'
                <div class="store-detail-facts">
                    <div><small>REGISTERED</small><strong>{{ optional($order->created_at)->format('d M Y') ?: '—' }}</strong></div>
                    <div><small>EXPIRES</small><strong>{{ $order->created_at ? $order->created_at->copy()->addYears(max(1, (int) $item->years))->format('d M Y') : '—' }}</strong></div>
                    <div><small>RENEWAL PRICE</small><strong>৳ {{ number_format((float) $item->renewal_price, 2) }}<i>/year</i></strong></div>
                    <div><small>PAID</small><strong>৳ {{ number_format((float) $item->price * max(1, (int) $item->years), 2) }}</strong></div>
                </div>
HTML;

$new = <<<'HTML'
                <div class="store-detail-facts store-detail-facts--inline">
                    <div>
                        <small>REGISTERED</small>
                        @if($order->created_at)<span class="store-fact-join">on</span>@endif
                        <strong>{{ optional($order->created_at)->format('d M Y') ?: '—' }}</strong>
                    </div>
                    <div>
                        <small>EXPIRES</small>
                        @if($order->created_at)<span class="store-fact-join">on</span>@endif
                        <strong>{{ $order->created_at ? $order->created_at->copy()->addYears(max(1, (int) $item->years))->format('d M Y') : '—' }}</strong>
                    </div>
                    <div><small>RENEWAL PRICE</small><strong>৳ {{ number_format((float) $item->renewal_price, 2) }}<i>/year</i></strong></div>
                    <div><small>PAID</small><strong>৳ {{ number_format((float) $item->price * max(1, (int) $item->years), 2) }}</strong></div>
                </div>
HTML;

if (strpos($blade, $old) === false) {
    fwrite(STDERR, "detail facts block not found\n");
    exit(1);
}

file_put_contents($bladePath, str_replace("\n", "\r\n", str_replace($old, $new, $blade)));
echo "detail blade updated\n";

/* ------------------------------------------------------------------ css ---- */
$cssPath = __DIR__ . '/../public/css/store.css';
$css = str_replace("\r\n", "\n", file_get_contents($cssPath));

$anchor = "@media(max-width:420px){\n  .store-facts--inline .store-fact{padding:13px 16px 14px}\n}";
if (strpos($css, $anchor) === false) {
    fwrite(STDERR, "css anchor not found\n");
    exit(1);
}

$add = $anchor . "\n" . <<<'CSS'

/* Manage page: same inline rhythm on the four-fact strip. */
.store-detail-facts--inline{display:flex;flex-wrap:wrap}
.store-detail-facts--inline > div{
  display:flex;
  flex:1 1 auto;
  flex-wrap:wrap;
  align-items:baseline;
  gap:0 7px;
  min-width:max-content;
  padding-right:14px;
}
.store-detail-facts--inline small{
  flex:none;
  color:#5f768c;
  font-size:9.5px;
  font-weight:800;
  letter-spacing:1px;
}
.store-detail-facts--inline strong{
  margin:0;
  color:#1a3150;
  font:800 14px 'Manrope';
  letter-spacing:-.15px;
  white-space:nowrap;
}
.store-detail-facts--inline i{font-size:10px;color:#7d93a8}
.store-detail-facts--inline .store-fact-join{
  margin-left:-1px;
  color:#7d93a8;
  font:500 12px 'DM Sans';
}
CSS;

file_put_contents($cssPath, str_replace("\n", "\r\n", str_replace($anchor, $add, $css)));
echo "detail css appended\n";
