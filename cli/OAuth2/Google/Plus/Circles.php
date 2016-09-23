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
            $this->lastError = $parsedBuffer['error_description'];

            return false;
        }

        $circles = [];
        foreach ($parsedBuffer['items'] as $friend) {
            $circles[] = $friend['id'];
        }

        if (count($circles)) {
            if (! $this->worker->isDryRun()) {
                // Send circle data to idOS API
                try {
                    $this->worker->getLogger()->debug(
                        sprintf(
                            '[%s] Uploading circles',
                            static::class
                        )
                    );
                    $rawEndpoint->createOrUpdate(
                        $this->worker->getSourceId(),
                        'circles',
                        $circles
                    );
                } catch (\Exception $exception) {
                    $this->lastError = $exception->getMessage();

                    return false;
                }
            }

            foreach ($circles as $friend) {
                $friendData = $this->worker->getService()->request("https://www.googleapis.com/plus/v1/people/{$friend}");
                $friendJson = json_decode($friendData, true);

                if ($friendJson === null) {
                    $this->lastError = 'Failed to parse response';

                    continue;
                }

                if (isset($friendJson['error'])) {
                    $this->lastError = $friendJson['error']['message'];

                    continue;
                }

                if (! $this->worker->isDryRun()) {
                    // Send plus data to idOS API
                    try {
                        $this->worker->getLogger()->debug(
                            sprintf(
                                '[%s] Uploading plus',
                                static::class
                            )
                        );
                        $rawEndpoint->createOrUpdate(
                            $this->worker->getSourceId(),
                            'plus',
                            $friendJson
                        );
                    } catch (\Exception $exception) {
                        $this->lastError = $exception->getMessage();

                        return false;
                    }
                }
            }
        }

        return true;
    }
}
