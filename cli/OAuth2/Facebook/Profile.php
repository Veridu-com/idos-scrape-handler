<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Facebook;

use Cli\Handler\AbstractHandlerThread;

/**
 * Facebook's Profile Scraper.
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
            // Retrieve profile data from Facebook's API
            $rawBuffer = $this->worker->getService()->request('/me?fields=id,first_name,last_name,gender,locale,languages,age_range,verified,birthday,education,email,hometown,location,picture.width(1024).height(1024),relationship_status,significant_other,work,friends');

            $parsedBuffer = json_decode($rawBuffer, true);
            if ($parsedBuffer === null) {
                throw new \Exception('Failed to parse response');
            }

            if (isset($parsedBuffer['error']['message'])) {
                throw new \Exception($parsedBuffer['error']['message']);
            }
        } catch (\Exception $exception) {
            $this->lastError = $exception->getMessage();

            return false;
        }

        $logger->debug(
            sprintf(
                '[%s] Retrieved profile',
                static::class
            )
        );

        if ($this->worker->isDryRun()) {
            $logger->debug(
                sprintf(
                    '[%s] Profile data',
                    static::class
                ),
                $parsedBuffer
            );

            return true;
        }

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

        return true;
    }
}
