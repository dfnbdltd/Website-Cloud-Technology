<?php
foreach (['domain-transfer', 'ssl-certificate', 'business-email', 'sms-service', 'not-a-service'] as $s) {
    $url = 'http://127.0.0.1:8143/services/' . $s;
    $ctx = stream_context_create(['http' => ['ignore_errors' => true]]);
    $h = @file_get_contents($url, false, $ctx);
    if ($h === false) {
        echo $s . " :: fetch fail\n";
        continue;
    }
    $code = 'n/a';
    foreach ($http_response_header ?? [] as $l) {
        if (stripos($l, 'HTTP/') === 0) { $code = $l; break; }
    }
    preg_match('~<h1 class="cs-title"[^>]*>([^<]*)~', $h, $m);
    $h1 = $m[1] ?? '(none)';
    $nav = strpos($h, 'id="site-navbar"') !== false;
    $foot = strpos($h, 'class="site-footer"') !== false;
    $back = strpos($h, 'id="csBack"') !== false;
    echo $s . ' | ' . $code . ' | h1=' . $h1 . ' | nav=' . ($nav ? 'y' : 'n') . ' foot=' . ($foot ? 'y' : 'n') . ' back=' . ($back ? 'y' : 'n') . "\n";
}
