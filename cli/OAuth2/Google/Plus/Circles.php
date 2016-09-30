<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Google\Plus;

use Cli\Handler\AbstractHandlerThread;

/**
 * Google Plus Circle's Profile Scraper.
 */
class Circles extends AbstractHandlerThread {
    /**
     * {@inheritdoc}
     */
    public function execute() : bool {
        try {
            $rawEndpoint = $this->worker->getSDK()
                ->Profile($this->worker->getUserName())
                ->Raw;
            // Retrieve profile data from Google's API
            $rawBuffer = $this->worker->getService()->request('https://www.googleapis.com/plus/v1/people/me/people/visible');
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

        if (!isset($parsedBuffer['items'])) {
            $this->lastError = 'Unexpected response format';

            return false;
        }

        if (count($parsedBuffer['items'])) {
            if (! $this->worker->isDryRun()) {
                // Send circle data to idOS API
                try {
                    $this->worker->getLogger()->debug(
                        sprintf(
                            '[%s] Uploading circles',
                            static::class
                        )
                    );
                    $rawEndpoint->upsertOne(
                        $this->worker->getSourceId(),
                        'circles',
                        $parsedBuffer['items']
                    );
                } catch (\Exception $exception) {
                    $this->lastError = $exception->getMessage();

                    return false;
                }
            }
        }

        return true;
    }
}
