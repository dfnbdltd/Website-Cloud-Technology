<?php
$path = __DIR__ . '/../resources/views/partials/sidebar.blade.php';
$raw = file_get_contents($path);
$eol = (strpos($raw, "\r\n") !== false) ? "\r\n" : "\n";

$replacements = [
    // Domain Transfer child — real page, opens the Domains group and highlights it.
    '                <a data-sidebar-label="Domain Transfer" class="coming-soon-trigger" href="#" data-service="Domain Transfer"><span>Domain Transfer</span></a>'
        => '                <a data-sidebar-label="Domain Transfer" {!! $active === \'domain-transfer\' ? \'class="active"\' : \'\' !!} href="{{ route(\'client.coming-soon\', \'domain-transfer\') }}?back={{ urlencode(request()->getRequestUri()) }}"><span>Domain Transfer</span></a>',

    // SSL Certificate — top-level service row.
    '        <a data-sidebar-label="SSL Certificate" class="service-nav-item coming-soon-trigger" href="#" data-service="SSL Certificate"><b>◇</b><span>SSL Certificate</span></a>'
        => '        <a data-sidebar-label="SSL Certificate" {!! $active === \'ssl-certificate\' ? \'class="active service-nav-item"\' : \'class="service-nav-item"\' !!} href="{{ route(\'client.coming-soon\', \'ssl-certificate\') }}?back={{ urlencode(request()->getRequestUri()) }}"><b>◇</b><span>SSL Certificate</span></a>',

    // Business Email — top-level service row.
    '        <a data-sidebar-label="Business Email" class="service-group-end service-nav-item coming-soon-trigger" href="#" data-service="Business Email"><b>✉</b><span>Business Email</span></a>'
        => '        <a data-sidebar-label="Business Email" {!! $active === \'business-email\' ? \'class="active service-group-end service-nav-item"\' : \'class="service-group-end service-nav-item"\' !!} href="{{ route(\'client.coming-soon\', \'business-email\') }}?back={{ urlencode(request()->getRequestUri()) }}"><b>✉</b><span>Business Email</span></a>',
];

$count = 0;
foreach ($replacements as $old => $new) {
    if (strpos($raw, $old) !== false) {
        $raw = str_replace($old, $new, $raw);
        $count++;
    } else {
        echo "NOT FOUND: " . substr($old, 0, 60) . "...\n";
    }
}

file_put_contents($path, $raw);
echo "replaced $count of " . count($replacements) . "\n";
