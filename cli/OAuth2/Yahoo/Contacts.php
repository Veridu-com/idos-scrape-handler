<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Yahoo;

use Cli\Handler\AbstractHandlerThread;

/**
 * Yahoo contact's Profile Scraper.
 */
class Contacts extends AbstractHandlerThread {
    /**
     * {@inheritdoc}
     */
    public function execute() : bool {
        $rawEndpoint = $this->worker->getSdk()
            ->Profile($this->worker->getUserName())
            ->Raw;

        $service = $this->worker->getService();
        $logger  = $this->worker->getLogger();

        try {
            $rawBuffer = $service->request('https://social.yahooapis.com/v1/user/me/profile?format=json');

            $parsedBuffer = json_decode($rawBuffer, true);
            if ($parsedBuffer === null) {
                throw new \Exception('Failed to parse response for user id');
            }

            if (isset($parsedBuffer['error'])) {
                if (isset($parsedBuffer['error']['description'])) {
                    throw new \Exception($parsedBuffer['error']['description']);
                }

                throw new \Exception('Unknown API error');
            }

            $profileId = $parsedBuffer['profile']['guid'];

            $rawBuffer = $service->request(
                sprintf(
                    'https://social.yahooapis.com/v1/user/%s/contacts;start=0;count=max?format=json&view=tinyusercard',
                    $profileId
                )
            );

            $parsedBuffer = json_decode($rawBuffer, true);
            if ($parsedBuffer === null) {
                throw new \Exception('Failed to parse response');
            }

            if (isset($parsedBuffer['error'])) {
                if (isset($parsedBuffer['error']['description'])) {
                    throw new \Exception($parsedBuffer['error']['description']);
                }

                throw new \Exception('Unknown API error');
            }

            if (! isset($parsedBuffer['contacts']['contact'])) {
                throw new \Exception('Invalid response format');
            }
        } catch (\Exception $exception) {
            $this->lastError = $exception->getMessage();

            return false;
        }

        $numItems = count($parsedBuffer['contacts']['contact']);

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
                    $parsedBuffer['contacts']['contact'],
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
                    $parsedBuffer['contacts']['contact']
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
