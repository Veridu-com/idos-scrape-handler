<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth1\Twitter;

use Cli\Handler\AbstractHandlerThread;

/**
 * Twitter's Profile Scraper.
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
            $this->rawBuffer = $this->service->request('/account/verify_credentials.json?include_entities=true&skip_status=false');
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return;
        }

        $this->parsedBuffer = json_decode($this->rawBuffer, true);
        if (empty($this->parsedBuffer)) {
            $this->lastError = 'Unknown error!';
            return;
        }

        if (isset($this->parsedBuffer['errors'])) {
            $this->lastError = $this->parsedBuffer['errors'][0]['message'];
            return;
        }

        $this->logger->debug('Profile Scraping Finished');
    }
}
