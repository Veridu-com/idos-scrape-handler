<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\Handler;

use Cli\Utils\Logger;
use idOS\Auth\AuthInterface;
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
     * Returns a generator to be iterated and create threads.
     *
     * @param string                                 $publicKey
     * @param string                                 $userName
     * @param int                                    $sourceId
     * @param \OAuth\Common\Service\ServiceInterface $service
     * @param
     *
     * @return \Generator
     */
    protected function poolGenerator(
        string $publicKey,
        string $userName,
        int $sourceId,
        ServiceInterface $service,
        bool $dryRun
    ) : \Generator {
        foreach ($this->poolThreads() as $thread) {
            yield new $thread($publicKey, $userName, $sourceId, $service, $dryRun);
        }
    }

    /**
     * Class constructor.
     *
     * @param Cli\Utils\Logger                       $logger
     * @param \OAuth\Common\Service\ServiceInterface $service
     *
     * @return void
     */
    public function __construct(
        Logger $logger,
        ServiceInterface $service
    ) {
        $this->logger  = $logger;
        $this->service = $service;
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

        $threadPool = [];

        $this->logger->debug('Adding worker threads');
        foreach (self::poolGenerator($publicKey, $userName, $sourceId, $this->service, $dryRun) as $thread) {
            $threadPool[] = $thread;
            $thread->start();
        }

        $this->logger->debug('Waiting for threads to be done');
        do {
            foreach ($threadPool as $index => $thread) {
                if ($thread->isRunning()) {
                    continue;
                }

                $className = get_class($thread);
                $className = substr($className, strrpos($className, '\\') + 1);

                if ($thread->isTerminated()) {
                    $this->logger->debug(
                        sprintf(
                            'Thread #%d (%s) was terminated: "%s"',
                            $index,
                            $className,
                            $thread->getLastError()
                        )
                    );
                    unset($threadPool[$index]);
                    continue;
                }

                if ($thread->failed()) {
                    $this->logger->debug(
                        sprintf(
                            'Thread #%d (%s) failed: "%s"',
                            $index,
                            $className,
                            $thread->getLastError()
                        )
                    );
                    unset($threadPool[$index]);
                    continue;
                }

                $this->logger->debug(
                    sprintf(
                        'Thread #%d (%s) completed (%.2fs)',
                        $index,
                        $className,
                        $thread->getExecTime()
                    )
                );

                unset($threadPool[$index]);
            }
        } while (count($threadPool));

        $this->logger->debug('Completed');
    }
}
