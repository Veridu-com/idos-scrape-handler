<?php
/*
 * Copyright (c) 2012-2016 Veridu Ltd <https://veridu.com>
 * All rights reserved.
 */

declare(strict_types = 1);

namespace Cli\OAuth2\Dropbox;

use Cli\Handler\AbstractHandlerThread;

/**
 * Dropbox's metadata scraper.
 */
class Metadata extends AbstractHandlerThread {
    /**
     * {@inheritdoc}
     */
    public function execute() : bool {
        $rawEndpoint = $this->worker->getSdk()
            ->Profile($this->worker->getUserName())
            ->Raw;

        $service = $this->worker->getService();
        $logger  = $this->worker->getLogger();

        try {
            // Retrieve profile data from Dropbox's API
            $rawBuffer = $service->request(
                'files/list_folder',
                'POST',
                json_encode(
                    [
                        'path'                                => '',
                        'recursive'                           => false,
                        'include_media_info'                  => true,
                        'include_deleted'                     => false,
                        'include_has_explicit_shared_members' => true
                    ]
                ),
                ['Content-Type' => 'application/json']
            );

            $parsedBuffer = json_decode($rawBuffer, true);
            if ($parsedBuffer === null) {
                throw new \Exception('Failed to parse response');
            }

            if (isset($parsedBuffer['error_summary'])) {
                throw new \Exception($parsedBuffer['error_summary']);
            }

            if (empty($parsedBuffer['entries'])) {
                throw new \Exception('Empty root folder');
            }
        } catch (\Exception $exception) {
            $this->lastError = $exception->getMessage();

            return false;
        }

        $buffer = $parsedBuffer['entries'];

        while ($parsedBuffer['has_more']) {
            $rawBuffer = $service->request(
                'files/list_folder/continue',
                'POST',
                json_encode(
                    [
                        'cursor' => $parsedBuffer['cursor']
                    ]
                ),
                ['Content-Type' => 'application/json']
            );

            $parsedBuffer = json_decode($rawBuffer, true);
            if ($parsedBuffer === null) {
                break;
            }

            if (isset($parsedBuffer['error_summary'])) {
                break;
            }

            if (! empty($parsedBuffer['entries'])) {
                $buffer = array_merge($buffer, $parsedBuffer['entries']);
            }
        }

        $numItems = count($buffer);

        $logger->debug(
            sprintf(
                '[%s] Retrieved %d items',
                static::class,
                $numItems
            )
        );

        if ($this->worker->isDryRun()) {
            $logger->debug(
                sprintf(
                    '[%s] Metadata data',
                    static::class
                ),
                $buffer
            );

            return true;
        }

        if ($numItems) {
            // Send metadata to idOS API
            try {
                $logger->debug(
                    sprintf(
                        '[%s] Sending data',
                        static::class
                    )
                );
                $rawEndpoint->upsertOne(
                    $this->worker->getSourceId(),
                    'metadata',
                    $buffer
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
        }

        return true;
    }
}
