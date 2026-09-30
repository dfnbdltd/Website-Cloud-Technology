<?php

namespace App\Bootstrap;

use Illuminate\Foundation\Bootstrap\HandleExceptions as BaseHandleExceptions;
use Illuminate\Contracts\Foundation\Application;

/**
 * Laravel 6 sets error_reporting(-1), which turns PHP 8.1+ deprecation
 * notices from the framework's own vendor code into fatal exceptions.
 * Keep every other error level, but ignore deprecations.
 */
class HandleExceptions extends BaseHandleExceptions
{
    public function bootstrap(Application $app)
    {
        parent::bootstrap($app);

        error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    }
}
