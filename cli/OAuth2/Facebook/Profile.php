<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Facebook;

use Cli\Handler\AbstractHandlerThread;
use idOS\SDK;

/**
 * Facebook's Profile Scraper.
 */
class Profile extends AbstractHandlerThread {
    /**
     * {@inheritdoc}
     */
    public function execute(SDK $sdk) : bool {
        try {
            // Retrieve profile data from Facebook's API
            $rawBuffer = $this->service->request('/me?fields=id,first_name,last_name,gender,locale,languages,age_range,verified,birthday,education,email,hometown,location,picture.width(1024).height(1024),relationship_status,significant_other,work,friends');
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

        if (! $this->dryRun) {
            // Send profile data to idOS API
            try {
                echo 'Uploading user profile', PHP_EOL;
                $sdk
                    ->Profile($this->userName)
                    ->Source($this->sourceId)
                    ->Raw
                    ->createNew(
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
