<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace App\Listener;

use League\Event\EventInterface;
use Monolog\Logger;

class LogFiredEventListener extends AbstractListener {
    private $logger;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    public function handle(EventInterface $event) {
        $this->logger->debug(sprintf('%s was fired', $event->getName()));
    }
}
