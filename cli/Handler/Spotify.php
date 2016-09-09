<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Handler;

use Cli\OAuth2\Spotify\Playlists;
use Cli\OAuth2\Spotify\Profile;
use Cli\OAuth2\Spotify\Tracks;

/**
 * Spotify Handler definition.
 */
class Spotify extends AbstractHandler {
    /**
     * {@inheritdoc}
     */
    protected function poolThreads() : array {
        return [
            Playlists::class,
            Profile::class,
            Tracks::class
        ];
    }
}
