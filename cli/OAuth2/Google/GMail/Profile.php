<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Google\GMail;

use Cli\Handler\AbstractHandlerThread;

/**
 * Gmail's Profile Scraper.
 */
class Profile extends AbstractHandlerThread {
    /**
     * {@inheritdoc}
     */
    public function execute() : bool {
        try {
            $rawEndpoint = $this->worker->getSDK()
                ->Profile($this->worker->getUserName())
                ->Raw;
            // Retrieve profile data from Google's API
            $rawBuffer = $this->worker->getService()->request('https://www.googleapis.com/gmail/v1/users/me/profile');
        } catch (\Exception $exception) {
            $this->lastError = $exception->getMessage();

            return false;
        }

        $parsedBuffer = json_decode($rawBuffer, true);
        if ($parsedBuffer === null) {
            $this->lastError = 'Failed to parse response';

            return false;
        }

        if (isset($parsedBuffer['error'])) {
            $this->lastError = $parsedBuffer['error']['message'];

            return false;
        }

        if (! $this->worker->isDryRun()) {
            // Send profile data to idOS API
            try {
                $this->worker->getLogger()->debug(
                    sprintf(
                        '[%s] Uploading profile',
                        static::class
                    )
                );
                $rawEndpoint->createOrUpdate(
                    $this->worker->getSourceId(),
                    'profile_gmail',
                    $parsedBuffer
                );
            } catch (\Exception $exception) {
                $this->lastError = $exception->getMessage();

                return false;
            }
        }

        return true;
    }
}
