<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Handler;

use Cli\OAuth2\Facebook\Events;
use Cli\OAuth2\Facebook\Family;
use Cli\OAuth2\Facebook\Friends;
use Cli\OAuth2\Facebook\Groups;
use Cli\OAuth2\Facebook\Likes;
use Cli\OAuth2\Facebook\Photos;
use Cli\OAuth2\Facebook\Posts;
use Cli\OAuth2\Facebook\Profile;
use Cli\OAuth2\Facebook\Tagged;

/**
 * Facebook Handler definition.
 */
class Facebook extends AbstractHandler {
    /**
     * {@inheritdoc}
     */
    protected function poolThreads() : array {
        return [
            Events::class,
            Family::class,
            Friends::class,
            Groups::class,
            Likes::class,
            Photos::class,
            Posts::class,
            Profile::class,
            Tagged::class
        ];
    }
}
