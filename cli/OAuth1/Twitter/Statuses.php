<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth1\Twitter;

/**
 * Twitter Status's Profile Scraper.
 */
class Statuses extends AbstractTwitterThread {
    /**
     * {@inheritdoc}
     */
    public function execute() : bool {
        $rawEndpoint = $this->worker->getSdk()
            ->Profile($this->worker->getUserName())
            ->Raw;

        $logger = $this->worker->getLogger();

        try {
            // Retrieve data from Twitter's API
            $buffer = $this->fetchAllWithIds('/statuses/user_timeline.json', 'count=200');
        } catch (\Exception $exception) {
            $this->lastError = $exception->getMessage();

            return false;
        }

        $numItems = count($buffer);

        $logger->debug(
            sprintf(
                '[%s] Retrieved %d items',
                static::class,
                $numItems
            )
        );

        if ($this->worker->isDryRun()) {
            $logger->debug(
                sprintf(
                    '[%s] Statuses data',
                    static::class
                ),
                $buffer
            );

            return true;
        }

        if ($numItems) {
            // Send statuses data to idOS API
            try {
                $logger->debug(
                    sprintf(
                        '[%s] Sending data',
                        static::class
                    )
                );
                $rawEndpoint->upsertOne(
                    $this->worker->getSourceId(),
                    'statuses',
                    $buffer
                );
                $logger->debug(
                    sprintf(
                        '[%s] Data sent',
                        static::class
                    )
                );
            } catch (\Exception $exception) {
                $this->lastError = $exception->getMessage();

                return false;
            }
        }

        return true;
    }
}
