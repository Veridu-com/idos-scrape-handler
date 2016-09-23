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
        try {
            $rawEndpoint = $this->worker->getSDK()
                ->Profile($this->worker->getUserName())
                ->Raw;
            // Retrieve profile data from Spotify's API
        } catch (\Exception $exception) {
            $this->lastError = $exception->getMessage();

            return false;
        }

        $contacts = [];
        $flag     = true;
        $start    = 0;
        try {
            while ($flag) {
                $rawBuffer = $this->worker->getService()->request("https://social.yahooapis.com/v1/user/{$this->worker->getSourceId()}/contacts;start=0;count=50?format=json&view=tinyusercard");

                $parsedBuffer = json_decode($rawBuffer, true);
                if ($parsedBuffer === null) {
                    $this->lastError = 'Failed to parse response';

                    return false;
                }

                // there is no info about error reporting via json in
                // https://developer.yahoo.com/social/rest_api_guide/
                /*if (isset($parsedBuffer['error'])) {
                    $this->lastError = $parsedBuffer['error']['message'];

                    return false;
                }
                */

                if (! isset($parsedBuffer['contacts']['total'], $parsedBuffer['contacts']['count'])
                    || $parsedBuffer['contacts']['total'] == 0
                    || $parsedBuffer['contacts']['count'] == 0
                ) {
                    $flag = false;
                }
                else {
                    $contacts = array_merge($contacts, $parsedBuffer['contacts']['contact']);
                    if ($start < $parsedBuffer['contacts']['total'] && $parsedBuffer['contacts']['count'] == 50) {
                        $start += 50;
                    } else {
                        $flag = false;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->lastError = $exception->getMessage();

            return false;
        }

        if (count($contacts)) {
            if (! $this->worker->isDryRun()) {
                // Send profile data to idOS API
                try {
                    $this->worker->getLogger()->debug(
                        sprintf(
                            '[%s] Uploading contacts',
                            static::class
                        )
                    );
                    $rawEndpoint->createOrUpdate(
                        $this->worker->getSourceId(),
                        'contacts',
                        $parsedBuffer
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
