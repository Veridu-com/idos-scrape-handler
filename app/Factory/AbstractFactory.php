<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace App\Factory;

/**
 * Abstract Factory Implementation.
 */
abstract class AbstractFactory implements FactoryInterface {
    /**
     * Class Map for Factory Calls.
     *
     * @var array
     */
    protected $classMap = [];

    /**
     * Returns the class namespace.
     *
     * @return string
     */
    abstract protected function getNamespace();

    /**
     * Returns the formatted name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getFormattedName(string $name) : string {
        return ucfirst($name);
    }

    /**
     * Returns the fully qualified class.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getClassName(string $name) {
        $name = $this->getFormattedName($name);

        if (isset($this->classMap[$name])) {
            return $this->classMap[$name];
        }

        return sprintf('%s%s', $this->getNamespace(), $name);
    }

    /**
     * Registers a custom name to class mapping.
     *
     * @param string $name
     * @param string $class
     *
     * @return App\Factory\FactoryInterface
     */
    public function register(string $name, string $class) : FactoryInterface {
        if (! class_exists($class)) {
            throw new \RuntimeException(sprintf('Repository Class "%s" does not exist.', $class));
        }

        $name                  = $this->getFormattedName($name);
        $this->classMap[$name] = $class;

        return $this;
    }

    /**
     * Creates new object instances.
     *
     * @param string $name
     *
     * @throws \RuntimeException
     *
     * @return mixed
     */
    public function create(string $name) {
        $class = $this->getClassName($name);

        if (class_exists($class)) {
            return new $class();
        }

        throw new \RuntimeException(sprintf('"%s" (%s) not found.', $name, $class));
    }
}
