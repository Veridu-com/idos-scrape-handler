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
            // Retrieve profile data from LinkedIn's API
            $rawBuffer = $this->worker->getService()->request('/people/~:(api-standard-profile-request,current-share,email-address,first-name,formatted-name,formatted-phonetic-name,headline,id,industry,last-name,location:(country:(code),name),maiden-name,num-connections,num-connections-capped,phonetic-first-name,phonetic-last-name,picture-url,picture-urls::(original),positions:(id,title,summary,start-date,end-date,is-current,company:(id,name,type,industry,ticker)),public-profile-url,site-standard-profile-request,specialties,summary)?format=json');

            $parsedBuffer = json_decode($rawBuffer, true);
            if ($parsedBuffer === null) {
                throw new \Exception('Failed to parse response');
            }

            if (isset($parsedBuffer['errorCode'])) {
                throw new \Exception($parsedBuffer['message']);
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
