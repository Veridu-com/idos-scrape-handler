<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Handler;

use Cli\OAuth1\Twitter\Followers;
use Cli\OAuth1\Twitter\Friends;
use Cli\OAuth1\Twitter\Profile;
use Cli\OAuth1\Twitter\Statuses;

/**
 * Twitter Handler definition.
 */
class Twitter extends AbstractHandler {
    /**
     * {@inheritdoc}
     */
    protected function poolThreads() : array {
        return [
            Followers::class,
            Friends::class,
            Profile::class,
            Statuses::class
        ];
    }
}
