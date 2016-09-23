<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Google\Plus;

use Cli\Handler\AbstractHandlerThread;

/**
 * Google Plus Activities's Profile Scraper.
 */
class Activities extends AbstractHandlerThread {
    /**
     * {@inheritdoc}
     */
    public function execute() : bool {
        try {
            $rawEndpoint = $this->worker->getSDK()
                ->Profile($this->worker->getUserName())
                ->Raw;
            // Retrieve profile data from Google's API
            $rawBuffer = $this->worker->getService()->request('https://www.googleapis.com/plus/v1/people/me/activities/public?maxResults=100');
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

        $buffer = $parsedBuffer['items'];
        if (! $this->worker->isDryRun()) {
            // Send activities data to idOS API
            try {
                $this->worker->getLogger()->debug(
                    sprintf(
                        '[%s] Uploading activities',
                        static::class
                    )
                );
                $rawEndpoint->createOrUpdate(
                    $this->worker->getSourceId(),
                    'activities',
                    $buffer
                );
            } catch (\Exception $exception) {
                $this->lastError = $exception->getMessage();

                return false;
            }
        }

        $buffer = [];
        do {
            if (! isset($parsedBuffer['pageToken'])) {
                break;
            }

            $data         = $this->worker->getService()->request('https://www.googleapis.com/plus/v1/people/me/activities/public?maxResults=100&pageToken=' . $parsedBuffer['pageToken']);
            $parsedBuffer = json_decode($data, true);

            if ($parsedBuffer === null) {
                $this->lastError = 'Failed to parse response';

                return false;
            }

            if (isset($parsedBuffer['error'])) {
                $this->lastError = $parsedBuffer['error']['message'];

                return false;
            }

            $count = count($parsedBuffer['items']);

            if ($count) {
                $buffer = array_merge($buffer, $parsedBuffer['items']);
                if (! $this->worker->isDryRun()) {
                    // Send activities data to idOS API
                    try {
                        $this->worker->getLogger()->debug(
                            sprintf(
                                '[%s] Uploading activities',
                                static::class
                            )
                        );
                        $rawEndpoint->createOrUpdate(
                            $this->worker->getSourceId(),
                            'activities',
                            $buffer
                        );
                    } catch (\Exception $exception) {
                        $this->lastError = $exception->getMessage();

                        return false;
                    }
                }
            }
        } while (! empty($parsedBuffer['items']));

        return true;
    }
}
