<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli;

use Cli\Common\HandlerInterface;
use Cli\Utils\Logger;

/**
 * Handler Factory.
 */
class HandlerFactory {
    /**
     * Class Map for Factory Calls.
     *
     * @var array
     */
    private $classMap = [];
    /**
     * OAuth Factory Instance.
     *
     * @var Cli\OAuthFactory
     */
    private $oauthFactory;

    /**
     * Returns the formatted name.
     *
     * @param string $name
     *
     * @return string
     */
    private function getFormattedName(string $name) : string {
        return ucfirst($name);
    }

    /**
     * Returns the fully qualified class name (FQCN).
     *
     * @param string $name
     *
     * @return string
     */
    private function getClassName(string $name) : string {
        $name = $this->getFormattedName($name);

        if (isset($this->classMap[$name])) {
            return $this->classMap[$name];
        }

        return sprintf('Cli\\Handler\\%s', $name);
    }

    /**
     * Class constructor.
     *
     * @param Cli\OAuthFactory $oauthFactory
     * @param array            $classMap
     *
     * @return void
     */
    public function __construct(OAuthFactory $oauthFactory, array $classMap = []) {
        $this->oauthFactory = $oauthFactory;
        foreach ($classMap as $name => $class) {
            $this->register($name, $class);
        }
    }

    /**
     * Registers a custom Handler name to class name mapping.
     *
     * @param string $handlerName
     * @param string $className
     *
     * @throws \RuntimeException
     *
     * @return Cli\HandlerFactory
     */
    public function register(string $handlerName, string $className) : HandlerFactory {
        if (! class_exists($className)) {
            throw new \RuntimeException(sprintf('Class "%s" does not exist.', $className));
        }

        $handlerName = $this->getFormattedName($handlerName);
        $reflClass   = new \ReflectionClass($className);

        if ($reflClass->implementsInterface('Cli\\Handler\\HandlerInterface')) {
            $this->classMap[$handlerName] = $className;

            return $this;
        }

        throw new \RuntimeException(sprintf('Class "%s" must implement HandlerInterface.', $className));
    }

    /**
     * Returns if a handlerName is valid.
     *
     * @param string $handlerName
     *
     * @return bool
     */
    public function check(string $handlerName) : bool {
        $className = $this->getClassName($handlerName);

        return class_exists($className);
    }

    /**
     * Creates a new instance of a provider based on its name.
     *
     * @param \Cli\Utils\Logger $logger
     * @param string            $handlerName
     * @param string            $accessToken
     * @param string            $tokenSecret
     * @param string            $appKey
     * @param string            $appSecret
     * @param string            $apiVersion
     * @param string            $handlerPublicKey
     * @param string            $handlerPrivateKey
     *
     * @throws \RuntimeException
     *
     * @return mixed
     */
    public function create(
        Logger $logger,
        string $handlerName,
        string $accessToken,
        string $tokenSecret,
        string $appKey,
        string $appSecret,
        string $apiVersion,
        string $handlerPublicKey,
        string $handlerPrivateKey
    ) {
        $className = $this->getClassName($handlerName);

        if (class_exists($className)) {
            return new $className(
                $logger,
                $this->oauthFactory->create(
                    $handlerName,
                    $accessToken,
                    $tokenSecret,
                    $appKey,
                    $appSecret,
                    $apiVersion
                ),
                $handlerPublicKey,
                $handlerPrivateKey
            );
        }

        throw new \RuntimeException(sprintf('"%s" not found.', $handlerName));
    }
}
