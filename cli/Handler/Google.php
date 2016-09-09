<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Handler;

use Cli\OAuth2\Google\Contacts;
use Cli\OAuth2\Google\Drive;
use Cli\OAuth2\Google\GMail;
use Cli\OAuth2\Google\Plus;
use Cli\OAuth2\Google\Profile;

/**
 * Google Handler definition.
 */
class Google extends AbstractHandler {
    /**
     * {@inheritdoc}
     */
    protected function poolThreads() : array {
        return [
            Contacts::class,
            Drive\Apps::class,
            Drive\Files::class,
            GMail\Labels::class,
            GMail\Messages::class,
            GMail\Profile::class,
            Plus\Activities::class,
            Plus\Circles::class,
            Plus\Profile::class,
            Profile::class,
        ];
    }
}
