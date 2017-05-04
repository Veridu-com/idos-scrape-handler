<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Handler;

use Cli\OAuth2\Facebook\Friends;
use Cli\OAuth2\Facebook\Profile;

/**
 * Facebook Handler definition.
 */
class Facebook extends AbstractHandler {
    /**
     * {@inheritdoc}
     */
    protected function poolThreads() : array {
        return [
            Friends::class,
            Profile::class
        ];
    }
}
