<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Utils;

use OAuth\Common\Service\ServiceInterface;
use idOS\SDK;

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
     * idOS SDK instance.
     *
     * @var \idOS\SDKK
     */
    private $sdk;
    /**
     * OAuth Service instance.
     *
     * @var \OAuth\Common\Service\ServiceInterface
     */
    private $service;

    /**
     * Class constructor.
     *
     * @param Cli\Utils\Logger                       $logger
     * @param \idOS\SDK                              $sdk
     * @param \OAuth\Common\Service\ServiceInterface $service
     *
     * @return void
     */
    public function __construct(
        Logger $logger,
        SDK $sdk,
        ServiceInterface $service
    ) {
        $this->logger  = $logger;
        $this->sdk     = $sdk;
        $this->service = $service;

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
     * Returns the idOS SDK instance.
     *
     * @return \idOS\SDK
     */
    public function getSDK() : SDK {
        return $this->sdk;
    }
}
