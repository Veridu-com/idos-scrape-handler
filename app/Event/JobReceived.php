<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace App\Event;

use App\Command\Job;

/**
 * Job Received event.
 */
class JobReceived extends AbstractEvent {
    /**
     * Event related Job.
     *
     * @var App\Command\Job
     */
    public $job;

    /**
     * Class constructor.
     *
     * @param App\Command\Job $job
     *
     * @return void
     */
    public function __construct(Job $job) {
        $this->job = $job;
    }
}
