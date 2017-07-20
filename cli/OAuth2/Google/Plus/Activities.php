<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Google\Plus;

use Cli\OAuth2\Google\AbstractGoogleThread;

/**
 * Google Plus Activities's Profile Scraper.
 */
class Activities extends AbstractGoogleThread {
    /**
     * Fetches google activities data.
     *
     * @throws \Exception
     *
     * @return \Generator
     */
    private function fetchAllActivities() : \Generator {
        $service = $this->worker->getService();
        $buffer  = [];
        $backoff = new Backoff(
            self::YIELD_ENABLED,
            self::YIELD_INTERVAL,
            self::YIELD_MULTIPLIER
        );
        try {
            $rawBuffer = $service->request(
                'https://www.googleapis.com/plus/v1/people/me/activities/public?maxResults=100'
            );
            while (true) {
                $parsedBuffer = json_decode($rawBuffer, true);
                if ($parsedBuffer === null) {
                    throw new \Exception('Failed to parse response');
                }

                if (isset($parsedBuffer['error'])) {
                    if (isset($parsedBuffer['error']['message'])) {
                        throw new \Exception($parsedBuffer['error']['message']);
                    }

                    throw new \Exception('Unknown API error');
                }

                if (empty($parsedBuffer['items'])) {
                    break;
                }

                $buffer = array_merge($buffer, $parsedBuffer['items']);
                if ($backoff->canYield()) {
                    yield $buffer;
                    $buffer = [];
                }

                if (! isset($parsedBuffer['pageToken'])) {
                    break;
                }

                $rawBuffer = $service->request(
                    sprintf(
                        'https://www.googleapis.com/plus/v1/people/me/activities/public?maxResults=100&pageToken=%s',
                        $parsedBuffer['pageToken']
                    )
                );
            }

            if (count($buffer)) {
                yield $buffer;
            }
        } catch (\Exception $exception) {
            // ensure that even if an exception get thrown, all buffer is returned
            if (count($buffer)) {
                yield $buffer;

                return;
            }

            throw $exception;
        }
    }

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
            $fetch = $this->fetchAll(
                'https://www.googleapis.com/plus/v1/people/me/activities/public',
                'maxResults=100',
                'items'
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

                    // Send activities to idOS API
                    $logger->debug(
                        sprintf(
                            '[%s] Sending data',
                            static::class
                        )
                    );
                    $rawEndpoint->upsertOne(
                        $this->worker->getSourceId(),
                        'activities',
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
