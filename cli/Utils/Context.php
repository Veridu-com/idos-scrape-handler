<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Utils;

use OAuth\Common\Service\ServiceInterface;

/**
 * Handles Thread's context.
 */
class Context extends \Worker {
    /**
     * Thread-safe Logger instance.
     *
     * @var Cli\Utils\Logger
     */
    private $logger;
    /**
     * OAuth Service instance.
     *
     * @var \OAuth\Common\Service\ServiceInterface
     */
    private $service;
    /**
     * Thread-safe buffer instance..
     *
     * @var Cli\Utils\Buffer
     */
    private $buffer;

    /**
     * Class constructor.
     *
     * @param Cli\Utils\Logger                       $logger
     * @param \OAuth\Common\Service\ServiceInterface $service
     *
     * @return void
     */
    public function __construct(
        Logger $logger,
        ServiceInterface $service,
        Buffer $buffer
    ) {
        $this->logger  = $logger;
        $this->service = $service;
        $this->buffer  = $buffer;
        $buffer->woot = '?';

    }

    /**
     * Worker's run function.
     *
     * Ensures autoloading works on thread's context.
     *
     * @return void
     */
    public function run() {
        require_once __DIR__ . '/../../vendor/autoload.php';
    }

    /**
     * Returns the Logger instance.
     *
     * @return Cli\Utils\Logger
     */
    public function getLogger() : Logger {
        return $this->logger;
    }

    /**
     * Returns the oAuth Service instance.
     *
     * @return mixed
     */
    public function getService() {
        return $this->service;
    }

    /**
     * Returns the Buffer instance.
     *
     * @return Cli\Utils\Buffer
     */
    public function getBuffer() : Buffer {
        return $this->buffer;
    }
}
