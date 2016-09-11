<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Handler;

use idOS\SDK;
use OAuth\Common\Service\ServiceInterface;

/**
 * Abstract Scraper Thread implementation.
 */
abstract class AbstractHandlerThread extends \Thread {
    /**
     * idOS SDK instance.
     *
     * @var \idOS\SDK
     */
    // protected $sdk;
    /**
     * OAuth Service instance.
     *
     * @var \OAuth\Common\Service\ServiceInterface
     */
    protected $service;
    /**
     * Dry run flag.
     *
     * Controls if data is sent (dryRun=false) to idOS or not (dryRun=true).
     *
     * @var bool
     */
    protected $dryRun;
    /**
     * Failure flag.
     *
     * @var bool
     */
    protected $failed = false;
    /**
     * Last Thread error.
     *
     * @var string
     */
    protected $lastError = '';
    /**
     * Execution time.
     *
     * @var float
     */
    protected $execTime = 0.0;

    /**
     * Class constructor.
     *
     * @param \idOS\SDK                              $sdk
     * @param \OAuth\Common\Service\ServiceInterface $service
     * @param bool                                   $dryRun
     *
     * @return void
     */
    public function __construct(
        // SDK $sdk,
        ServiceInterface $service,
        bool $dryRun = false
    ) {
        // $this->sdk     = $sdk;
        $this->service = $service;
        $this->dryRun  = $dryRun;
    }

    /**
     * Returns Thread's failure flag.
     *
     * @return bool
     */
    public function failed() : bool {
        return $this->failed;
    }

    /**
     * Returns Thread's last error.
     *
     * @return string
     */
    public function getLastError() : string {
        return $this->lastError;
    }

    /**
     * Returns Thread's execution time.
     *
     * @return float
     */
    public function getExecTime() : float {
        return $this->execTime;
    }

    /**
     * Thread run method.
     *
     * @return void
     */
    public function run() {
        $startTime = microtime(true);
        if (! $this->execute()) {
            $this->failed = true;
        }

        $this->execTime = microtime(true) - $startTime;
    }

    /**
     * Main execution function.
     *
     * @return void
     */
    abstract public function execute();
}
