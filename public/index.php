<?php

use App\Kernel;

// FrankenPHP's HTTP SAPI does not populate $_SERVER with OS env vars.
// getenv() still works; default to 'prod' if the env var is absent entirely.
$_SERVER['APP_ENV'] ??= getenv('APP_ENV') ?: 'prod';
$_SERVER['APP_DEBUG'] ??= getenv('APP_DEBUG') ?: '0';

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
