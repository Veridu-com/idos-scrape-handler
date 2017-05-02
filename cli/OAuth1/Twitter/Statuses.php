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

        // Retrieve data from Twitter's API
        try {
            $buffer = $this->fetchAllWithIds('/statuses/user_timeline.json', 'count=200');
        } catch (\Exception $exception) {
            $this->lastError = $exception->getMessage();

            return false;
        }

        $logger->debug(
            sprintf(
                '[%s] Retrieved %d items',
                static::class,
                count($buffer)
            )
        );

        if (! $this->worker->isDryRun()) {
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
