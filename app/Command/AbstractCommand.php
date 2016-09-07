<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace App\Command;

/**
 * Abstract Command Implementation.
 */
abstract class AbstractCommand implements CommandInterface {
    /**
     * {@inheritdoc}
     */
    public function setParameter(string $name, $value) {
        if (property_exists($this, $name)) {
            $this->{$name} = $value;

            return $this;
        }

        throw new \RuntimeException(sprintf('Invalid property name "%s"', $name));
    }
}
