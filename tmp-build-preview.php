<?php
require __DIR__ . '/vendor/autoload.php';

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

$lines = file(__DIR__ . '/resources/views/store/domain-details.blade.php');

// body of @section('store'): lines 4..270
$fragment = implode('', array_slice($lines, 3, 267));

$compiler = new BladeCompiler(new Filesystem(), sys_get_temp_dir());
$compiled = $compiler->compileString($fragment);
file_put_contents(__DIR__ . '/tmp-fragment.php', $compiled);

if (!function_exists('e')) {
    function e($value, $doubleEncode = true)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}

function old($key, $default = null)
{
    return $default;
}

function csrf_field()
{
    return '<input type="hidden" name="_token" value="preview">';
}

function route($name, $params = [])
{
    return '#preview-' . str_replace('.', '-', $name);
}

$item = (object) [
    'id' => 1,
    'domain' => 'cloudtechnologybd.com',
    'years' => 1,
    'price' => 1250.0,
    'renewal_price' => 1450.0,
];

$order = (object) [
    'reference' => 'ORD-2026-0042',
    'created_at' => Carbon\Carbon::parse('2026-03-11 10:24:00'),
];

$invoice = null;
$activities = [];

class StubEnv
{
    private $loops = [];

    public function addLoop($data)
    {
        $this->loops[] = ['remaining' => count($data), 'index' => 0, 'iteration' => 1];
    }

    public function incrementLoopIndices()
    {
        $index = count($this->loops) - 1;
        $this->loops[$index]['iteration']++;
    }

    public function getLastLoop()
    {
        return null;
    }

    public function popLoop()
    {
        array_pop($this->loops);
    }
}

function renderPanel($state)
{
    $nameservers = $state === 'empty'
        ? []
        : ['ns1.stackdns.com', 'ns2.stackdns.com'];

    $__env = new StubEnv();
    $errors = new Illuminate\Support\MessageBag();
    extract(['item' => $GLOBALS['item'], 'order' => $GLOBALS['order'], 'invoice' => $GLOBALS['invoice'], 'activities' => $GLOBALS['activities'], 'nameservers' => $nameservers]);

    ob_start();
    include __DIR__ . '/tmp-fragment.php';

    return ob_get_clean();
}

$css = function ($path) {
    return 'public/css/' . $path;
};

$page = function ($state) use ($css) {
    $body = renderPanel($state);

    return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        . '<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">'
        . '<link rel="stylesheet" href="' . $css('app.css') . '">'
        . '<link rel="stylesheet" href="' . $css('workspace.css') . '">'
        . '<link rel="stylesheet" href="' . $css('domains.css') . '">'
        . '<link rel="stylesheet" href="' . $css('store.css') . '">'
        . '<link rel="stylesheet" href="' . $css('chat.css') . '">'
        . '<style>html,body{margin:0}body{background:#f7f9fc}</style>'
        . '</head><body class="domain-app"><main class="domain-main"><div class="domain-content standalone-content store-page">'
        . $body
        . '</div></main></body></html>';
};

file_put_contents(__DIR__ . '/tmp-preview-configured.html', $page('configured'));
file_put_contents(__DIR__ . '/tmp-preview-empty.html', $page('empty'));

echo "preview written\n";
