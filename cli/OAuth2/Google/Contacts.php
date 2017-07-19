<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Google;

use Cli\Handler\AbstractHandlerThread;

/**
 * Google Contacts's Profile Scraper.
 */
class Contacts extends AbstractHandlerThread {
    /**
     * {@inheritdoc}
     */
    public function execute() : bool {
        $rawEndpoint = $this->worker->getSdk()
            ->Profile($this->worker->getUserName())
            ->Raw;

        $logger = $this->worker->getLogger();

        try {
            $rawBuffer = $this->worker->getService()->request(
                'https://www.google.com/m8/feeds/contacts/default/full?v=3.0&alt=json'
            );
            $rawBuffer = preg_replace('/[$]/u', '', $rawBuffer);

            $parsedBuffer = json_decode($rawBuffer, true);
            if ($parsedBuffer === null) {
                throw new \Exception('Failed to parse response');
            }

            if (isset($parsedBuffer['error'])) {
                if (isset($parsedBuffer['error']['error_description'])) {
                    throw new \Exception($parsedBuffer['error']['error_description']);
                }

                throw new \Exception('Unknown API error');
            }

            if (! isset($parsedBuffer['feed']['entry'])) {
                throw new \Exception('No entries found');
            }
        } catch (\Exception $exception) {
            $this->lastError = $exception->getMessage();

            return false;
        }

        $numItems = count($parsedBuffer['feed']['entry']);

        $logger->debug(
            sprintf(
                '[%s] Retrieved %d items',
                static::class,
                $numItems
            )
        );

        if ($numItems) {
            if ($this->worker->isDryRun()) {
                $this->worker->writeData(
                    $parsedBuffer['feed']['entry'],
                    static::class
                );

                return true;
            }

            // Send data to idOS API
            try {
                $logger->debug(
                    sprintf(
                        '[%s] Sending data',
                        static::class
                    )
                );
                $rawEndpoint->upsertOne(
                    $this->worker->getSourceId(),
                    'contacts',
                    $parsedBuffer['feed']['entry']
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
