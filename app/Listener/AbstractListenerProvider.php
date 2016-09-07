<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace App\Listener;

use League\Event\ListenerAcceptorInterface;
use League\Event\ListenerProviderInterface;

/**
 * Abstract Listener Provider Implementation.
 */
abstract class AbstractListenerProvider implements ListenerProviderInterface {
    /**
     * Associative array defining events and their listeners
     * initialized on constructor.
     *
     * @example array [ 'event' => [ 'listener1', 'listener2'] ]
     *
     * @var array
     */
    protected $events = [];

    /**
     * {@inheritdoc}
     */
    public function provideListeners(ListenerAcceptorInterface $acceptor) {
        foreach ($this->events as $eventName => $listeners) {
            if (count($listeners)) {
                foreach ($listeners as $listener) {
                    $acceptor->addListener($eventName, $listener);
                }
            }
        }
    }
}
