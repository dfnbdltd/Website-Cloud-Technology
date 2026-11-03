<?php
$path = __DIR__ . '/../public/css/store.css';
$raw = file_get_contents($path);
$eol = (strpos($raw, "\r\n") !== false) ? "\r\n" : "\n";

if (strpos($raw, 'store-coming-points') !== false) {
    echo "already appended\n";
    exit(0);
}

$block = <<<CSS
/* --- Coming-soon client page (sidebar service rows) --- */
.store-coming-points{list-style:none;display:flex;flex-wrap:wrap;justify-content:center;gap:8px 20px;padding:0;margin:0 0 22px}
.store-coming-points li{position:relative;color:#5c7695;font-size:11.5px}
.store-coming-points li::before{content:"✓";color:var(--tone);margin-right:7px;font-weight:800}
.store-empty-actions{display:flex;justify-content:center;align-items:center;gap:10px;flex-wrap:wrap}
.store-empty-actions .store-ghost-link{display:inline-flex;align-items:center;text-decoration:none}
CSS;

$raw = rtrim($raw) . $eol . $eol . $block . $eol;
file_put_contents($path, $raw);
echo "appended\n";
