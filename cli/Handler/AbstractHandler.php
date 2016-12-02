<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Handler;

use Cli\Utils\Context;
use Cli\Utils\Logger;
use idOS\Auth\CredentialToken;
use OAuth\Common\Service\ServiceInterface;

/**
 * Abstract Handler Implementation.
 */
abstract class AbstractHandler implements HandlerInterface {
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
     * Handler public key.
     *
     * @var string
     */
    protected $handlerPublicKey;
    /**
     * Handler private key.
     *
     * @var string
     */
    protected $handlerPrivateKey;
    /**
     * Returns the Pool Thread list.
     *
     * @return array
     */
    abstract protected function poolThreads() : array;
    /**
     * Returns the pool size required to deal with all threads.
     *
     * @return int
     */
    protected function poolSize() : int {
        return count($this->poolThreads());
    }

    /**
     * Class constructor.
     *
     * @param Cli\Utils\Logger                        $logger
     * @param \OAuth\Common\Service\ServiceInterface $service
     * @param string                                  $handlerPublicKey
     * @param string                                  $handlerPrivateKey
     *
     * @return void
     */
    public function __construct(
        Logger $logger,
        ServiceInterface $service,
        string $handlerPublicKey,
        string $handlerPrivateKey
    ) {
        $this->logger            = $logger;
        $this->service           = $service;
        $this->handlerPublicKey  = $handlerPublicKey;
        $this->handlerPrivateKey = $handlerPrivateKey;
    }

    /**
     * Handles Scrape process using a thread pool.
     *
     * @param string $publicKey
     * @param string $userName
     * @param int    $sourceId
     * @param bool   $dryRun
     *
     * @return void
     */
    public function handle(
        string $publicKey,
        string $userName,
        int $sourceId,
        bool $dryRun = false
    ) {
        $this->logger->debug(
            sprintf(
                'Initializing pool with %d threads',
                self::poolSize()
            )
        );

        $threadPool = new \Pool(
            self::poolSize(),
            Context::class,
            [
                $this->logger,
                new CredentialToken($publicKey, $this->handlerPublicKey, $this->handlerPrivateKey),
                $this->service,
                $userName,
                $sourceId,
                $dryRun
            ]
        );

        $this->logger->debug('Adding worker threads');
        foreach ($this->poolThreads() as $className) {
            $threadPool->submit(new $className());
        }

        $threadPool->shutdown();

        $this->logger->debug('Completed');
    }
}
