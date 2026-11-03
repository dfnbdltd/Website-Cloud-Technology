<?php
/**
 * Convert the public navbar's coming-soon triggers from modal buttons into
 * real links to /services/{slug}. Each link passes the current URL as `back`
 * so the page's "Go back" returns the visitor exactly where they were.
 *
 * The `data-service` attribute is kept on the anchors, so the modal script
 * (which looks for .coming-soon-trigger) must no longer see them. We drop the
 * class and rely on the new .coming-soon-link class instead.
 */
$path = __DIR__ . '/../resources/views/partials/site-navbar.blade.php';
$raw = file_get_contents($path);

$replacements = [
    // Domain Transfer (inside the Domains dropdown)
    '<a class="coming-soon-trigger" href="#" data-service="Domain Transfer">Domain Transfer</a>'
        => '<a class="coming-soon-link" href="{{ route(\'services.coming-soon\', \'domain-transfer\') }}?back={{ urlencode(request()->fullUrl()) }}">Domain Transfer</a>',

    // Top-level items
    '<a class="coming-soon-trigger" href="#" data-service="SSL Certificate">SSL Certificate</a>'
        => '<a class="coming-soon-link" href="{{ route(\'services.coming-soon\', \'ssl-certificate\') }}?back={{ urlencode(request()->fullUrl()) }}">SSL Certificate</a>',

    '<a class="coming-soon-trigger" href="#" data-service="Business Email">Business Email</a>'
        => '<a class="coming-soon-link" href="{{ route(\'services.coming-soon\', \'business-email\') }}?back={{ urlencode(request()->fullUrl()) }}">Business Email</a>',

    '<a class="coming-soon-trigger" href="#" data-service="SMS Service">SMS Service</a>'
        => '<a class="coming-soon-link" href="{{ route(\'services.coming-soon\', \'sms-service\') }}?back={{ urlencode(request()->fullUrl()) }}">SMS Service</a>',
];

$applied = 0;
foreach ($replacements as $from => $to) {
    if (strpos($raw, $from) === false) {
        echo "MISS: ", substr($from, 0, 70), PHP_EOL;
        continue;
    }
    $raw = str_replace($from, $to, $raw);
    $applied++;
}

file_put_contents($path, $raw);
echo "applied {$applied}/", count($replacements), PHP_EOL;
