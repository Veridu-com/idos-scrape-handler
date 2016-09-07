<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Facebook;

use Cli\Handler\AbstractHandlerThread;

/**
 * Facebook's Profile Scraper.
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
            $this->rawBuffer = $this->service->request('/me?fields=id,first_name,last_name,gender,locale,languages,age_range,verified,birthday,education,email,hometown,location,picture.width(1024).height(1024),relationship_status,significant_other,work,friends');
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());

            return;
        }

        $this->parsedBuffer = json_decode($this->rawBuffer, true);
        if (empty($this->parsedBuffer)) {
            $this->lastError = 'Unknown error!';

            return;
        }

        if (isset($this->parsedBuffer['error'])) {
            $this->lastError = $this->parsedBuffer['error']['message'];

            return;
        }

        $this->logger->debug('Profile Scraping Finished');
    }
}
