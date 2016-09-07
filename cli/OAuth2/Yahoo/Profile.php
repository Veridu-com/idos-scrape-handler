<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Yahoo;

use Cli\Handler\AbstractHandlerThread;

/**
 * Yahoo's Profile Scraper.
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
            $this->rawBuffer = $this->service->request('https://social.yahooapis.com/v1/user/me/profile?format=json');
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
            $this->lastError = $this->parsedBuffer['error']['detail'];
            return;
        }

        $this->logger->debug('Profile Scraping Finished');
    }
}
