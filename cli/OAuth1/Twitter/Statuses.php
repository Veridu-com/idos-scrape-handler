<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth1\Twitter;

use Cli\Handler\AbstractHandlerThread;

/**
 * Twitter Status's Profile Scraper.
 */
class Statuses extends AbstractHandlerThread {
    /**
     * {@inheritdoc}
     */
    public function execute() : bool {
        try {
            $rawEndpoint = $this->worker->getSDK()
                ->Profile($this->worker->getUserName())
                ->Raw;
            // Retrieve profile data from Twitter's API
            $rawBuffer = $this->worker->getService()->request('/statuses/user_timeline.json?count=200');
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

        $buffer = $parsedBuffer;

        if (! $this->worker->isDryRun()) {
            // Send statuses data to idOS API
            try {
                $this->worker->getLogger()->debug(
                    sprintf(
                        '[%s] Uploading statuses',
                        static::class
                    )
                );
                $rawEndpoint->upsertOne(
                    $this->worker->getSourceId(),
                    'statuses',
                    $buffer
                );
            } catch (\Exception $exception) {
                $this->lastError = $exception->getMessage();

                return false;
            }
        }

        $minId = gmp_init(PHP_INT_MAX);
        //find the min id value
        foreach ($parsedBuffer as $item) {
            if (gmp_cmp($minId, $item['id_str']) == 1) {
                $minId = gmp_init($item['id_str']);
            }
        }

        do {
            $lastId       = $minId;
            $data         = $this->worker->getService()->request('/statuses/user_timeline.json?count=200&max_id=' . gmp_strval(gmp_sub($minId, -1)));
            $parsedBuffer = json_decode($data, true);

            if ($parsedBuffer === null) {
                $this->lastError = 'Failed to parse response';

                return false;
            }

            if (isset($parsedBuffer['error'])) {
                $this->lastError = $parsedBuffer['error']['message'];

                return false;
            }

            $buffer = array_merge($buffer, $parsedBuffer);

            if (count($parsedBuffer)) {
                if (! $this->worker->isDryRun()) {
                    // Send statuses data to idOS API
                    try {
                        $this->worker->getLogger()->debug(
                            sprintf(
                                '[%s] Uploading statuses',
                                static::class
                            )
                        );
                        $rawEndpoint->upsertOne(
                            $this->worker->getSourceId(),
                            'statuses',
                            $buffer
                        );
                    } catch (\Exception $exception) {
                        $this->lastError = $exception->getMessage();

                        return false;
                    }
                }
            }

            //find the min id value
            foreach ($parsedBuffer as $item) {
                if (gmp_cmp($minId, $item['id_str']) == 1) {
                    $minId = gmp_init($item['id_str']);
                }
            }
        } while (gmp_cmp($lastId, $minId) != 0);

        return true;
    }
}
