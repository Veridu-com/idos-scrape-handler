<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Handler;

use Cli\Utils\Logger;
use OAuth\Common\Service\ServiceInterface;

/**
 * Abstract Scraper Thread implementation.
 */
abstract class AbstractHandlerThread extends \Thread {
    /**
     * Thread raw data buffer.
     *
     * @var string
     */
    protected $rawBuffer = '';
    /**
     * Thread parsed buffer.
     *
     * @var string
     */
    protected $parsedBuffer;
    /**
     * Logger instance.
     *
     * @var Cli\Utils\Logger
     */
    protected $logger;
    /**
     * OAuth Service instance.
     *
     * @var \OAuth\Common\Service\ServiceInterface
     */
    protected $service;
    /**
     * Last Thread error.
     *
     * @var string
     */
    protected $lastError = '';

    /**
     * Class constructor.
     *
     * @param Logger           $logger
     * @param ServiceInterface $service
     *
     * @return void
     */
    public function __construct(Logger $logger, ServiceInterface $service) {
        $this->logger  = $logger;
        $this->service = $service;
    }

    /**
     * Returns Thread's raw buffer content.
     *
     * @return string
     */
    public function getRawBuffer() : string {
        return $this->rawBuffer;
    }

    /**
     * Returns Thread's raw buffer size.
     *
     * @return int
     */
    public function getRawBufferSize() : int {
        return strlen($this->rawBuffer);
    }

    /**
     * Returns Thread's parsed buffer content.
     *
     * @return mixed
     */
    public function getParsedBuffer() {
        return $this->parsedBuffer;
    }

    /**
     * Returns Thread's last error.
     *
     * @return string
     */
    public function getLastError() : string {
        return $this->lastError;
    }
}
