<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\LinkedIn;

use Cli\Handler\AbstractHandlerThread;

/**
 * LinkedIn's Profile Scraper.
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
            // Retrieve profile data from Linkedin's API
            $rawBuffer = $this->worker->getService()->request('/people/~:(id,first-name,last-name,phonetic-first-name,phonetic-last-name,location,num-connections-capped,positions,picture-url,picture-urls::(original),public-profile-url,email-address,last-modified-timestamp,educations,courses,volunteer,three-current-positions,three-past-positions,num-recommenders,recommendations-received,following,date-of-birth,phone-numbers,main-address,twitter-accounts)?format=json');
        } catch (\Exception $exception) {
            $this->lastError = $exception->getMessage();

            return false;
        }

        $parsedBuffer = json_decode($rawBuffer, true);
        if ($parsedBuffer === null) {
            $this->lastError = 'Failed to parse response';

            return false;
        }

        if (isset($parsedBuffer['errorCode'])) {
            $this->lastError = $parsedBuffer['message'];

            return false;
        }

        $parsedBuffer['updated'] = time();

        if (! $this->worker->isDryRun()) {
            // Send profile data to idOS API
            try {
                $this->worker->getLogger()->debug(
                    sprintf(
                        '[%s] Uploading profile',
                        static::class
                    )
                );
                $rawEndpoint->upsertOne(
                    $this->worker->getSourceId(),
                    'profile',
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
