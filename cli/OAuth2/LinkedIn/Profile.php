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
            $rawEndpoint = $this->worker->getSdk()
                ->Profile($this->worker->getUserName())
                ->Raw;
            // Retrieve profile data from Linkedin's API
            $rawBuffer = $this->worker->getService()->request('/people/~:(api-standard-profile-request,current-share,email-address,first-name,formatted-name,formatted-phonetic-name,headline,id,industry,last-name,location:(country:(code),name),maiden-name,num-connections,num-connections-capped,phonetic-first-name,phonetic-last-name,picture-url,picture-urls::(original),positions:(id,title,summary,start-date,end-date,is-current,company:(id,name,type,industry,ticker)),public-profile-url,site-standard-profile-request,specialties,summary)?format=json');
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
