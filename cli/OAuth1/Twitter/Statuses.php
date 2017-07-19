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
        $data   = [];

        try {
            // Retrieve data from Twitter's API
            $fetch = $this->fetchAllWithIds(
                '/statuses/user_timeline.json',
                'count=200'
            );

            foreach ($fetch as $buffer) {
                $numItems = count($buffer);

                $logger->debug(
                    sprintf(
                        '[%s] Retrieved %d items',
                        static::class,
                        $numItems
                    )
                );


                if ($numItems) {
                    $data = array_merge($data, $buffer);

                    if ($this->worker->isDryRun()) {
                        $this->worker->writeData(
                            $data,
                            static::class
                        );

                        continue;
                    }

                    // Send data to idOS API
                    $logger->debug(
                        sprintf(
                            '[%s] Sending data',
                            static::class
                        )
                    );
                    $rawEndpoint->upsertOne(
                        $this->worker->getSourceId(),
                        'statuses',
                        $data
                    );
                    $logger->debug(
                        sprintf(
                            '[%s] Data sent',
                            static::class
                        )
                    );
                }
            }
        } catch (\Exception $exception) {
            $this->lastError = $exception->getMessage();

            return false;
        }

        return true;
    }
}
