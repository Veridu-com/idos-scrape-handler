<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

date_default_timezone_set('UTC');
setlocale(LC_ALL, 'en_US.UTF8');
mb_http_output('UTF-8');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../environment.php';
require_once __DIR__ . '/../config/settings.php';

if ($appSettings['debug']) {
    ini_set('display_errors', 'On');
    error_reporting(-1);
}

/*
 * Application Setup
 */
$app = new Slim\App(
    ['settings' => $appSettings]
);

require_once __DIR__ . '/../config/dependencies.php';

require_once __DIR__ . '/../config/middleware.php';

require_once __DIR__ . '/../config/handlers.php';

require_once __DIR__ . '/../config/routes.php';

$app->run();
