<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth1\Twitter;

/**
 * Twitter Friends's Profile Scraper.
 */
class Friends extends AbstractTwitterThread {
    /**
     * {@inheritdoc}
     */
    public function execute() : bool {
        $rawEndpoint = $this->worker->getSdk()
            ->Profile($this->worker->getUserName())
            ->Raw;

        $logger = $this->worker->getLogger();

        try {
            $buffer = $this->fetchAllWithCursors(
                '/friends/list.json',
                'count=200&include_user_entities=true',
                'users'
            );
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
            // Send friends data to idOS API
            try {
                $logger->debug(
                    sprintf(
                        '[%s] Sending data',
                        static::class
                    )
                );
                $rawEndpoint->upsertOne(
                    $this->worker->getSourceId(),
                    'friends',
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
