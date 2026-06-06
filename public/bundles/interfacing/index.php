<?php

use App\Interfacing\InterfaceKernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new InterfaceKernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
