<?php

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

if (PHP_SAPI === 'cli-server' || PHP_SAPI === 'frankenphp') {
    $_SERVER['SCRIPT_FILENAME'] = __FILE__;
}

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
