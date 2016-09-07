<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace App\Factory;

/**
 * Factory Interface.
 */
interface FactoryInterface {
    /**
     * Register a custom name to class mapping.
     *
     * @param string $name
     * @param string $class
     *
     * @return App\Factory\FactoryInterface
     */
    public function register(string $name, string $class) : FactoryInterface;
    /**
     * Builds and returns objects.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function create(string $name);
}
