<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Handler;

use Cli\OAuth2\PayPal\Profile;

/**
 * PayPal Handler definition.
 */
class PayPal extends AbstractHandler {
    /**
     * {@inheritdoc}
     */
    protected function poolThreads() : array {
        return [
            Profile::class
        ];
    }
}
