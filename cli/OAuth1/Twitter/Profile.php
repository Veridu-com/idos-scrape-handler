<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth1\Twitter;

use Cli\Handler\AbstractHandlerThread;

/**
 * Twitter's Profile Scraper.
 */
class Profile extends AbstractHandlerThread {
    /**
     * {@inheritdoc}
     */
    public function execute() : bool {
        $rawEndpoint = $this->worker->getSdk()
            ->Profile($this->worker->getUserName())
            ->Raw;

        $logger = $this->worker->getLogger();

        try {
            // Retrieve profile data from Twitter's API
            $rawBuffer = $this->worker->getService()->request('/account/verify_credentials.json?include_entities=true&skip_status=false&include_email=true');

            $parsedBuffer = json_decode($rawBuffer, true);
            if ($parsedBuffer === null) {
                throw new \Exception('Failed to parse response');
            }

            if (isset($parsedBuffer['errors'][0]['message'])) {
                throw new \Exception($parsedBuffer['errors'][0]['message']);
            }
        } catch (\Exception $exception) {
            $this->lastError = $exception->getMessage();

            return false;
        }

        $parsedBuffer['updated'] = time();

        $logger->debug(
            sprintf(
                '[%s] Retrieved profile',
                static::class
            )
        );

        if (! $this->worker->isDryRun()) {
            // Send profile data to idOS API
            try {
                $logger->debug(
                    sprintf(
                        '[%s] Sending data',
                        static::class
                    )
                );
                $rawEndpoint->upsertOne(
                    $this->worker->getSourceId(),
                    'profile',
                    $parsedBuffer
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
