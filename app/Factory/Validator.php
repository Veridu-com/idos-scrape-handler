<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace App\Factory;

/**
 * Validator Factory Implementation.
 */
class Validator extends AbstractFactory {
    /**
     * {@inheritdoc}
     */
    protected function getNamespace() : string {
        return '\\App\\Validator\\';
    }
}
