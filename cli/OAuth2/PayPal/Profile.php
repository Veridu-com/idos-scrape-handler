<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\PayPal;

use Cli\Handler\AbstractHandlerThread;

/**
 * PayPal's Profile Scraper.
 */
class Profile extends AbstractHandlerThread {
    /**
     * {@inheritdoc}
     */
    public function execute() : bool {
        $rawEndpoint = $this->worker->getSdk()
            ->Profile($this->worker->getUserName())
            ->Raw;

        $logger = $this->worker->getLogger();

        try {
            $rawBuffer = $this->worker->getService()->request('/identity/openidconnect/userinfo/?schema=openid');

            $parsedBuffer = json_decode($rawBuffer, true);
            if ($parsedBuffer === null) {
                throw new \Exception('Failed to parse response');
            }

            if (isset($parsedBuffer['error'])) {
                if (isset($parsedBuffer['error']['message'])) {
                    throw new \Exception($parsedBuffer['error']['message']);
                }

                throw new \Exception('Unknown API error');
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
            $this->worker->writeData(
                $parsedBuffer,
                static::class
            );

            return true;
        }

        // Send data to idOS API
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
