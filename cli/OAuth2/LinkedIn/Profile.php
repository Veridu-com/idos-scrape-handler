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
     * Thread run method.
     *
     * @return void
     */
    public function run() {
        $this->logger->debug('Profile Scraping Started');
        try {
            $this->rawBuffer = $this->service->request('/people/~:(id,first-name,last-name,phonetic-first-name,phonetic-last-name,location,num-connections-capped,positions,picture-url,picture-urls::(original),public-profile-url,email-address,last-modified-timestamp,educations,courses,volunteer,three-current-positions,three-past-positions,num-recommenders,recommendations-received,following,date-of-birth,phone-numbers,main-address,twitter-accounts)?format=json');
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return;
        }

        $this->parsedBuffer = json_decode($this->rawBuffer, true);
        if (empty($this->parsedBuffer)) {
            $this->lastError = 'Unknown error!';
            return;
        }

        if (isset($this->parsedBuffer['errorCode'])) {
            $this->lastError = $this->parsedBuffer['message'];
            return;
        }

        $this->logger->debug('Profile Scraping Finished');
    }
}
