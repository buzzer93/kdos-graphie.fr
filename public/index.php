<?php

use App\Kernel;

// FrankenPHP's HTTP SAPI does not populate $_SERVER with OS env vars.
// Without this, Symfony's dotenv loads .env (APP_ENV=dev) and cannot
// be overridden by .env.local because the key is already set.
foreach (['APP_ENV', 'APP_DEBUG'] as $_k) {
    if (($_v = getenv($_k)) !== false) {
        $_SERVER[$_k] ??= $_ENV[$_k] ??= $_v;
    }
}
unset($_k, $_v);

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
