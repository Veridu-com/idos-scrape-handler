<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace App\Handler;

use App\Command\Job;
use App\Event\InvalidJob;
use App\Event\JobReceived;
use App\Event\JobScheduled;
use App\Event\ScheduleFailed;
use App\Exception\AppException;
use App\Validator\Schedule as ScheduleValidator;
use Interop\Container\ContainerInterface;
use League\Event\Emitter;

/**
 * Handles Schedule commands.
 */
class Schedule implements HandlerInterface {
    /**
     * Gearman Client instance.
     *
     * @var \GearmanClient
     */
    protected $gearman;
    /**
     * Schedule Validator instance.
     *
     * @var App\Validator\Schedule
     */
    protected $validator;
    /**
     * Event emitter instance.
     *
     * @var \League\Event\Emitter
     */
    protected $emitter;

    /**
     * {@inheritdoc}
     */
    public static function register(ContainerInterface $container) {
        $container[self::class] = function (ContainerInterface $container) : HandlerInterface {
            return new \App\Handler\Schedule(
                $container
                    ->get('gearmanClient'),
                $container
                    ->get('validatorFactory')
                    ->create('Schedule'),
                $container
                    ->get('eventEmitter')
            );
        };
    }

    /**
     * Class constructor.
     *
     * @param \GearmanClient         $gearmanClient
     * @param App\Validator\Schedule $validator
     * @param \League\Event\Emitter  $emitter
     *
     * @return void
     */
    public function __construct(
        \GearmanClient $gearmanClient,
        ScheduleValidator $validator,
        Emitter $emitter
    ) {
        $this->gearman   = $gearmanClient;
        $this->validator = $validator;
        $this->emitter   = $emitter;
    }

    /**
     * Handles Job scheduling.
     *
     * @param App\Command\Job $command
     *
     * @return void
     */
    public function handleJob(Job $command) {
        try {
            // Job validation
            $this->validator->assertUserName($command->userName);
            $this->validator->assertId($command->sourceId);
            $this->validator->assertName($command->providerName);
            $this->validator->assertToken($command->accessToken);
            $this->validator->assertNullableToken($command->tokenSecret);
            $this->validator->assertNullableToken($command->appKey);
            $this->validator->assertNullableToken($command->appSecret);
            $this->validator->assertNullableVersion($command->apiVersion);
            $this->validator->assertKey($command->publicKey);
        } catch (\Respect\Validation\Exceptions\ExceptionInterface $exception) {
            $this->emitter->emit(new InvalidJob($command));
            throw new AppException(
                sprintf(
                    'Invalid input: %s',
                    implode('; ', $exception->getMessages())
                ),
                400
            );
        }

        $this->emitter->emit(new JobReceived($command));

        // Job Scheduling
        $task = $this->gearman->doBackground(
            'scrape',
            json_encode($command)
        );
        if ($this->gearman->returnCode() === \GEARMAN_SUCCESS) {
            $this->emitter->emit(new JobScheduled($command, $task));

            return;
        }

        $this->emitter->emit(new ScheduleFailed($command, $this->gearman->error()));
    }
}
