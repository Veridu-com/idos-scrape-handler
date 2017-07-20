<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Google\GMail;

use Cli\OAuth2\Google\AbstractGoogleThread;
use Cli\Utils\Backoff;

/**
 * Gmail Labels's Profile Scraper.
 */
class Labels extends AbstractGoogleThread {
    /**
     * Fetches gmail labels data.
     *
     * @throws \Exception
     *
     * @return \Generator
     */
    private function fetchAllLabels() : \Generator {
        $service = $this->worker->getService();
        $buffer  = [];
        $backoff = new Backoff(
            self::YIELD_ENABLED,
            self::YIELD_INTERVAL,
            self::YIELD_MULTIPLIER
        );
        try {
            $fetch = $this->fetchAll(
                'https://www.googleapis.com/gmail/v1/users/me/labels',
                '',
                'labels'
            );

            foreach ($fetch as $labels) {
                foreach ($labels as $label) {
                    $rawBuffer = $service->request(
                        sprintf(
                            'https://www.googleapis.com/gmail/v1/users/me/labels/%s',
                            $label['id']
                        )
                    );

                    $parsedBuffer = json_decode($rawBuffer, true);
                    if ($parsedBuffer === null) {
                        throw new \Exception('Failed to parse response');
                    }

                    if (isset($parsedBuffer['error_summary'])) {
                        throw new \Exception($parsedBuffer['error_summary']);
                    }

                    $buffer[] = $parsedBuffer;
                    if ($backoff->canYield()) {
                        yield $buffer;
                        $buffer = [];
                    }
                }
            }

            if (isset($buffer)) {
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
            // Retrieve data from Google's API
            $fetch = $this->fetchAllLabels();

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

                    // Send labels to idOS API
                    $logger->debug(
                        sprintf(
                            '[%s] Sending data',
                            static::class
                        )
                    );
                    $rawEndpoint->upsertOne(
                        $this->worker->getSourceId(),
                        'labels',
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
