<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Utils;

use Monolog\Logger as Monolog;

/**
 * Handles Thread-Safe logging.
 */
class Logger extends \Threaded {
    /**
     * Monolog instance.
     *
     * @var \Monolog\Logger
     */
    private $logger;
    /**
     * Busy flag for synchornized output.
     *
     * @var bool
     */
    private $busy = false;

    /**
     * Class constructor.
     *
     * @param \Monolog\Logger $logger
     *
     * @return void
     */
    public function __construct(Monolog $logger) {
        $this->logger = $logger;
    }

    /**
     * Synchronized function call.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed|null
     */
    public function __call(string $name, array $arguments) {
        $this->synchronized(
            function () use ($name, $arguments) {
                while ($this->busy) {
                    $this->wait();
                }

                $this->busy = true;
                call_user_func_array([$this->logger, $name], $arguments);
                $this->busy = false;
                $this->notify();
            }
        );
    }
}
