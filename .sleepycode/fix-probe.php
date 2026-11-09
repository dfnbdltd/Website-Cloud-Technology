<?php
/* Drop the unsupported ->withoutMiddleware() (Laravel 6 has no such route method). */
$path = __DIR__ . '/../routes/web.php';
$s = str_replace("\r\n", "\n", file_get_contents($path));

$old = "})->withoutMiddleware([\\App\\Http\\Middleware\\EnsureCustomer::class]);";
$new = "});";
if (strpos($s, $old) === false) {
    fwrite(STDERR, "anchor missing\n");
    exit(1);
}

file_put_contents($path, str_replace("\n", "\r\n", str_replace($old, $new, $s)));
echo "probe fixed\n";
