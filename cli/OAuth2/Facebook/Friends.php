<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Facebook;

use idOS\SDK;

class Friends extends AbstractFacebookThread {
    /**
     * {@inheritdoc}
     */
    public function execute(SDK $sdk) : bool {
        return false;
    }
}
