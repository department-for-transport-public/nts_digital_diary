<?php

error_reporting(E_ALL);

use App\Kernel;
use App\Utility\RemoteActions;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context): \App\Kernel {
    // ----- Modified START
    RemoteActions::run(@parse_url((string) $context['REQUEST_URI'])['path']);
    // ----- Modified END
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};