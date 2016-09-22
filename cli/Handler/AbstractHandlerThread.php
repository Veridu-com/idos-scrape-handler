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
        $startTime = microtime(true);
        if (! $this->execute()) {
            $this->worker->getLogger()->error(
                sprintf('[%s] Error: %s', static::class, $this->lastError)
            );
            $this->failed = true;
        }

        $this->execTime = microtime(true) - $startTime;
        $this->worker->getLogger()->debug(
            sprintf('[%s] Completed (%.2fs)', static::class, $this->execTime)
        );
    }

    /**
     * Main execution function.
     *
     * @return void
     */
    abstract public function execute();
}
