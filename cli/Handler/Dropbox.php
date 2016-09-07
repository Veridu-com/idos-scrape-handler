<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Handler;

use Cli\OAuth2\Dropbox\Metadata;
use Cli\OAuth2\Dropbox\Profile;

/**
 * Dropbox Handler definition.
 */
class Dropbox extends AbstractHandler {
    /**
     * {@inheritdoc}
     */
    protected function poolThreads() : array {
        return [
            Profile::class,
            Metadata::class
        ];
    }
}
