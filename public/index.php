<?php

use App\Kernel;

// Setting directory to itself, in order to fix relative paths whith calls from symfony (e.g CallLegacy)
chdir(__DIR__);

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
