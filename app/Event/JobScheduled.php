<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace App\Event;

use App\Command\Job;

/**
 * Job Scheduled event.
 */
class JobScheduled extends AbstractEvent {
    /**
     * Event related Job.
     *
     * @var App\Command\Job
     */
    public $job;
    /**
     * Event related Gearman Task.
     *
     * @var \GearmanTask
     */
    public $task;

    /**
     * Class constructor.
     *
     * @param App\Command\Job $job
     * @param \GearmanTask    $task
     *
     * @return void
     */
    public function __construct(Job $job, \GearmanTask $task) {
        $this->job  = $job;
        $this->task = $task;
    }
}
