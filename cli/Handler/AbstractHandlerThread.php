<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Handler;

/**
 * Abstract Scraper Thread implementation.
 */
abstract class AbstractHandlerThread extends \Thread {
    /**
     * Constrols yielding.
     *
     * @const bool
     */
    const YIELD_ENABLED = true;
    /**
     * Controls the Yield Interval.
     *
     * @const int
     */
    const YIELD_INTERVAL = 10;
    /**
     * Yield interval backoff multiplier.
     *
     * Use 0 for constant backoff.
     *
     * @const int
     */
    const YIELD_MULTIPLIER = 1;

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
        $logger         = $this->worker->getLogger();
        $startTime      = microtime(true);
        $this->failed   = ! $this->execute();
        $this->execTime = microtime(true) - $startTime;

        if ($this->failed) {
            $logger->error(
                sprintf(
                    '[%s] Error: %s (%.2fs)',
                    static::class,
                    $this->lastError,
                    $this->execTime
                )
            );

            return;
        }

        $logger->debug(
            sprintf(
                '[%s] Completed (%.2fs)',
                static::class,
                $this->execTime
            )
        );
    }

    /**
     * Main execution function.
     *
     * @return void
     */
    abstract public function execute();
}
