<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Dropbox;

use Cli\Handler\AbstractHandlerThread;

/**
 * Dropbox's Profile Scraper.
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
            $rawBuffer = $this->worker->getService()->request(
                'users/get_current_account',
                'POST',
                null,
                ['Content-Type' => '']
            );

            $parsedBuffer = json_decode($rawBuffer, true);
            if ($parsedBuffer === null) {
                throw new \Exception('Failed to parse response');
            }

            if (isset($parsedBuffer['error_description'])) {
                throw new \Exception($parsedBuffer['error_description']);
            }
        } catch (\Exception $exception) {
            $this->lastError = $exception->getMessage();

            return false;
        }

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
