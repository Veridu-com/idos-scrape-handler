<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Handler;

use Cli\OAuth2\Yahoo\Contacts;
use Cli\OAuth2\Yahoo\Profile;

/**
 * Yahoo Handler definition.
 */
class Yahoo extends AbstractHandler {
    /**
     * {@inheritdoc}
     */
    protected function poolThreads() : array {
        return [
            Contacts::class,
            Profile::class
        ];
    }
}
